# Officina Hub — CLAUDE.md

Gestionale web on-premise per officine meccaniche (auto, moto, carrozzerie).
Stack: Laravel 11 + Livewire 3 + Alpine.js + AdminLTE 3 + MariaDB 11 + FullCalendar v6.

## Comandi Principali

### Sviluppo locale (Laragon)
```bash
# Avvia il server Laravel
php artisan serve

# Compila gli asset (Alpine.js + signature_pad)
npm run dev        # watch mode
npm run build      # build produzione

# Esegui le migration + seed
php artisan migrate:fresh --seed

# Queue worker (email + richiami scadenze)
php artisan queue:work --queue=default --tries=3 --backoff=60

# Scheduler (eseguire via cron ogni minuto in produzione)
php artisan schedule:run

# Test scheduler manualmente
php artisan schedule:run --verbose

# Invia manualmente i richiami scadenza
php artisan tinker --execute="dispatch(new \App\Jobs\InviaRichiamiScadenza)"

# Backup manuale
php artisan backup:run
php artisan backup:clean
php artisan backup:monitor

# Cache management
php artisan config:cache
php artisan view:clear
php artisan cache:clear

# Invalida cache KPI analytics manualmente (TTL 10 min)
php artisan tinker --execute="Cache::forget('kpi_sparkline_fatturato'); Cache::forget('kpi_grafico_fatturato');"
```

### Docker
```bash
# Avvia tutti i container
docker-compose up -d

# Accesso al container PHP
docker-compose exec app bash

# Log in tempo reale
docker-compose logs -f app

# Cron scheduler (già configurato in docker/php/entrypoint.sh):
# * * * * * cd /var/www/html && php artisan schedule:run >> /dev/null 2>&1

# Queue worker (già avviato da entrypoint.sh):
# php artisan queue:work --queue=default --tries=3 --backoff=60
```

### Test
```bash
# Tutti i test (usa SQLite in-memory)
php artisan test

# Solo feature test
php artisan test --testsuite=Feature

# Test specifico
php artisan test --filter CommessaFlussoTest
```

## Route Step 16

```
GET  /crm/dashboard          → crm.dashboard    (admin) — retention dashboard
GET  /crm/campagne           → crm.campagne     (admin) — campagne email
```

## Note architetturali Step 16

- **GDPR art. 6**: `consenso_marketing = false` è vincolo legale — `InviaCampagnaEmail` lancia `\RuntimeException` se tenta invio a cliente senza consenso. Non bypassabile da admin.
- `SegmentazioneService::aggiornaIncrementale()`: aggiornamento CRM su singolo cliente — chiamato da `CommessaObserver` al passaggio a stato chiuso. Nessun ricalcolo ad ogni request.
- `AggiornaPunteggiCrm` job: ricalcola `segmento_crm`, `valore_lifetime`, `numero_visite`, `ultima_visita_at` per tutti i clienti ogni notte alle 03:30.
- `InviaAuguriCompleanno` job: idempotente via `notifiche_log.sottotipo = 'compleanno'` + check data odierna. Solo clienti con `consenso_marketing = true` + `email` + `data_nascita` oggi (giorno+mese).
- `InviaCampagnaEmail` job: idempotente via `campagna_invii.stato`; non reinvia se già `inviata`. Delay Laravel via `->delay()` al momento pianificato.
- `CrmNota`: tabella `crm_note` con SoftDeletes; tipi: nota|chiamata|email|appuntamento|altro.
- `campagne_email` + `campagna_invii`: traccia lifecycle completo invii per cliente; `totale_destinatari`, `totale_inviati`, `totale_errori` aggiornati al completamento job.
- `notifiche_log` esteso con colonne `sottotipo` (varchar) e `campagna_email_id` (FK nullable) — migration 160006.
- Badge menu CRM: count clienti `a_rischio`, cache 60 min (non per-utente ma globale), aggiornato in `MenuBadgesController`.
- `EmailTemplateService::compilaManuale()`: aggiunto per campagne (oggetto+corpo diretti, senza settings key).
- Test: `CrmStep16Test` — unit segmentazione (nuovo/perso/a_rischio/attivo), campagna solo consensati, job compleanno, filtro segmento, nota CRM, route access.

## Route Step 15

```
GET  /acquisti/ordini                    → acquisti.ordini              (admin, accettatore)
GET  /acquisti/ordini/crea               → acquisti.ordini.create       (admin, accettatore)
GET  /acquisti/ordini/{id}               → acquisti.ordini.show         (admin, accettatore)
GET  /acquisti/ordini/{id}/ricevi        → acquisti.ordini.ricevi       (admin, accettatore)
GET  /acquisti/genera-ordini             → acquisti.genera-ordini       (admin, accettatore)
GET  /acquisti/fatture                   → acquisti.fatture             (admin, cassa)
```

## Note architetturali Step 15

