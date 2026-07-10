# Note architetturali per modulo

## Step 34 — Undo operativo e activity feed

- **`UndoHandler` interface** (`app/Contracts/`): `supports(Activity): bool`, `undo(Activity, User): Activity`. Handler registrati in `config/undo.php` array — estendibile senza toccare il core.
- **`UndoService::canUndo()`**: gate a 4 condizioni: `properties.undoable === true`, `properties.undone_at` assente, `created_at >= now() - UNDO_WINDOW_MINUTES`, autore oppure ruolo admin.
- **`UndoService::undo()`**: `DB::transaction` + `lockForUpdate()` sull'activity — ricontrolla `undone_at` dentro la transaction (anti doppio-undo concorrente). Su successo stampa `undone_at`, `undone_by`, `compensazione_id` nelle properties.
- **`StockMovementUndoHandler`**: soggetto `MovimentoMagazzino@created`. Storno via `CaricoManualeAction` con tipo opposto (carico → scarico, scarico → carico) e note `"Storno movimento #N"`. Compensazione non marcata undoable.
- **`WorkOrderStatusUndoHandler`**: soggetto `Commessa@updated` con `properties.old.stato`. Ripristina via `AggiornaStatoAction` solo se `commessa->puoTransireA($statoPrecedente)` è true — in pratica solo Sospesa ↔ InLavorazione.
- **`WorkOrderPartUndoHandler`**: soggetto `CommessaRiga` con `log_name='commessa_riga'@created`. Elimina riga via `delete()` (CommessaRigaObserver gestisce ha_righe_garanzia). Activity di compensazione su `Commessa` con event `compensazione`.
- **Marcatura undoable**: `AggiornaStatoAction` e `CaricoManualeAction` aggiornano l'activity auto-creata da LogsActivity post-fatto (`Activity::latest('id')->first()`). Compensazioni e undo automatici escludono la marcatura.
- **`EmitsActionCompleted` trait** (Livewire components): `markLastActivityUndoable(Model)` trova e marca, `emitActionCompleted(msg, id)` fa `$this->dispatch('action-completed', ...)`.
- **`UndoToast`**: layout component, ascolta `#[On('action-completed')]`. Toast Alpine countdown (secondi rimanenti), pulsante Annulla chiama `UndoService::undo()`. Dismiss manuale o auto alla scadenza.
- **`ActivityFeedService::humanize()`**: mapping (subject_type + event + log_name) → frase italiana. Eager load causer+subject già nel query del Feed. Fallback `"{autore} ha eseguito {event} su {tipo}"`.
- **`Activity\Feed`**: paginata 25 voci, filtri `causer_id`, `subject_type`, `created_at` range. `canUndo()` chiamato per-riga per mostrare pulsante Annulla.
- **`Activity\FeedWidget`**: ultime 10 attività, nella dashboard solo per admin|accettatore.
- **Env**: `UNDO_WINDOW_MINUTES=10` (default in `config/undo.php`).
- **Spatie activity properties schema**: `undoable: true | false`, `undone_at: ISO8601?`, `undone_by: user_id?`, `compensazione_id: activity_id?`. Convenzione solo a livello applicativo, nessuna colonna aggiuntiva.

## Step 33 — Suggerimenti contestuali da storico veicolo

