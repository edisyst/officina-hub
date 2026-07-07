# Note architetturali per modulo

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