- `OrdineFornitore`: numerazione `ORD-YYYY-NNNN` gestita da `NumerazioneService::prossimoOrdineFornitore()` — metodo aggiunto al service (query su tabella dedicata, non `documenti`).
- `RicezioneMerceAction`: in transaction crea DDT fornitore, aggiorna `quantita_ricevuta` su righe ordine, chiama `CaricoManualeAction` per ogni articolo, aggiorna stato ordine.
- `PagamentoFornitoreObserver::created()`: crea automaticamente `prima_nota` di tipo `uscita`; usa `MetodoPagamentoFornitore::aMetodoPrimaNota()` per il mapping.
- `FatturaAcquistoObserver::updated()`: al passaggio a stato `registrata` crea record in `registro_iva` con `tipo_registro = acquisti`; idempotente (controlla se già esiste).
- `FatturaPAParser::parsaFatturaAcquisto()`: usa `SimpleXMLElement` nativo; gestisce FPR12 e FPA12 (strip namespace prefix via regex). Auto-match fornitore per P.IVA, auto-match articoli per codice (non standard in FPA, raramente valorizzato).
- `MetodoPagamentoFornitore`: enum separato da `MetodoPagamento` perché include `Riba` non presente lato clienti; `aMetodoPrimaNota()` mappa a `MetodoPrimaNota` (Riba → Altro).
- `fattura_acquisto_id` aggiunto a `registro_iva` come FK nullable — coesiste con `documento_id` (vendite).
- `pagamento_fornitore_id` aggiunto a `prima_nota` come FK nullable — coesiste con `pagamento_id` (clienti).
- Scadenziario: tab "Clienti" (esistente) + tab "Fornitori" (fatture acquisto `registrata` con scadenza).
- Registro IVA: tab "Vendite" (esistente) + tab "Acquisti" (fatture acquisto `registrata|pagata`).
- `GeneraOrdiniDaSottoscorta`: raggruppa articoli sotto scorta per `fornitore_id`, genera un ordine per fornitore.
- Import XML ZIP: `FatturaPAParser::parsaZip()` estrae tutti gli XML dal ZIP in tmp dir, li parsa uno per uno.
- Test: `AcquistiStep15Test` — parser XML fixture, ricezione parziale/completa, prima nota uscita, registro IVA acquisti, route access.

## Route Step 14

```
GET  /contabilita/prima-nota             → contabilita.prima-nota        (admin, cassa)
GET  /contabilita/riepilogo              → contabilita.riepilogo         (admin, cassa)
GET  /contabilita/export-sdi-batch       → contabilita.export-sdi-batch  (admin, cassa)
```

## Note architetturali Step 14

- `PrimaNota`: tabella `prima_nota` con SoftDeletes; `automatico=true` per i record generati da `PagamentoObserver` — non modificabili dall'interfaccia.
- `PagamentoObserver::created()`: registrato in `AppServiceProvider`; crea automaticamente un record `prima_nota` di tipo `entrata` ad ogni pagamento fattura.
- `ContoPrimaNota::daMetodo()`: helper che mappa il metodo di pagamento al conto (contanti→cassa, carta→pos, altri→banca).
- Enums aggiunti: `TipoPrimaNota`, `MetodoPrimaNota`, `ContoPrimaNota`, `FormatoExportContabile`.
- `CsvGenericoFormatter`: produce CSV UTF-8+BOM con separatore `;` — sempre disponibile, compatibile Excel italiano.
- `TeamSystemFormatter`: tracciato a larghezza fissa per TeamSystem Studio; codici conto configurabili in settings.
- `ZucchettiFormatter`, `DatagammaFormatter`: stub — lanciano `\RuntimeException`. **TODO**: implementare dalle specifiche ufficiali dei rispettivi vendor.
- `ConservazioneService`: stub — lancia eccezione se non abilitata. **TODO**: integrare con conservatore accreditato AgID prima di abilitare.
- Settings aggiunti: `export_contabile_formato` (csv_generico), `export_contabile_codice_conto_*` (placeholder — da verificare col commercialista), `conservazione_sostitutiva_abilitata` (0).
- `ExportSdiBatch`: genera ZIP con XML FatturaPA dei documenti selezionati + `indice.csv`; usa `FatturaPAService::genera()` già esistente.
- `RiepilogoCommercialista`: calcola i totali del periodo + chiama il formatter configurato; PDF via dompdf con timbro "NON HA VALORE FISCALE".
- Il formato export per il riepilogo commercialista si legge da `settings.export_contabile_formato` (cambiabile in Impostazioni senza deploy).
- Test: `ContabilitaStep14Test` — observer prima nota, movimenti manuali, CSV generico, TeamSystem, stub exceptions, route access.

## Struttura chiave