- **`outcome` su `commessa_righe`** enum('completed','declined') default 'completed'. Righe `declined` escluse da: totali commessa (`getTotaleImponibileAttribute` ecc.), PDF scheda/preventivo (filter collection `where('outcome','!=','declined')`), `MarginalitaService::calcola()` (loop collezione), `MarginalitaService` query DB aggregata (WHERE clause). Scope `CommessaRiga::completed()` per query dirette.
- **`CommessaRigaObserver::updated()`**: quando `outcome` cambia a `declined` crea `VehicleRecommendation(source=declined)` se non esiste già pending equivalente; quando torna `completed` soft-deletes la recommendation pending corrispondente (reversibile prima della chiusura OdL).
- **`VeicoloObserver::updated()`**: triggera `RecommendationEngineService::refreshFor()` su ogni cambio `km_attuali`.
- **`RecommendationEngineService::refreshFor(Veicolo)`**: idempotente — non crea mai duplicati pending (stessa `source + title + vehicle_id`). Tre sorgenti:
  - *declined*: gestita dall'Observer, non dall'engine.
  - *deadline*: query `scadenze` entro `config('recommendations.deadline_horizon_days', 60)` giorni. Feature-check `Schema::hasTable('scadenze')` — se assente, la sorgente viene saltata silenziosamente.
  - *mileage*: per ogni `MaintenanceRule` attiva, cerca ultima esecuzione con doppio lookup: 1) `VehicleRecommendation` accepted con `resolved_work_order_id` + titolo match; 2) fuzzy case-insensitive su `LOWER(commessa_righe.descrizione)` delle commesse del veicolo. Crea recommendation se `km_attuali − km_ultima ≥ every_km` (o mesi trascorsi ≥ `every_months`, o nessuna storia).
- **`Livewire\Recommendations\Panel`**: montato nel dettaglio OdL sopra i tab, solo se `commessa->veicolo_id` presente. `mount()` triggera `refreshEngine()` (lazy). Azioni: `addToWorkOrder()` crea `CommessaRiga(tipo=manodopera, outcome=completed)` + segna recommendation `accepted + resolved_work_order_id`; `confirmDismiss()` segna `dismissed` con motivo opzionale.
- **`Livewire\Impostazioni\GestioneManutenzioni`**: CRUD standard (no sortable). Validazione via `MaintenanceRuleRequest` — almeno uno tra `every_km` e `every_months` obbligatorio (custom `withValidator`).
- **Feature check scadenzario**: `Schema::hasTable('scadenze')` nel service — non in `boot()` del provider (evita query durante CLI migrate).

## Step 32 — Bulk actions e inline editing tabelle

- **`WithBulkSelection` trait** (`app/Livewire/Concerns/`): `selectedIds[]`, `selectPage`, `selectAll`. Il flag `selectAll=true` rappresenta "tutti i risultati del filtro corrente" (Gmail-style) — gli ID non sono materializzati; `resolveIds()` esegue la query filtro al momento dell'azione. Reset automatico di selezione e pagina a ogni cambio filtro/ricerca.
- **`authorizeBulk(string $action)`**: hook astratto implementato da ciascun componente. Usa `$this->authorize('create', Model::class)` (no istanza richiesta) — semanticamente equivalente a "può modificare".
- **`#[Computed] selectionCount()`**: calcolato via `getBulkQuery()->count()` se `selectAll=true`, altrimenti `count($selectedIds)`. Passato esplicitamente al Blade (`'selectionCount' => $this->selectionCount()`).
- **`BulkChangeWorkOrderStatusAction`**: orchestrates `AggiornaStatoAction` per-record in loop (chunk 50). Ogni OdL è indipendente — un fallimento non annulla i precedenti. Report: `['success' => [...ids], 'skipped' => [['id', 'numero', 'motivo']]]`. Motivo estratto dalla `ValidationException` di `AggiornaStatoAction`.
- **`BulkReorderAction`**: Step 15 presente — crea `OrdineFornitore` in bozza per fornitore preferenziale via `NumerazioneService`. Articoli senza `fornitore_id` restituiti in `senza_fornitore[]`. `toCsv()` disponibile come fallback (utile per test). Tutto in `DB::transaction` per coerenza numerazione.
- **`BulkUpdateLocationAction`**: aggiorna `ubicazione` con activitylog `old/new` per ogni articolo (chunk 50).
- **`InlineEdit` Blade component** (`x-inline-edit`): Alpine.js `x-data` con `editing`, `val`, `orig`. Click attiva l'input (`$refs.inp.select()`); Enter chiama `$wire.salvaInlineEdit(id, field, val)`; Esc ripristina; Tab salva e naviga alla cella successiva via `data-inline-next`. Il metodo Livewire restituisce `bool` — `false` su campo non ammesso o validazione fallita, senza causare errori visibili.
- **`salvaInlineEdit(int $id, string $field, mixed $value)`** su `ListaArticoli`: allowlist esplicita dei campi (`ubicazione`, `prezzo_vendita`, `scorta_minima`) con regole di validazione corrispondenti a quelle del form modale — single source of truth dentro il metodo. Validazione via `validator()` standalone (non `$this->validate()` perché nessuna proprietà Livewire dichiarata). Autorizzazione sull'istanza `Articolo::findOrFail($id)`.
- **Stampa massiva** (`/commesse/stampa-massiva?ids=...`): controller thin `CommessaController::stampaMassiva()`, max 200 ID, view `print/work-orders-batch.blade.php` con CSS `page-break-after: always`.
- **Export CSV**: `response()->streamDownload()` su entrambe le tabelle — BOM UTF-8 + separatore `;`.
- **Chunk size 50**: per selezioni > 500 record il loop itera su chunk di 50. Sincrono (code attive presenti ma non obbligatorie per default).

