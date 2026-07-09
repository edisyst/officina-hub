# Moduli funzionali — Officina Hub

Panoramica di tutte le aree applicative, con route principali e note operative.

---

## Suggerimenti contestuali da storico veicolo (Step 33)

Pannello "Da valutare" nell'OdL: ricorda al banco i lavori declinati in passato, le scadenze imminenti e le manutenzioni per chilometraggio. Motore delle "opportunità mancate" basato interamente su dati interni (zero API esterne).

**Route:** `GET /impostazioni/manutenzioni` (admin) — CRUD regole manutenzione ricorrente.  
Il pannello è un Livewire component inline nel dettaglio OdL, senza route propria.

### Righe declined
- Su ogni riga manodopera dell'OdL appare il pulsante **🚫 ban**: toggle `outcome` tra `completed` e `declined`.
- Righe `declined` sono visibili in grigio con badge rosso ma **escluse da totali, PDF e margini**.
- Observer su `CommessaRiga` crea automaticamente una `VehicleRecommendation(source=declined)` quando outcome→declined; la rimuove (soft-delete) se la riga torna completed prima della chiusura.

### Engine (RecommendationEngineService)
Tre sorgenti, tutte idempotenti (no duplicati pending):
1. **declined** — gestita dall'Observer, non dall'engine.
2. **deadline** — scadenze del veicolo (`scadenze`) entro 60 giorni (configurabile via `recommendations.deadline_horizon_days`). Feature-check: se la tabella scadenze non esiste, sorgente saltata.
3. **mileage** — per ogni `MaintenanceRule` attiva: cerca ultima esecuzione (FK su recommendation accettata → fuzzy case-insensitive su descrizione righe storico); se km_attuali − km_ultima ≥ every_km **oppure** mesi trascorsi ≥ every_months (o nessuna storia), genera recommendation.

Trigger: apertura pannello (lazy via `mount()`) + aggiornamento `km_attuali` veicolo (VeicoloObserver).

### Pannello nell'OdL
- Badge contatore "Da valutare (N)" visibile solo se N > 0.
- Per ogni suggestion: badge sorgente colorato, titolo, data/km scadenza, OdL di origine.
- **Aggiungi** → crea CommessaRiga(manodopera, outcome=completed) + segna accepted.
- **Ignora** → modale con motivo opzionale + segna dismissed.

### Regole manutenzione
CRUD in `Impostazioni → Manutenzioni ricorrenti`. Campi: nome, ogni_km, ogni_mesi (almeno uno obbligatorio), toggle attivo.  
Seeder: tagliando olio (15.000 km / 12 mesi), filtro aria (30.000 km / 24 mesi), cinghia distribuzione (120.000 km), liquido freni (24 mesi).

---

## Stato Veicolo — ricerca rapida da banco

Vista pensata per rispondere al telefono in tre secondi: digita targa, cognome o numero di telefono e vedi subito dov'è la macchina, cosa manca e quando è prevista la consegna.

**Route:** `GET /stato-veicolo` — shortcut globale **F2**, pulsante **`fa-phone`** in navbar e voce sidebar.

- Ricerca live (`debounce 200ms`, min 2 caratteri) su targa (prefix), cognome/nome/ragione_sociale, telefono. Max 5 risultati.
- Card grande per ogni veicolo: targa in font monospace, marca+modello, nome e telefono cliente.
- Stato OdL come badge colorato (coerente con board Step 25) + data consegna prevista (Step 28).
- **Semaforo ricambi**: 🟢 verde (tutti disponibili o già scaricati), 🟡 giallo (mancanti con elenco e stato ordine fornitore se Step 15 attivo), ⚫ grigio (nessun ricambio in OdL).
- Approvazione DVI (Step 10) e ultima comunicazione con estratto (Step 30): presenti solo se i moduli sono installati.
- Se nessun OdL attivo: mostra data ultima consegna storica.
- Auto-espansione card se match esatto per targa.
- Azioni: "Apri OdL" e "Annota chiamata" (se Step 30 presente).
- **Zero dati economici**: la scheda è pensata per essere visibile al banco.

---

## Accettazione veicolo (one-screen)

Punto d'ingresso quotidiano per l'accettatore: digita la targa e apre l'OdL senza mai cambiare pagina.

**Route:** `GET /acceptance`