```
app/
├── Actions/
│   ├── Commessa/           # AggiornaStatoAction, GeneraNumeroProgressivoAction
│   └── Lavorazione/        # AvviaLavorazioneAction, FermaLavorazioneAction
├── Enums/                  # StatoCommessa, TipoCommessa, TipoCliente, TipoVeicolo, Alimentazione,
│                           # TipoRiga, TipoPonte, StatoAppuntamento
├── Http/Controllers/Api/   # AppuntamentiController, RisorseAgendaController (endpoint JSON FullCalendar)
├── Livewire/
│   ├── Agenda/             # CalendarioAppuntamenti (FullCalendar + modal CRUD)
│   ├── Clienti/            # ListaClienti, DettaglioCliente
│   ├── Commesse/           # ListaCommesse, FormCommessa, DettaglioCommessa, GestioneRighe, GestioneAllegati
│   ├── Dashboard/          # StatoOfficina (polling 30s, contenuto per ruolo)
│   ├── Impostazioni/       # GestionePonti (CRUD + drag&drop)
│   ├── Magazzino/          # ListaArticoli, DettaglioArticolo, ListaFornitori, GestioneCategorie, ReportMagazzino
│   ├── Marcatempo/         # BoardMeccanico (tablet), GestioneLavorazioni (tab commessa)
│   └── Veicoli/            # ListaVeicoli, DettaglioVeicolo
├── Actions/
│   └── Magazzino/          # ScaricoCommessaAction, CaricoManualeAction, RettificaInventarioAction
├── Enums/                  # ... + UnitaMisura, TipoMovimento
├── Models/                 # Cliente, Veicolo, Commessa, CommessaRiga, Allegato, CommessaLog,
│                           # Setting, Ponte, Appuntamento, Lavorazione,
│                           # Articolo, CategoriaArticolo, Fornitore, MovimentoMagazzino,
│                           # TariffaManodopera, PacchettoServizio, PacchettoRiga
├── Observers/              # CommessaObserver (scarico automatico al completamento)
├── Policies/               # ClientePolicy, VeicoloPolicy, CommessaPolicy,
│                           # PontePolicy, AppuntamentoPolicy, LavorazionePolicy,
│                           # ArticoloPolicy, FornitorePolicy
│   └── Fatturazione/       # GeneraFatturaAction, EmettereNotaCreditoAction
├── Enums/                  # ... + TipoDocumento, StatoDocumento, MetodoPagamento, TipoRegistroIva
├── Http/Controllers/       # ... + FatturazioneController (download XML/ZIP/PDF)
├── Livewire/
│   └── Fatturazione/       # DettaglioDocumento, ListaDocumenti, Scadenziario, RegistroIva
├── Models/                 # ... + Documento, DocumentoRiga, Pagamento, RegistroIva, SdiLog
├── Observers/              # ... + DocumentoObserver (aggiorna registro_iva)
├── Policies/               # ... + DocumentoPolicy
└── Services/               # PdfService, MarginalitaService, NumerazioneService, FatturaPAService

resources/views/
├── layouts/
│   ├── app.blade.php       # Layout AdminLTE (usa @vite, @PwaHead, @RegisterServiceWorkerScript)
│   ├── tablet.blade.php    # Layout tablet ottimizzato (18px, btn 56px, clock Alpine, jsQR CDN)
│   └── marcatempo.blade.php # Alias → <x-tablet-layout subtitle="Marcatempo">
├── livewire/               # Template Blade per i componenti Livewire
└── pdf/                    # Template Blade per dompdf (scheda, preventivo, fattura-cortesia, checklist)
```

## Route Step 2

```
GET  /agenda                    → agenda.index         (admin, accettatore)
GET  /officina/marcatempo       → marcatempo           (admin, meccanico)
GET  /api/appuntamenti          → api.appuntamenti     (auth, JSON FullCalendar)
GET  /api/risorse-agenda        → api.risorse-agenda   (auth, JSON risorse)
GET  /impostazioni/ponti        → impostazioni.ponti   (admin)
```

## Route Step 3

```
GET  /magazzino/articoli        → magazzino.articoli       (admin, accettatore, cassa)
GET  /magazzino/articoli/{id}   → magazzino.articoli.show  (admin, accettatore, cassa)
GET  /magazzino/fornitori       → magazzino.fornitori      (admin, accettatore)
GET  /magazzino/movimenti       → magazzino.movimenti      (admin)
GET  /magazzino/report          → magazzino.report         (admin, cassa)
GET  /magazzino/categorie       → magazzino.categorie      (admin)
```

## Route Step 4

```
GET  /fatturazione/documenti             → fatturazione.documenti          (admin, cassa)
GET  /fatturazione/documenti/{id}        → fatturazione.documenti.show     (admin, cassa)
GET  /fatturazione/scadenziario          → fatturazione.scadenziario       (admin, cassa)
GET  /fatturazione/registro-iva          → fatturazione.registro-iva       (admin, cassa)
GET  /fatturazione/documenti/{id}/xml    → fatturazione.documenti.xml      download XML FatturaPA
GET  /fatturazione/documenti/{id}/zip    → fatturazione.documenti.zip      download ZIP SdI
GET  /fatturazione/documenti/{id}/pdf    → fatturazione.documenti.pdf      download PDF cortesia
```

## Route Step 5

```
GET  /commesse/{id}/qr-code              → commesse.qr-code              SVG QR code (admin, accettatore)
GET  /officina/checklist/{commessa}/{template} → officina.checklist      tablet compilazione checklist (admin, meccanico)
GET  /allegati/checklist/{filename}      → checklist.foto                foto checklist autenticata
GET  /impostazioni/checklist             → impostazioni.checklist        CRUD template (admin)
```

## Route Step 11

```
GET  /impostazioni/tariffe               → impostazioni.tariffe   (admin) — tariffario manodopera
GET  /impostazioni/pacchetti             → impostazioni.pacchetti (admin) — pacchetti servizio
GET  /analytics/pacchetti               → analytics.pacchetti    (admin) — report utilizzo pacchetti e tariffe fuori listino
```

## Note architetturali Step 11

- `TariffaManodopera`: listino tariffe manodopera; campo `minuti_standard` → `ore_standard` accessor.
- `PacchettoServizio::isCompatibile(Commessa)`: filtra per tipo_commessa, tipo_veicolo, alimentazione veicolo.
- `PacchettoServizio::calcolaTotale()`: somma le righe (escluse note) con IVA.
- `ApplicaPacchettoAction::execute()`: in transaction crea righe commessa dal pacchetto, imposta `pacchetto_servizio_id` su ogni riga, incrementa `utilizzi`.
- `tariffa_manodopera_id` e `pacchetto_servizio_id` aggiunti a `commessa_righe` come FK nullable — traccia l'origine delle righe.
- Typeahead tariffe in `GestioneRighe`: selezione pre-popola descrizione, quantita (minuti/60), prezzo_unitario.
- Modal "Applica pacchetto" a 2 passi: passo 1 ricerca con filtro compatibilità, passo 2 preview righe modificabili prima della conferma.
- Seeder: 31 tariffe comuni in `TariffeManodoperaSeeder`, 3 pacchetti di esempio in `PacchettiServizioSeeder`.
- Import CSV tariffe: delimitatore `;`, colonne `codice;descrizione;categoria;minuti_standard;prezzo_listino;iva_percentuale;tipo_veicolo`. Usa `updateOrCreate` per idempotenza.
- Export CSV: BOM UTF-8 (`\xEF\xBB\xBF`) per compatibilità Excel italiano.
- Analytics pacchetti: top 10 per utilizzi + tariffe fuori listino (scostamento > 15% da prezzo listino).