## Step 31 — Vista rapida stato veicolo

- **`VehicleStatusService::lookup(string $term)`**: cerca per targa (prefix), cognome/nome/ragione_sociale, telefono; max 5 risultati; eager loading batch senza N+1 (query totali ≤ 12 per 5 veicoli).
- **`VehicleStatusCard` DTO** (readonly): targa, veicolo, cliente, OdL attivo/storico, semaforo ricambi, approvazione DVI, ultima comunicazione. Zero dati economici.
- **Semaforo ricambi**: grigio (nessun articolo), verde (giacenza ok o `MovimentoMagazzino::Scarico` già registrato per quella commessa), giallo (giacenza < fabbisogno, non scaricato). Calcolo su `commessa_righe` tipo `Articolo` con `articolo_id` non null.
- **Feature check via `Schema::hasTable()`** in costruttore del service:
  - Step 10 (`dvi_ispezioni`): stato approvazione dell'ultima `DviIspezione` per OdL.
  - Step 15 (`ordine_fornitore_righe`): per ogni ricambio giallo, cerca `OrdineFornitoreRiga` su ordine in stato pendente e ne espone stato + `data_consegna_prevista`.
  - Step 28 (`commesse.data_ora_consegna_prevista`): già presente — migrazione guard no-op se colonna esiste.
  - Step 30 (`communications`): ultima `Communication` per OdL (canale, data, estratto 80 char).