- **Stadio 1 — Targa**: input autofocus con match esatto automatico; lista di scelta per match parziali; bottone "Crea nuovo veicolo" se nessun match.
- **Stadio 2A — Veicolo esistente**: card veicolo + card cliente + timeline ultimi 5 interventi + aggiornamento km (validazione monotona).
- **Stadio 2B — Nuovo veicolo**: form inline (targa precompilata, lookup targa opzionale) + ricerca cliente esistente o mini-form creazione inline.
- **Stadio 3 — OdL**: tipo intervento, richieste cliente, data prevista, selettore pacchetti (se attivi), riepilogo sticky.
- `CheckInVehicleAction`: transazione unica — rollback completo se qualcosa fallisce.
- "Apri OdL e stampa scheda": apre il PDF scheda di accettazione in nuova scheda dopo il redirect.

---

## Clienti & Veicoli

Anagrafica clienti (persone fisiche e giuridiche) con storico commesse e veicoli associati.  
Il veicolo può essere associato a più clienti (relazione N:M tramite `cliente_veicolo`).

**Route:** `GET /clienti`, `GET /clienti/{id}`, `GET /veicoli`, `GET /veicoli/{id}`

- Lookup targa opzionale (provider esterno configurabile) per compilazione automatica dati veicolo
- Tab "Pneumatici" nel dettaglio veicolo mostra i set in deposito
- Tab "DVI" nel dettaglio commessa con badge se in attesa di risposta

---

## Commesse

Cuore del gestionale. Ogni commessa segue il workflow:

```
bozza → accettata → in_lavorazione ⇄ sospesa
                  → completata → consegnata → fatturata
```

Ogni transizione è loggata in `commessa_log`. Accettazione e consegna richiedono **firma SVG** (signature_pad).

**Route:** `GET /commesse`, `GET /commesse/{id}`, `GET /commesse/{id}/qr-code`, `GET /commesse/stampa-massiva`

- Righe libere (manodopera, articoli, note) con typeahead su catalogo/tariffe
- Scarico automatico magazzino al passaggio a `completata` (CommessaObserver)
- Allegati con upload multiplo e anteprima
- Generazione PDF scheda e preventivo (dompdf)
- Widget veicolo di cortesia nel dettaglio
- Pulsante per avviare flusso deposito gomme dalla commessa
- **Selezione multipla** nella lista: checkbox per riga, seleziona pagina, "seleziona tutti i N risultati del filtro" (Gmail-style)
- **Bulk cambio stato** con modale e report per-record (successi / saltati con motivo)
- **Stampa massiva** HTML multi-pagina e **export CSV** della selezione

---

## Agenda

Calendario appuntamenti con FullCalendar v6, viste giorno / settimana / risorsa (ponti).  
Drag & drop per spostare gli appuntamenti; modal CRUD inline.

**Route:** `GET /agenda`  
**API FullCalendar:** `GET /api/appuntamenti`, `GET /api/risorse-agenda`

---

## Marcatempo (tablet)

Board tablet per i meccanici: avvio/stop lavorazioni con timer live, scanner QR per identificare la commessa.  
Richiede HTTPS (o localhost) per `getUserMedia` (fotocamera scanner QR).

**Route:** `GET /officina/marcatempo`

- Layout ottimizzato (font 18px, pulsanti 56px)
- Timer client-side (Alpine.js `setInterval`), timestamp di inizio salvato in JS
- jsQR caricato da CDN solo nel layout tablet

---

## Magazzino

Gestione catalogo articoli, fornitori, categorie e movimenti transazionali.

**Route:** `/magazzino/articoli`, `/magazzino/fornitori`, `/magazzino/movimenti`, `/magazzino/report`, `/magazzino/categorie`

- `giacenza_attuale` denormalizzata con `lockForUpdate()` in transaction
- Movimenti immutabili dopo la creazione
- Scarico automatico al completamento commessa
- Report con tab Rotazione (ABC), Valore per categoria, CSV export con BOM UTF-8
- Import/export CSV compatibile Excel italiano
- **Selezione multipla** nella lista articoli: bulk riordino (crea bozze ordini fornitore per fornitore preferenziale), bulk aggiornamento ubicazione, export CSV
- **Inline editing** direttamente nella cella: prezzo vendita, ubicazione, scorta minima — click attiva input, Enter salva, Esc annulla; ogni salvataggio loggato con old/new su activitylog

---

## Fatturazione

Emissione documenti fiscali: fattura, nota di credito, fattura PA (FPR12 AdE v1.9).

**Route:** `/fatturazione/documenti`, `/fatturazione/scadenziario`, `/fatturazione/registro-iva`  
**Download:** `/fatturazione/documenti/{id}/xml`, `.../zip`, `.../pdf`