## Route Step 13

```
GET  /cortesia                               → cortesia.index              (admin, accettatore) — calendario disponibilità
GET  /cortesia/flotta                        → cortesia.flotta             (admin) — gestione flotta
GET  /cortesia/report                        → cortesia.report             (admin, accettatore, cassa)
GET  /cortesia/consegna                      → cortesia.consegna           (admin, accettatore) — flusso consegna
GET  /cortesia/consegna/commessa/{id}        → cortesia.consegna.commessa  (admin, accettatore) — da commessa
GET  /cortesia/prestiti/{id}/rientro         → cortesia.rientro            (admin, accettatore) — flusso rientro
GET  /cortesia/prestiti/{id}/contratto       → cortesia.contratto          (admin, accettatore) — PDF contratto
GET  /api/cortesia/disponibilita             → api.cortesia.disponibilita  (auth) — JSON FullCalendar risorse+eventi
GET  /impostazioni/lookup-targa-test         → impostazioni.lookup-test    (admin) — test connessione API targa
```

## Note architetturali Step 13

### Veicoli di cortesia (Parte A)
- `VeicoloCortesia`: flotta auto/moto/furgone; `isDisponibile(dal, al)` verifica sovrapposizioni sui prestiti attivi.
- `PrestitoCortesia`: registra tutto il ciclo consegna→rientro; immutabile nel tracciamento (mai eliminare, solo aggiornare stato).
- `km_percorsi` e `delta_carburante` sono accessor calcolati — non colonne DB.
- Flusso consegna (4 step tablet): selezione periodo → scelta veicolo → firma → conferma. Il canvas firma usa canvas nativo (non signature_pad) per semplicità.
- Flusso rientro: validazione `km_rientro >= km_consegna` server-side e client-side; avviso carburante se delta < -10%.
- PDF contratto comodato in `resources/views/pdf/contratto-cortesia.blade.php`: **le clausole legali sono testo fisso nel template Blade** (art. 1803 c.c. e ss.) — modificarle richiede accesso al codice, non è configurabile da settings.
- `PdfService::contrattoCortesia()` scarica il PDF; route `cortesia.contratto`.
- Badge menu "Cortesia" = count prestiti in ritardo (`inRitardo` scope: stato `in_corso` + `data_rientro_prevista < oggi`).
- Endpoint FullCalendar `/api/cortesia/disponibilita` restituisce `{risorse: [...], eventi: [...]}` — formato diverso da `/api/appuntamenti` (che restituisce solo l'array eventi).
- `patente_numero` e `patente_scadenza` aggiunti a `clienti` — esclusi dall'audit log e dai CSV export (dati sensibili).
- Widget "Veicolo di cortesia" in `DettaglioCommessa`: query diretta `@php` nel Blade (accettabile, N+1 solo se molte commesse aperte).
- Pulsante "Cortesia" nell'header di `DettaglioCommessa` → `/cortesia/consegna/commessa/{id}`.

### Lookup Targa (Parte B)
- `LookupTargaService`: singleton-like; legge settings a runtime; restituisce `null` senza errori se disabilitato.
- Provider: `MockProvider` (deterministico, no API key), `InfoTargaProvider` (Bearer token), `OpenApiProvider` (x-api-key header).
- Cache 30 giorni per targa: `Cache::remember("targa_{$targa}", ...)`. Invalidazione manuale con `Cache::forget("targa_XX123YY")`.
- **Non loggare mai la API key** — i provider loggano solo targa + status code HTTP in caso di errore.
- Settings aggiunti: `lookup_targa_abilitato` (0), `lookup_targa_provider` (mock), `lookup_targa_api_key`, `lookup_targa_timeout_ms` (3000), `lookup_targa_auto_search` (0).
- Pulsante "Cerca dati" nel form veicolo: visibile SOLO se `lookup_targa_abilitato = true`; opzionalmente auto al blur.
- Test connessione: `GET /impostazioni/lookup-targa-test` → risposta JSON raw in `<pre>` (solo admin).

## Route Step 12

```
GET  /deposito                            → deposito.index              (admin, accettatore) — situazione + accettazione
GET  /deposito/commessa/{id}              → deposito.commessa           (admin, accettatore) — accettazione da commessa
GET  /deposito/cambio-stagionale          → deposito.cambio-stagionale  (admin, accettatore) — cambio massivo
GET  /deposito/report                     → deposito.report             (admin, accettatore, cassa)
GET  /deposito/etichetta/{pneumatico}     → deposito.etichetta          (admin, accettatore, meccanico) — PDF A6
POST /deposito/etichette-multiple         → deposito.etichette-multiple (admin, accettatore, meccanico) — PDF multiplo
GET  /deposito/qr/{codice}                → deposito.qr                 (admin, accettatore, meccanico) — redirect da QR scan
```

## Note architetturali Step 12

- `Pneumatico`: anagrafica set gomme per veicolo; `codiceEtichetta()` → formato `{prefisso}-{anno}-{id:05d}`.
- `DepositoPneumatico`: movimenti immutabili (come `MovimentoMagazzino`); traccia azione, ubicazione fisica, usura%.
- `StagionePneumatico::opposta()`: helper per il cambio stagionale massivo (se monto estivi → cerco invernali in deposito).
- `EtichettaDepositoService::genera()`: PDF A6 o adesivo (100×50mm) con QR code che encode l'URL `/deposito/qr/{codice}`.
- `EtichettaDepositoService::generaMultiple()`: PDF A4 multi-etichetta; 4 etichette per pagina.
- QR scan tablet: il QR dell'etichetta deposito contiene l'URL diretto — funziona con lo stesso scanner Alpine.js già esistente in `BoardMeccanico`.
- `InviaNotificaCambioStagionale` job: idempotente via `NotificaLog.pneumatico_id` (colonna aggiunta in migration 120004).
- `CambioStagionaleMassivo::creaAppuntamentiInBlocco()`: distribuisce gli appuntamenti nei 5 giorni lavorativi della settimana selezionata bilanciando il carico (`COUNT` appuntamenti per giorno).
- Badge menu "Deposito Gomme": count set `in_deposito` da più di 180 giorni — aggiunto in `MenuBadgesController` (`deposito_da_ritirare`).
- Mappa scaffali `ReportDeposito`: SVG inline generato PHP con regex `([A-Z])(\d+)` sulle ubicazioni; celle rosse = occupato, verdi = libero.
- Tab "Pneumatici" nel `DettaglioVeicolo`: badge blu con count set in deposito; carica `GestionePneumatici` Livewire.
- Pulsante "Pneumatici" nel header di `DettaglioCommessa` → `/deposito/commessa/{id}`.
- Settings aggiunti: `deposito_pneumatici_abilitato`, `etichetta_deposito_prefisso` (DEP), `etichetta_deposito_formato` (A6), `notifica_cambio_stagionale_mese_estivo` (4), `notifica_cambio_stagionale_mese_invernale` (10), `template_email_cambio_stagionale`.
- PFU (D.Lgs. 152/2006): nella tab smaltimenti del report è visibile l'elenco annuale con promemoria documentazione. Il sistema NON genera automaticamente documenti PFU — è responsabilità dell'officina.
- `NotificaGenerica` Mailable: mailable senza modello specifico, usa lo stesso template `emails.notifica-commessa`.

## Route Step 8

```
GET  /dashboard                          → analytics.dashboard (tutti)       [alias: /analytics/dashboard]
GET  /analytics/meccanici                → analytics.meccanici (admin)
GET  /analytics/marginalita              → analytics.marginalita (admin, cassa)
GET  /analytics/commesse                 → analytics.commesse (admin, accettatore, cassa)
GET  /api/menu-badges                    → api.menu-badges (auth, JSON badge menu)
```

## Route Step 7

```
GET  /impostazioni/compagnie                → impostazioni.compagnie            (admin)
GET  /commesse/{id}/pdf/carrozzeria         → pdf.carrozzeria                   download PDF scheda carrozzeria
GET  /commesse/{id}/foto-danni/zip          → carrozzeria.foto.zip              download ZIP foto danni
GET  /allegati/foto-danni/{filename}        → allegati.foto-danni               foto danni autenticata
GET  /allegati/perizie/{filename}           → allegati.perizia                  PDF perizia autenticato
```

## Note architetturali Step 8

- `KpiService` (in `Services/Analytics/`): calcoli KPI con `Cache::remember` TTL 10 min. Usa `whereDate` (non `whereBetween`) per compatibilità SQLite in test (SQLite salva le date come `Y-m-d H:i:s`).
- `MeccaniciService`: produttività per-meccanico (ore lavorate vs fatturate, efficienza, margine).
- `Analytics\MarginalitaService`: unificata con `Services\MarginalitaService` Step 2 — quella originale ora estende la nuova come alias backward-compat. Include `calcolaPerCategoria`, `calcolaPerArticoli`, `calcolaTrendMensile`.
- `CsvExportService`: export CSV con BOM UTF-8 (`\xEF\xBB\xBF`) per compatibilità Excel italiano.
- Funzioni SQL `YEAR()` / `MONTH()` non supportate da SQLite — il metodo `datePartExpressions()` detecta il driver e usa `strftime` per SQLite, `YEAR/MONTH` per MySQL/MariaDB.
- Badge menu aggiornati ogni 2 minuti via `fetch('/api/menu-badges')` in vanilla JS inline nel layout. Cache TTL 120s per-utente.
- Chart.js 2.9.4 importato in `app.js` ed esposto come `window.Chart`. Grafici inizializzati in `x-init` di Alpine; aggiornati via eventi Livewire dispatch (`@event-name.window`).
- Export PDF meccanici: il grafico Chart.js viene catturato come base64 PNG via `chart.toBase64Image()` in JS, passato a Livewire via `@this.set()`, poi inviato a dompdf.
- `ReportMagazzino` esteso con tab Rotazione (indice = scaricato_anno / giacenza_media; classi: alta/media/bassa/ferma) e Valore per categoria (grafico Chart.js).
- Test analytics in `tests/Feature/Analytics/`: aggiungere `Cache::flush()` in `setUp()` per evitare collisioni di cache tra test dello stesso periodo.

## Note architetturali Step 7

- Modulo Carrozzeria: visibile solo per commesse di tipo `carrozzeria`.
- `CompagniaAssicurativa` + `Sinistro` + `Perizia`: gestione sinistri assicurativi.
- `DannoVeicolo`: catalogo danni per zona (enum `ZonaDanno`); SVG interattivo con Alpine.js nella vista.
- `FotoDanno`: foto caricate in `storage/app/allegati/foto-danni/`, servite tramite route autenticata.
- `StatoCarrozzeria`: workflow parallelo allo stato commessa; la commessa passa a `completata` solo con `stato_carrozzeria = consegna`.
- `GeneraFatturaDoppiaAction`: crea due fatture (cliente + assicurazione) collegate via `documento_correlato_id`; la seconda fattura usa il progressivo dopo aver inserito la prima (ordine sequenziale).
- Download ZIP foto usa `ZipArchive` nativo PHP in `CarrozzeriaController`.
- `commesse.sinistro_id` è senza FK constraint (FK inversa: `sinistri.commessa_id`) per evitare riferimento circolare.
- PDF scheda carrozzeria usa closure `$zonaClass` (non funzione named) per evitare redeclarazione in Blade compilato.
- Perizia PDF allegato salvato in `storage/app/allegati/perizie/`.

## Route Step 6

```
GET  /scadenziario                → scadenziario.index         (admin, accettatore)
GET  /impostazioni/email          → impostazioni.email         (admin)
GET  /cliente/{token}             → cliente.portale            (pubblico, URL firmato 30 gg)
```

## Note architetturali Step 6

- `MailConfigService::applica()` sovrascrive la config SMTP a runtime da `settings` DB; invocata in `AppServiceProvider::boot()` e prima di ogni invio manuale.
- `setting(string $key)` è un helper globale (`app/helpers.php`, autoloadato via composer.json `files`) che legge da `Setting::get()` con cache Laravel.
- `EmailTemplateService::compila()` splitta la prima riga `Oggetto:` dall'oggetto email e sostituisce `{{VARIABILE}}` nel corpo.
- `AggiornaStatoAction` accoda l'email in coda (`Mail::to()->queue()`) solo se: notifiche abilitate, cliente ha email, nessuna notifica accodata negli ultimi 5 minuti per la stessa commessa.
- `NotificaLog` registra ogni tentativo di notifica (email); campo `email_smtp_password` NON in `$fillable`, mai esposto in log o JS.
- `InviaRichiamiScadenza` Job è idempotente: prima di inviare verifica `NotificaLog` delle ultime 24h per la stessa scadenza. Usa filtro PHP (non SQL vendor-specific) per la finestra `notifica_giorni_prima`.
- `CommessaObserver::updated()` su stato `consegnata` chiama `CreaScadenzeAutomaticheAction::suggerisci()` e dispatcha l'evento Livewire `scadenze-suggerite` che il `DettaglioCommessa` gestisce con `#[On]`.
- Portale cliente `/cliente/{token}` usa `URL::signedRoute` con `encrypt($cliente->id)` come token; validato con `$request->hasValidSignature()`. Link non richiede login.
- Scheduler: `routes/console.php` con `Schedule::job(new InviaRichiamiScadenza)->dailyAt('08:00')`. Docker: `entrypoint.sh` avvia `crond` e il queue worker in background, poi `php-fpm` in foreground.

## PWA — note sviluppo

- Service Worker attivo su HTTPS (Caddy in Docker) o su `localhost` (Chrome/Chromium permette SW su localhost)
- Manifest configurato in `config/pwa.php`; per rigenerare `manifest.json` usare `php artisan erag:update-manifest`
- Icons PWA in `public/images/icons/` (generate con PHP GD; sostituire con PNG grafici per produzione)
- jsQR CDN caricato **solo** nel layout tablet (`layouts/tablet.blade.php`), non nel bundle Vite
- Service Worker `public/sw.js` NON è generato dal pacchetto — è custom con 3 strategie:
  - Cache-first: asset statici CSS/JS/immagini
  - Network-first: Livewire (`/livewire/`) e API (`/api/`)
  - Stale-while-revalidate: pagine tablet (`/officina/marcatempo`, `/officina/checklist/`)
- Pagina offline: `public/offline.html` (statica, pre-cacciata al install)
- `public/sw.js` registra scope `/` — coprire tutta l'app

## Database

- **DB locale**: `officina_hub` su MySQL/MariaDB (Laragon: root senza password)
- **DB test**: SQLite in-memory (phpunit.xml)
- Tutti i modelli con nomi italiani hanno `$table` esplicita

## Credenziali default (seeder)

- **Admin**: `admin@officinahub.local` / `password`
- Ruoli disponibili: `admin`, `accettatore`, `meccanico`, `cassa`

## Workflow stati commessa

```
bozza → accettata → in_lavorazione ⇄ sospesa
                   → completata → consegnata → fatturata
```

Ogni transizione è loggata in `commessa_log`. Accettazione e consegna richiedono firma SVG.

## Asset statici

AdminLTE 3 è copiato in `public/vendor/adminlte/` da `node_modules/admin-lte`.
Per aggiornare gli asset AdminLTE:
```bash
cp -r node_modules/admin-lte/dist public/vendor/adminlte/dist
cp -r node_modules/admin-lte/plugins public/vendor/adminlte/plugins
```

## Note architetturali Step 5

- `TabletLayout` è un Blade component (`app/View/Components/TabletLayout.php`) che renderizza `layouts/tablet.blade.php`.
- `<x-marcatempo-layout>` è un alias che delega a `<x-tablet-layout subtitle="Marcatempo">` via `layouts/marcatempo.blade.php`.
- jsQR è caricato da CDN solo nel layout tablet — non va nel bundle Vite (evita peso per le view desktop).
- Scanner QR usa `getUserMedia` con `facingMode: 'environment'` (fotocamera posteriore tablet); richiede HTTPS o localhost.
- Checklist: unica constraint `UNIQUE(checklist_template_id, commessa_id)` in `checklist_compilate` — una compilazione per coppia template+commessa.
- Risposte progressive: ogni `wire:change` chiama `salvaRisposta()` — nessun batch submit finale; `completata_at` si imposta solo su "Finalizza".
- Foto checklist: salvate in `storage/app/allegati/checklist/` (disco `local`), servite tramite route autenticata `/allegati/checklist/{filename}`.
- Il badge checklist nel tab di `DettaglioCommessa` esegue query inline `@php` nel Blade — accettabile, ma se diventa N+1 estrarre nel component Livewire.
- Il QR code SVG è generato on-the-fly da `simplesoftwareio/simple-qrcode` (backend); nessun file viene salvato su disco.
- Icons PWA: PNG generati con GD in `public/images/icons/`; sostituire con file grafici professionali per produzione.

## Note architetturali Step 4

- `Documento` è immutabile una volta in stato `emessa` — per correggere si emette una nota di credito.
- `NumerazioneService::prossimo()` usa `lockForUpdate()` in transaction per garantire unicità sotto carico.
- `FatturaPAService::genera()` renderizza il template Blade `fatturapa/fattura.blade.php` e ritorna stringa XML.
- `FatturaPAService::valida()` richiede `storage/app/fatturapa/xsd/Schema_VFPR12.xsd` (da scaricare da AdE).
- `DocumentoObserver` popola la tabella `registro_iva` al passaggio di stato a `emessa`.
- Lo ZIP SdI è generato in `storage/app/fatturapa/temp/` e può essere eliminato dopo il download.
- Le ricevute SdI caricate manualmente sono salvate in `storage/app/fatturapa/ricevute/`.
- Il PDF di cortesia porta la dicitura "Documento privo di valore fiscale" obbligatoria.

## Note architetturali Step 3

- `MovimentoMagazzino` è immutabile: `$timestamps = false`, nessun update/delete dopo la creazione.
- `giacenza_attuale` su `Articolo` è denormalizzata: aggiornata da ogni Action con DB transaction + `lockForUpdate()`.
- `CommessaObserver::updated()` invoca `ScaricoCommessaAction` solo al passaggio a `StatoCommessa::Completata`.
- Lo scarico in negativo non blocca il completamento: crea il movimento con nota "SCARICO IN NEGATIVO" e logga un warning.
- Il typeahead articoli in `GestioneRighe` usa `wire:model.live.debounce.300ms` e popola automaticamente prezzo e IVA.
- Il badge rosso sul menu Magazzino esegue una query diretta nel layout (non caching): accettabile per poche migliaia di articoli.
- `ReportMagazzino::esportaCsv()` usa `response()->streamDownload()` — nessun pacchetto aggiuntivo.

## Route Step 10

```
GET  /officina/dvi/{commessa}/nuova          → dvi.nuova              (admin, meccanico, accettatore) — layout tablet
GET  /officina/dvi/{ispezione}/anteprima     → dvi.anteprima          (admin, meccanico, accettatore)
GET  /dvi/media/{media}                      → dvi.media              (auth) — serve foto/video staff
GET  /dvi/media/{media}/thumb                → dvi.media.thumb        (auth) — thumbnail video
POST /api/dvi/upload-chunk                   → api.dvi.upload-chunk   (auth) — chunked video upload
GET  /dvi/{token}                            → dvi.portale            (pubblico) — portale cliente
POST /dvi/{token}/risposte                   → dvi.salva-risposte     (pubblico) — salva scelte cliente
GET  /dvi/{token}/conferma                   → dvi.conferma           (pubblico) — pagina post-risposta
GET  /dvi/{token}/media/{media}              → dvi.media.cliente      (pubblico, token-based)
GET  /impostazioni/dvi-categorie             → impostazioni.dvi-categorie (admin)
```

## Note architetturali Step 10

### DVI (Digital Vehicle Inspection)

- `DviIspezione` → `DviVoce` → `DviMedia`: struttura dati separata dalla checklist (interna) — la DVI è esterna (meccanici → cliente).
- Foto salvate in `storage/app/dvi/foto/{anno}/{mese}/`, thumbnail con Intervention Image (GD).
- Video: upload chunked in vanilla JS (Alpine + XMLHttpRequest), endpoint `POST /api/dvi/upload-chunk`.
  Chunk salvati in `storage/app/tmp/dvi/{upload_id}/chunk_N`, riassemblati all'ultimo chunk.
  File finale in `storage/app/dvi/video/{anno}/{mese}/`.
- Thumbnail video: estratta con `ffmpeg -ss 00:00:01 -vframes 1`; se ffmpeg non disponibile, placeholder SVG con icona play.
- Dockerfile aggiornato con `apk add --no-cache ffmpeg` per il container Docker.
- Il portale cliente `/dvi/{token}` è **HTML puro** senza AdminLTE/Livewire/Alpine: massima compatibilità su connessioni lente.
- Token DVI: opaco (64 char random), scadenza in DB (`link_scade_at`). Non usa `URL::signedRoute`.
- Dopo la risposta del cliente: `stato` aggiornato (approvata/parzialmente_approvata/rifiutata), `dvi_approvazione_importo` salvato su `commesse`.
- "Converti in preventivo": crea righe `commessa_righe` dalle voci DVI approvate. Tipo riga: `Manodopera`.
- Jobs: `InviaDviCliente` (email al cliente con link), `NotificaDviRisposta` (email officina al completamento).
- Template email DVI: `template_email_dvi` in `settings` — variabili: `{{TARGA}}`, `{{NOME_CLIENTE}}`, `{{LINK_DVI}}`, `{{DATA_SCADENZA}}`, `{{NOME_OFFICINA}}`, `{{TELEFONO_OFFICINA}}`.
- Widget dashboard (admin/accettatore): DVI in attesa di risposta + count risposte ricevute oggi.
- Tab "DVI" nel dettaglio commessa con badge giallo se in attesa, grigio se già risposto.
- `DviCategoria`: categorie configurabili via `/impostazioni/dvi-categorie`; 10 predefinite seeded.
- `intervention/image` v4 richiede `ImageManager(new Driver())` — non usare la vecchia API `Image::make()`.

## Note architetturali Step 9

### Sicurezza

- `SecurityHeaders` middleware registrato globalmente via `bootstrap/app.php` (`$middleware->append`).
  Aggiunge X-Frame-Options, X-Content-Type-Options, CSP, ecc. a tutte le risposte.
- **CSP usa `'unsafe-inline'`** intenzionalmente: necessario per Livewire (wire:click inline) e Alpine.js
  (x-data inline). Non è un bug — è documentato qui.
- Rate limiting: `throttle:login` (5/min per IP) su POST `/login`; `throttle:api-internal`
  (120/min per utente/IP) sugli endpoint `/api/*`. Definiti in `AppServiceProvider::boot()`.
- Validazione upload: tutti i file usano sia `mimes:` (estensione) sia `mimetypes:` (MIME reale),
  più nome file randomizzato prima del salvataggio.
- Sessione: lifetime 480 min (8 ore, turno di lavoro); `secure=true` di default (solo HTTPS).
  In sviluppo HTTP impostare `SESSION_SECURE_COOKIE=false` nel `.env` locale.
- Policy `AllegatoPolicy` e `ScadenzaPolicy` aggiunte in `AppServiceProvider` (totale 13 policy).

### Backup (spatie/laravel-backup)

- Config in `config/backup.php`: include `storage/app/allegati` e `storage/app/fatturapa`.
- Backup locale in `storage/app/{APP_NAME}/` (disco `local`).
- Scheduler: `backup:run` alle 02:00, `backup:clean` alle 02:30, `backup:monitor` alle 03:00.
- Notifiche email in caso di fallimento: destinatario `BACKUP_NOTIFY_EMAIL` (env).
- ZIP cifrabile via `BACKUP_PASSWORD` (env, vuoto = nessuna cifratura).

### Audit log (spatie/laravel-activitylog v4)

- Trait `LogsActivity` aggiunto a `Commessa`, `Documento`, `MovimentoMagazzino`, `User`.
- Ogni model logga solo i campi rilevanti (es. Commessa: `stato`, `data_consegna`, `km_ingresso`).
- Vista `/audit-log` (solo `admin`): Livewire component `Admin\AuditLog` con filtri e export CSV.
- Il log non è eliminabile dall'interfaccia (solo l'admin DB può farlo).