- **Migrazione guard** `ensure_expected_delivery_on_commesse`: `up()` crea `data_ora_consegna_prevista` solo se assente; `down()` è no-op documentato (impossibile ricostruire chi l'ha creata).
- **Livewire `VehicleStatus\Lookup`**: `wire:model.live.debounce.200ms`, min 2 char, computed `results` lazy. Auto-expand card se un unico match con targa esatta (`strtoupper` comparato).
- **Accesso rapido**: pulsante navbar `fa-phone` + voce sidebar "Stato veicolo" + shortcut globale F2 (JS vanilla in `app.blade.php`, non attivo se focus su input/textarea/select).

## Step 29 — Matrici prezzi ricambi e tariffe orarie manodopera

- **Tabelle:** `matrici_prezzo`, `matrici_prezzo_scaglioni`, `tariffe_orarie`. `commessa_righe` ha `tariffa_oraria_id` FK nullable come riferimento (snapshot del valore resta in `prezzo_unitario`).
- **`TariffaOraria`** ≠ `TariffaManodopera`: la prima è una semplice tariffa oraria nominata (Meccanica/Elettrauto/…) che precompila `prezzo_unitario`; la seconda è il catalogo operazioni con `minuti_standard` e `prezzo_listino`.
- **Bordi scaglione documentati:** `costo_da` inclusivo (≥), `costo_a` esclusivo (<). L'ultimo scaglione ha `costo_a = null` (aperto, cattura tutti i costi superiori). `validateScaglioni()` rifiuta buchi, overlap, costo_a null non in coda.
- **`MatricePrezzoService::suggestPrice()`**: null se nessuna matrice default attiva o costo ≤ 0. Arrotondamento per eccesso al multiplo (0.10 / 0.50 / 1.00).
- **`MatricePrezzoService::setDefault()`**: transazionale — togli il flag da tutte, poi imposta la nuova.
- **Auto-suggerimento in `CommessaRigaObserver::creating()`**: se `tipo=Articolo`, `prezzo_unitario=0` e `prezzo_acquisto` disponibile, valorizza `prezzo_unitario` dalla matrice default. Non sovrascrive valori manuali.
- **Badge UI in OdL:** "matrice" (verde) se `prezzo_unitario ≈ suggestPrice(prezzo_acquisto)`; "manuale" (giallo) se modificato. Pulsante "Riapplica matrice" ricalcola al click.
- **Tariffa oraria su riga manodopera:** select nelle tariffe attive; selezionando precompila `prezzo_unitario`. Modifica manuale possibile. Default pre-selezionato all'apertura modal nuova riga.
- **Invariante:** cambiare matrice/tariffa NON tocca gli OdL già esistenti — il prezzo è uno snapshot sulla riga.
- **Un solo default:** applicativo (non DB constraint); `setDefault()` usa transaction; `toggleAttiva()` blocca disattivazione se `is_default=true`.
- **Seeder `PricingSeeder`:** matrice "Standard" con 4 scaglioni realistici (default); tariffe Meccanica/Elettrauto/Carrozzeria.

## Step 28 — Ore preventivate vs effettive e marginalità OdL

- `commessa_righe.ore_preventivate` decimal(6,2) nullable: budget ore per riga manodopera (distinto da `quantita` = ore fatturate).
- `commessa_righe.prezzo_acquisto` già esistente: snapshot costo ricambio al momento dell'aggiunta. `CommessaRigaObserver::creating()` lo copia da `articolo.prezzo_acquisto` se 0/null — non sovrascrive valori manuali.
- `commesse.data_ora_consegna_prevista` datetime nullable: aggiunta separata da `data_uscita_prevista` (date) esistente per non rompere codice esistente.
- `MarginCalculatorService` in `app/Services/Commesse/`: restituisce `CommessaMargins` DTO con ricavi/costi/margini separati per ricambi e manodopera, ore (preventivate/effettive/delta), conteggi trasparenza righe senza dati.
- `config('margins.labor_cost_per_hour')` (env `LABOR_COST_PER_HOUR`): se null, `costoManodopera` nel DTO è null e i margini mostrano solo il ricavo. Il costo per-meccanico (`users.costo_orario`) ha priorità sul config.
- Ore effettive: da `lavorazioni.minuti_effettivi` (time tracking Step 2). Ore preventivate: da `commessa_righe.ore_preventivate` (sum righe manodopera).
- Gate `view-margins` (admin + cassa): registrato in `AppServiceProvider`. Admin bypassa comunque via `Gate::before`. Le info-box ore sono visibili a tutti; i valori economici solo ai ruoli con gate.
- `Livewire\Reports\Profitability`: filtri data/meccanico/stato, tabella per meccanico (ore, efficienza), tabella OdL paginata, export CSV streaming UTF-8+BOM. Query usa `concatExpr()` helper per compatibilità MySQL/SQLite.
- Info-box nell'OdL (`dettaglio-commessa.blade.php`): aggiornate a ogni render Livewire via `MarginCalculatorService` (già nel `mount()` e nel `render()` di `DettaglioCommessa`).

## Step 27 — Accettazione veicolo one-screen

- `CheckIn` Livewire a 3 stadi (targa → veicolo → OdL) senza redirect intermedi.
- `AcceptanceContextService`: feature check `packagesEnabled()` e `plateLookupEnabled()` — assenza dei moduli non rompe il flusso.
- `CheckInVehicleAction::execute()`: `DB::transaction` unica che crea/aggiorna Cliente, Veicolo (km monotono), Commessa e righe pacchetto; rollback completo su eccezione.
- Km attuali aggiornati solo se il nuovo valore > valore esistente (validazione server-side al passaggio allo stadio 3).
- Stampa scheda accettazione: evento JS `apriStampaScheda` apre `pdf.scheda` in nuova scheda dopo il redirect al dettaglio commessa.
- Targa normalizzata (`strtoupper(trim())`); match esatto avanza automaticamente allo stadio 2A senza click.

## Step 3 — Magazzino

- `MovimentoMagazzino` immutabile: `$timestamps = false`, nessun update/delete dopo creazione.
- `giacenza_attuale` su `Articolo` denormalizzata: aggiornata da ogni Action con DB transaction + `lockForUpdate()`.
- `CommessaObserver::updated()` invoca `ScaricoCommessaAction` solo al passaggio a `StatoCommessa::Completata`.
- Scarico in negativo non blocca completamento: crea movimento con nota "SCARICO IN NEGATIVO" e logga warning.
- Typeahead articoli in `GestioneRighe` usa `wire:model.live.debounce.300ms`.
- `ReportMagazzino::esportaCsv()` usa `response()->streamDownload()` — nessun pacchetto aggiuntivo.

## Step 4 — Fatturazione

- `Documento` immutabile una volta in stato `emessa` — correggere con nota di credito.
- `NumerazioneService::prossimo()` usa `lockForUpdate()` in transaction.
- `FatturaPAService::valida()` richiede `storage/app/fatturapa/xsd/Schema_VFPR12.xsd` (da scaricare da AdE).
- `DocumentoObserver` popola `registro_iva` al passaggio a `emessa`.
- ZIP SdI in `storage/app/fatturapa/temp/` — eliminabile dopo download.

## Step 5 — QR / Checklist / Tablet

- `TabletLayout` è Blade component che renderizza `layouts/tablet.blade.php`.
- `<x-marcatempo-layout>` alias di `<x-tablet-layout subtitle="Marcatempo">`.
- jsQR caricato da CDN solo nel layout tablet — non va nel bundle Vite.
- Scanner QR: `getUserMedia` con `facingMode: 'environment'`; richiede HTTPS o localhost.
- Checklist: constraint `UNIQUE(checklist_template_id, commessa_id)` in `checklist_compilate`.
- Risposte progressive: ogni `wire:change` → `salvaRisposta()`; `completata_at` si imposta solo su "Finalizza".
- Foto checklist: `storage/app/allegati/checklist/`, servite tramite route autenticata.

## Step 6 — Notifiche / Email / Portale Cliente

- `MailConfigService::applica()` sovrascrive config SMTP a runtime da `settings` DB; invocata in `AppServiceProvider::boot()`.
- `setting(string $key)` helper globale in `app/helpers.php` (autoloadato via composer.json `files`).
- `AggiornaStatoAction` accoda email solo se: notifiche abilitate, cliente ha email, nessuna notifica accodata negli ultimi 5 min per stessa commessa.
- `NotificaLog`: `email_smtp_password` NON in `$fillable`, mai esposto in log o JS.
- `InviaRichiamiScadenza` Job idempotente: verifica `NotificaLog` ultime 24h per stessa scadenza.
- Portale cliente usa `URL::signedRoute` con `encrypt($cliente->id)` come token.

## Step 7 — Carrozzeria

- Modulo Carrozzeria: visibile solo per commesse tipo `carrozzeria`.
- `StatoCarrozzeria`: workflow parallelo a stato commessa; commessa passa a `completata` solo con `stato_carrozzeria = consegna`.
- `GeneraFatturaDoppiaAction`: crea due fatture (cliente + assicurazione) collegate via `documento_correlato_id`.
- `commesse.sinistro_id` senza FK constraint (FK inversa: `sinistri.commessa_id`) per evitare riferimento circolare.
- PDF scheda carrozzeria usa closure `$zonaClass` (non funzione named) per evitare redeclarazione in Blade compilato.

## Step 8 — Analytics

- `KpiService`: `Cache::remember` TTL 10 min. Usa `whereDate` (non `whereBetween`) per compatibilità SQLite.
- `datePartExpressions()`: `strftime` per SQLite, `YEAR/MONTH` per MySQL/MariaDB.
- Badge menu aggiornati ogni 2 minuti via `fetch('/api/menu-badges')`. Cache TTL 120s per-utente.
- Chart.js 2.9.4 in `app.js` esposto come `window.Chart`. Grafici in `x-init` Alpine.
- Export PDF meccanici: grafico catturato come base64 PNG via `chart.toBase64Image()`, passato a Livewire, poi a dompdf.
- Test analytics: `Cache::flush()` in `setUp()` per evitare collisioni cache.

## Step 9 — Sicurezza / Backup / Audit

### Sicurezza
- `SecurityHeaders` middleware globale via `bootstrap/app.php`.
- **CSP usa `'unsafe-inline'`** intenzionalmente: necessario per Livewire e Alpine.js — non è bug.
- Rate limiting: `throttle:login` (5/min per IP) su POST `/login`; `throttle:api-internal` (120/min) su `/api/*`.
- Sessione: lifetime 480 min; `secure=true` default. In sviluppo HTTP: `SESSION_SECURE_COOKIE=false` nel `.env`.

### Backup (spatie/laravel-backup)
- Include `storage/app/allegati` e `storage/app/fatturapa`.
- Scheduler: `backup:run` 02:00, `backup:clean` 02:30, `backup:monitor` 03:00.
- `BACKUP_NOTIFY_EMAIL` (env). ZIP cifrabile via `BACKUP_PASSWORD`.

### Audit log (spatie/laravel-activitylog v4)
- Trait `LogsActivity` su `Commessa`, `Documento`, `MovimentoMagazzino`, `User`.
- Vista `/audit-log` (solo admin): Livewire `Admin\AuditLog`. Log non eliminabile dall'interfaccia.

### SDI diretto (disabilitato)
- `SdiService` e `InviaDocumentoSdi` lanciano `RuntimeException` se `SDI_ABILITATO=false`.
- Procedura attivazione: `docs/sdi-diretto.md`.

## Step 10 — DVI

- `DviIspezione` → `DviVoce` → `DviMedia`: struttura separata da checklist. DVI è verso il cliente.
- Foto: `storage/app/dvi/foto/{anno}/{mese}/`. Video: upload chunked, chunk in `tmp/dvi/{upload_id}/chunk_N`.
- Thumbnail video: `ffmpeg -ss 00:00:01 -vframes 1`; se assente → placeholder SVG. Dockerfile: `apk add --no-cache ffmpeg`.
- Portale `/dvi/{token}` è HTML puro senza AdminLTE/Livewire/Alpine.
- Token DVI: opaco 64 char random, scadenza in DB (`link_scade_at`). Non usa `URL::signedRoute`.
- "Converti in preventivo": crea `commessa_righe` da voci DVI approvate. Tipo riga: `Manodopera`.
- `intervention/image` v4: usare `ImageManager(new Driver())` — non `Image::make()`.

## Step 11 — Tariffe / Pacchetti

- `ApplicaPacchettoAction::execute()`: in transaction crea righe commessa dal pacchetto, incrementa `utilizzi`.
- `tariffa_manodopera_id` e `pacchetto_servizio_id` su `commessa_righe` come FK nullable.
- Import CSV tariffe: delimitatore `;`, colonne `codice;descrizione;categoria;minuti_standard;prezzo_listino;iva_percentuale;tipo_veicolo`. Usa `updateOrCreate`.
- Analytics pacchetti: top 10 per utilizzi + tariffe fuori listino (scostamento > 15% da prezzo listino).

## Step 12 — Deposito Pneumatici

- `Pneumatico`: `codiceEtichetta()` → `{prefisso}-{anno}-{id:05d}`.
- `DepositoPneumatico`: movimenti immutabili; traccia azione, ubicazione, usura%.
- `EtichettaDepositoService::genera()`: PDF A6 o adesivo 100×50mm con QR → `/deposito/qr/{codice}`.
- `CambioStagionaleMassivo::creaAppuntamentiInBlocco()`: distribuisce appuntamenti nei 5 giorni lavorativi bilanciando carico.
- Mappa scaffali: SVG inline PHP, regex `([A-Z])(\d+)` sulle ubicazioni.
- PFU (D.Lgs. 152/2006): sistema NON genera documenti automaticamente — responsabilità dell'officina.

## Step 13 — Cortesia / Lookup Targa

### Veicoli di cortesia
- `PrestitoCortesia`: immutabile nel tracciamento; mai eliminare, solo aggiornare stato.
- `km_percorsi` e `delta_carburante` sono accessor calcolati — non colonne DB.
- Flusso consegna (4 step tablet): firma usa canvas nativo (non signature_pad).
- Flusso rientro: `km_rientro >= km_consegna` validato server-side e client-side.
- **Clausole legali contratto comodato** (`contratto-cortesia.blade.php`) sono testo fisso nel Blade — non configurabili da settings.
- Endpoint `/api/cortesia/disponibilita` restituisce `{risorse: [...], eventi: [...]}` — diverso da `/api/appuntamenti` (solo array).
- `patente_numero` e `patente_scadenza` esclusi da audit log e CSV export (dati sensibili).

### Lookup Targa
- Provider: `MockProvider` (deterministico), `InfoTargaProvider` (Bearer), `OpenApiProvider` (x-api-key).
- Cache 30 giorni per targa: `Cache::remember("targa_{$targa}", ...)`.
- **Non loggare mai la API key** — solo targa + HTTP status in caso di errore.

## Step 14 — Contabilità

- `PrimaNota`: `automatico=true` per record da `PagamentoObserver` — non modificabili dall'interfaccia.
- `CsvGenericoFormatter`: CSV UTF-8+BOM separatore `;` — compatibile Excel italiano.
- `TeamSystemFormatter`: tracciato larghezza fissa; codici conto configurabili in settings.
- `ZucchettiFormatter`, `DatagammaFormatter`: **TODO** — lanciano `\RuntimeException`.
- `ConservazioneService`: stub — **TODO**: integrare con conservatore accreditato AgID.
- `RiepilogoCommercialista`: PDF dompdf con timbro "NON HA VALORE FISCALE".

## Step 15 — Acquisti

- `OrdineFornitore`: numerazione `ORD-YYYY-NNNN` via `NumerazioneService::prossimoOrdineFornitore()`.
- `RicezioneMerceAction`: in transaction crea DDT fornitore, aggiorna `quantita_ricevuta`, chiama `CaricoManualeAction`.
- `PagamentoFornitoreObserver::created()`: crea `prima_nota` tipo `uscita` automaticamente.
- `FatturaAcquistoObserver::updated()`: al passaggio a `registrata` crea record `registro_iva tipo acquisti`; idempotente.
- `FatturaPAParser::parsaFatturaAcquisto()`: `SimpleXMLElement` nativo; gestisce FPR12 e FPA12 (strip namespace via regex).
- `MetodoPagamentoFornitore`: enum separato perché include `Riba` assente lato clienti.

## Step 16 — CRM

- **GDPR art. 6**: `InviaCampagnaEmail` lancia `\RuntimeException` se cliente senza `consenso_marketing`. Non bypassabile.
- `SegmentazioneService::aggiornaIncrementale()`: chiamato da `CommessaObserver` al passaggio a stato chiuso.
- `AggiornaPunteggiCrm` job: ricalcola segmenti per tutti clienti ogni notte alle 03:30.
- `InviaAuguriCompleanno` job: idempotente via `notifiche_log.sottotipo = 'compleanno'`.
- `InviaCampagnaEmail` job: idempotente via `campagna_invii.stato`.

## Step 17 — Garanzie

- `Garanzia`: scope `attive()` filtra `attiva=true` e `data_fine >= oggi`; `isInScadenza()` → `data_fine <= now()->addDays(30)`.
- `TipoGaranzia::richiedeCasaMadre()`: true per `GaranziaCostruttore` e `Convenzione`.
- `CommessaRiga`: `in_garanzia` bool, `garanzia_id` FK nullable, `casa_madre_id` FK nullable. `totale_cliente` = 0 se in_garanzia.
- `CommessaRigaObserver`: aggiorna `ha_righe_garanzia` su commessa ad ogni `saved`/`deleted`.
- `GeneraFatturaGaranziaAction::execute()`: in transaction crea 1 fattura cliente + 1 per ogni `casa_madre_id` unico; `documento_correlato_id` collega i documenti.
- `TipoEmissione::CasaMadre` ('casa_madre'): template FatturaPA usa dati `CasaMadre` in `CessionarioCommittente`.
- Page views usano `<x-app-layout>` (non `@extends`) perché `layouts/app.blade.php` usa `{{ $slot }}`.