- Numerazione sequenziale con `lockForUpdate()` per unicità sotto carico
- Documento immutabile dopo `emessa`; correzioni via nota di credito
- `DocumentoObserver` aggiorna `registro_iva` al cambio di stato
- ZIP SdI generato in `storage/app/fatturapa/temp/`
- PDF cortesia con dicitura obbligatoria "Documento privo di valore fiscale"
- Canale SdI diretto: presente ma disabilitato (`SDI_ABILITATO=false`); vedi [`docs/sdi-diretto.md`](sdi-diretto.md)

---

## Checklist

Template configurabili da admin; compilazione tablet da meccanici.

**Route:** `GET /impostazioni/checklist`, `GET /officina/checklist/{commessa}/{template}`

- Tipi voce: `si_no`, `numerico`, `testo_libero`, `foto_obbligatoria`
- Salvataggio progressivo (ogni `wire:change`); finalizzazione esplicita
- Foto salvate in `storage/app/allegati/checklist/` e servite via route autenticata
- Vincolo unico: una compilazione per coppia template+commessa

---

## DVI — Digital Vehicle Inspection

Ispezione digitale veicolo da meccanico al cliente: foto, video, approvazione online.

**Route:** `/officina/dvi/{commessa}/nuova`, `/officina/dvi/{ispezione}/anteprima`, `/dvi/{token}` (portale pubblico)

- Foto salvate in `storage/app/dvi/foto/`, thumbnail con Intervention Image (GD)
- Video: upload chunked (chunk → temp → riassemblaggio), thumbnail con ffmpeg
- Portale cliente HTML puro (no Livewire/Alpine) per massima compatibilità mobile
- Token opaco 64 char con scadenza; dopo la risposta crea righe commessa dalle voci approvate
- 10 categorie DVI predefinite, configurabili in Impostazioni

---

## Deposito Gomme

Gestione deposito pneumatici: accettazione, rientro, cambio stagionale massivo, etichette QR.

**Route:** `/deposito`, `/deposito/commessa/{id}`, `/deposito/cambio-stagionale`, `/deposito/report`, `/deposito/etichetta/{pneumatico}`

- `Pneumatico` con `codiceEtichetta()` → `{prefisso}-{anno}-{id:05d}`
- Movimenti immutabili; `StagionePneumatico::opposta()` per il cambio massivo
- Etichette PDF A6 o adesivo 100×50mm con QR code; multi-etichetta A4 (4 per pagina)
- QR scan tramite lo stesso scanner Alpine.js del board meccanico
- Distribuzione appuntamenti cambio stagionale su 5 giorni lavorativi bilanciando il carico
- Badge menu: set in deposito da più di 180 giorni
- Mappa scaffali SVG nel report (celle rosse = occupato, verdi = libero)
- PFU (D.Lgs. 152/2006): lista annuale visibile nel report; documentazione a carico dell'officina

---

## Veicoli di Cortesia

Flotta veicoli di cortesia con gestione prestiti, firma contratto comodato, calendario disponibilità.

**Route:** `/cortesia`, `/cortesia/flotta`, `/cortesia/consegna`, `/cortesia/prestiti/{id}/rientro`, `/cortesia/prestiti/{id}/contratto`  
**API FullCalendar:** `GET /api/cortesia/disponibilita`

- `VeicoloCortesia::isDisponibile(dal, al)`: verifica sovrapposizioni sui prestiti attivi
- Flusso consegna 4-step tablet; flusso rientro con validazione km e avviso carburante
- PDF contratto comodato (clausole art. 1803 c.c. fisso nel template Blade)
- `patente_numero` e `patente_scadenza` su `clienti` — esclusi da audit log ed export CSV
- Badge menu: prestiti in ritardo (stato `in_corso` + data rientro prevista < oggi)

---

## Carrozzeria

Gestione sinistri, perizie e danni veicolo; visibile solo per commesse di tipo `carrozzeria`.

**Route:** `/commesse/{id}/pdf/carrozzeria`, `GET /impostazioni/compagnie`

- `CompagniaAssicurativa` + `Sinistro` + `Perizia` per la gestione assicurativa
- SVG interattivo per mappare i danni per zona (enum `ZonaDanno`)
- `GeneraFatturaDoppiaAction`: doppia fattura (cliente + assicurazione) con progressivi sequenziali
- Download ZIP foto danni (ZipArchive PHP nativo)
- Workflow `StatoCarrozzeria` parallelo allo stato commessa

---

## Analytics & Dashboard

Dashboard con KPI in tempo reale; report meccanici, marginalità, commesse, pacchetti.