### Cache KPI

- `KpiService::invalidaCache()` svuota le chiavi statiche (sparkline, grafico fatturato).
- Chiamato automaticamente da `CommessaObserver` e `DocumentoObserver` al cambio di stato.
- Le cache per-periodo (fatturato range date) scadono per TTL (10 min): per invalidazione completa
  servirebbero cache tag (`Cache::tags()`), che richiedono Redis/Memcached.

### SDI diretto (disabilitato)

- `config/sdi.php` contiene tutti i parametri del canale SdI.
- `SdiService` e `InviaDocumentoSdi` job sono skeleton: `throw RuntimeException` se `SDI_ABILITATO=false`.
- Il pulsante "Invia al SdI" nel dettaglio documento è visibile **solo** se `config('sdi.abilitato')=true`.
- Procedura di attivazione completa in `docs/sdi-diretto.md`.

### Relazioni N+1 da eager-load sempre

```php
Commessa::with(['cliente', 'veicolo', 'righe', 'lavorazioni.user', 'allegati'])
Documento::with(['cliente', 'righe', 'pagamenti'])
Appuntamento::with(['commessa', 'cliente', 'veicolo', 'ponte', 'user'])
```

### Laravel Debugbar (dev only)

- Installato come dipendenza `--dev`. Non attivo in produzione (`APP_DEBUG=false`).
- Usare per individuare query N+1 (`Queries` panel).

---

## Note architetturali

- I Livewire component non contengono logica di business: delegano ad Actions/Services
- Policies registrate in AppServiceProvider con Gate::before per admin bypass
- PDF generati da barryvdh/laravel-dompdf con template Blade in `resources/views/pdf/`
- Firma digitale via `signature_pad` (npm), salvata come SVG nel DB
- Il numero progressivo commessa usa `lockForUpdate()` per evitare race condition
- FullCalendar v6 Premium (CC licence) inizializzato in `Alpine.js init()`, non si re-inizializza
  ad ogni render Livewire; comunicazione via `@this.metodo()` e evento `calendar-refresh`
- Endpoint API agenda in `routes/web.php` (prefisso `api/`), autenticati via sessione Laravel
- Timer marcatempo lato client in `setInterval` Alpine, timestamp di inizio salvato in JS (non Livewire)
- `MarginalitaService::calcola()` usa `costo_orario` dell'utente o `setting.costo_orario_default`
