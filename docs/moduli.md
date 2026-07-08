# Moduli funzionali — Officina Hub

Panoramica di tutte le aree applicative, con route principali e note operative.

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

**Route:** `GET /commesse`, `GET /commesse/{id}`, `GET /commesse/{id}/qr-code`

- Righe libere (manodopera, articoli, note) con typeahead su catalogo/tariffe
- Scarico automatico magazzino al passaggio a `completata` (CommessaObserver)
- Allegati con upload multiplo e anteprima
- Generazione PDF scheda e preventivo (dompdf)
- Widget veicolo di cortesia nel dettaglio
- Pulsante per avviare flusso deposito gomme dalla commessa

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