**Route:** `/dashboard`, `/analytics/meccanici`, `/analytics/marginalita`, `/analytics/commesse`, `/analytics/pacchetti`

- `KpiService` con `Cache::remember` TTL 10 min; invalidazione automatica da Observers
- `MeccaniciService`: ore lavorate vs fatturate, efficienza, margine per meccanico
- Chart.js 2.9.4 (`window.Chart`); export PDF con cattura base64 del grafico
- Aggiornamento badge menu ogni 2 minuti via fetch

---

## Tariffe & Pacchetti Servizio

Listino tariffe manodopera (catalogo operazioni) e pacchetti servizio predefiniti applicabili alle commesse.

**Route:** `/impostazioni/tariffe`, `/impostazioni/pacchetti`, `/analytics/pacchetti`

- `TariffaManodopera`: `minuti_standard` → `ore_standard` accessor; typeahead in GestioneRighe
- `PacchettoServizio::isCompatibile(Commessa)`: filtro per tipo_commessa, tipo_veicolo, alimentazione
- `ApplicaPacchettoAction`: modal 2 step (ricerca + preview righe modificabili) prima della conferma
- Import/export CSV tariffe con BOM UTF-8; delimitatore `;`
- Seeder: 31 tariffe comuni + 3 pacchetti di esempio

---

## Listini e Tariffe (Prezzi automatici)

Matrici di ricarico a scaglioni per il calcolo automatico del prezzo di vendita ricambi, e tariffe orarie nominate per le righe di manodopera.

**Route:** `GET /impostazioni/listini` (tab `matrici` / `tariffe`)

### Matrici prezzo ricambi
- Scaglioni contigui: `costo_da` inclusivo (≥), `costo_a` esclusivo (<), ultimo scaglione aperto (`costo_a null`)
- Markup percentuale + arrotondamento per eccesso (0.10 / 0.50 / 1.00 / nessuno)
- Anteprima live nel modal di editing: inserisci un costo e vedi il prezzo suggerito in tempo reale
- Una sola matrice può essere "default"; cambio con `MatricePrezzoService::setDefault()` transazionale
- Su nuova riga ricambio: `prezzo_unitario` auto-calcolato dalla matrice default se non specificato manualmente
- Badge "matrice" / "manuale" nell'OdL; pulsante "Riapplica matrice" per ricalcolare

### Tariffe orarie manodopera
- Tariffe orarie nominate (Meccanica, Elettrauto, Carrozzeria…) distinte dal catalogo operazioni `TariffaManodopera`
- Select nel modal riga manodopera precompila `prezzo_unitario`; modifica manuale sempre possibile
- Default pre-selezionato su nuova riga se presente
- `tariffa_oraria_id` su `commessa_righe`: solo riferimento per reportistica, il valore è snapshot in `prezzo_unitario`

---

## Scadenziario & Notifiche Email

Promemoria scadenze veicolo (revisione, tagliando, ecc.) con invio email automatico.

**Route:** `GET /scadenziario`, `GET /impostazioni/email`

- `InviaRichiamiScadenza` Job idempotente (verifica `NotificaLog` ultime 24h)
- Scheduler `dailyAt('08:00')`; finestra `notifica_giorni_prima` filtrata in PHP
- Template email configurabili con variabili `{{VARIABILE}}`
- `MailConfigService::applica()` sovrascrive la config SMTP a runtime dal DB settings

---

## Portale Cliente

Pagine pubbliche accessibili via link firmato (30 gg), senza login.

| Portale | Route | Contenuto |
|---------|-------|-----------|
| Stato commessa | `GET /cliente/{token}` | Stato, note, firma accettazione preventivo |
| DVI risposta | `GET /dvi/{token}` | Voci ispezione con foto/video, approvazione/rifiuto |

---

## Impostazioni

Configurazione globale dell'officina: dati azienda, SMTP, ponti (risorse agenda), checklist, tariffe, deposito gomme, lookup targa, PWA.

**Route:** `/impostazioni/*`

- `setting(string $key)` helper globale (cache Laravel) per leggere le impostazioni
- Ponti: CRUD + drag & drop per riordinare
- Lookup targa: provider configurabile (mock, InfoTarga, OpenApi); cache 30 gg per targa

---

## PWA

Installabile su tablet/smartphone come Progressive Web App.

- Service Worker custom in `public/sw.js` con 3 strategie (cache-first, network-first, stale-while-revalidate)
- Manifest configurato in `config/pwa.php`
- Pagina offline: `public/offline.html` (pre-cacheata all'install)
- Richiede HTTPS (o localhost per sviluppo Chrome/Chromium)
