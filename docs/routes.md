# Route per modulo

## Step 31 — Vista rapida stato veicolo
```
GET  /stato-veicolo   → stato-veicolo   (tutti i ruoli autenticati)
```
Shortcut globale F2; pulsante navbar `fa-phone`.

## Step 29 — Matrici prezzi manodopera e ricambi
```
GET  /impostazioni/listini   → impostazioni.listini   (admin)
     ?tab=matrici            → Livewire MatriciPrezzo
     ?tab=tariffe            → Livewire TariffeOrarie
```

## Step 28 — Redditività OdL
```
GET  /analytics/profitability   → analytics.profitability   (admin, cassa — gate view-margins)
```

## Step 27 — Accettazione veicolo one-screen
```
GET  /acceptance   → acceptance   (admin, accettatore)
```

## Step 2 — Agenda / Marcatempo
```
GET  /agenda                    → agenda.index         (admin, accettatore)
GET  /officina/marcatempo       → marcatempo           (admin, meccanico)
GET  /api/appuntamenti          → api.appuntamenti     (auth, JSON FullCalendar)
GET  /api/risorse-agenda        → api.risorse-agenda   (auth, JSON risorse)
GET  /impostazioni/ponti        → impostazioni.ponti   (admin)
```

## Step 3 — Magazzino
```
GET  /magazzino/articoli        → magazzino.articoli       (admin, accettatore, cassa)
GET  /magazzino/articoli/{id}   → magazzino.articoli.show  (admin, accettatore, cassa)
GET  /magazzino/fornitori       → magazzino.fornitori      (admin, accettatore)
GET  /magazzino/movimenti       → magazzino.movimenti      (admin)
GET  /magazzino/report          → magazzino.report         (admin, cassa)
GET  /magazzino/categorie       → magazzino.categorie      (admin)
```

## Step 4 — Fatturazione
```
GET  /fatturazione/documenti             → fatturazione.documenti          (admin, cassa)
GET  /fatturazione/documenti/{id}        → fatturazione.documenti.show     (admin, cassa)
GET  /fatturazione/scadenziario          → fatturazione.scadenziario       (admin, cassa)
GET  /fatturazione/registro-iva          → fatturazione.registro-iva       (admin, cassa)
GET  /fatturazione/documenti/{id}/xml    → fatturazione.documenti.xml      download XML FatturaPA
GET  /fatturazione/documenti/{id}/zip    → fatturazione.documenti.zip      download ZIP SdI
GET  /fatturazione/documenti/{id}/pdf    → fatturazione.documenti.pdf      download PDF cortesia
```

## Step 5 — QR / Checklist
```
GET  /commesse/{id}/qr-code                        → commesse.qr-code        (admin, accettatore)
GET  /officina/checklist/{commessa}/{template}     → officina.checklist      (admin, meccanico)
GET  /allegati/checklist/{filename}                → checklist.foto          autenticata
GET  /impostazioni/checklist                       → impostazioni.checklist  (admin)
```

## Step 6 — Scadenziario / Email / Portale Cliente
```
GET  /scadenziario          → scadenziario.index   (admin, accettatore)
GET  /impostazioni/email    → impostazioni.email   (admin)
GET  /cliente/{token}       → cliente.portale      (pubblico, URL firmato 30 gg)
```

## Step 7 — Carrozzeria
```
GET  /impostazioni/compagnie             → impostazioni.compagnie     (admin)
GET  /commesse/{id}/pdf/carrozzeria      → pdf.carrozzeria            download PDF
GET  /commesse/{id}/foto-danni/zip       → carrozzeria.foto.zip       download ZIP
GET  /allegati/foto-danni/{filename}     → allegati.foto-danni        autenticata
GET  /allegati/perizie/{filename}        → allegati.perizia           autenticata
```

## Step 8 — Analytics
```
GET  /dashboard                  → analytics.dashboard      (tutti)
GET  /analytics/meccanici        → analytics.meccanici      (admin)
GET  /analytics/marginalita      → analytics.marginalita    (admin, cassa)
GET  /analytics/commesse         → analytics.commesse       (admin, accettatore, cassa)
GET  /analytics/profitability    → analytics.profitability  (admin, cassa — gate view-margins)
GET  /api/menu-badges            → api.menu-badges          (auth, JSON)
```

## Step 10 — DVI
```
GET  /officina/dvi/{commessa}/nuova      → dvi.nuova              (admin, meccanico, accettatore)
GET  /officina/dvi/{ispezione}/anteprima → dvi.anteprima          (admin, meccanico, accettatore)
GET  /dvi/media/{media}                  → dvi.media              (auth)
GET  /dvi/media/{media}/thumb            → dvi.media.thumb        (auth)
POST /api/dvi/upload-chunk               → api.dvi.upload-chunk   (auth)
GET  /dvi/{token}                        → dvi.portale            (pubblico)
POST /dvi/{token}/risposte               → dvi.salva-risposte     (pubblico)
GET  /dvi/{token}/conferma               → dvi.conferma           (pubblico)
GET  /dvi/{token}/media/{media}          → dvi.media.cliente      (pubblico, token-based)
GET  /impostazioni/dvi-categorie         → impostazioni.dvi-categorie (admin)
```

## Step 11 — Tariffe / Pacchetti
```
GET  /impostazioni/tariffe   → impostazioni.tariffe   (admin)
GET  /impostazioni/pacchetti → impostazioni.pacchetti (admin)
GET  /analytics/pacchetti    → analytics.pacchetti    (admin)
```

## Step 12 — Deposito Pneumatici
```
GET  /deposito                        → deposito.index              (admin, accettatore)
GET  /deposito/commessa/{id}          → deposito.commessa           (admin, accettatore)
GET  /deposito/cambio-stagionale      → deposito.cambio-stagionale  (admin, accettatore)
GET  /deposito/report                 → deposito.report             (admin, accettatore, cassa)
GET  /deposito/etichetta/{pneumatico} → deposito.etichetta          (admin, accettatore, meccanico)
POST /deposito/etichette-multiple     → deposito.etichette-multiple (admin, accettatore, meccanico)
GET  /deposito/qr/{codice}            → deposito.qr                 (admin, accettatore, meccanico)
```

## Step 13 — Cortesia / Lookup Targa
```
GET  /cortesia                             → cortesia.index              (admin, accettatore)
GET  /cortesia/flotta                      → cortesia.flotta             (admin)
GET  /cortesia/report                      → cortesia.report             (admin, accettatore, cassa)
GET  /cortesia/consegna                    → cortesia.consegna           (admin, accettatore)
GET  /cortesia/consegna/commessa/{id}      → cortesia.consegna.commessa  (admin, accettatore)
GET  /cortesia/prestiti/{id}/rientro       → cortesia.rientro            (admin, accettatore)
GET  /cortesia/prestiti/{id}/contratto     → cortesia.contratto          (admin, accettatore)
GET  /api/cortesia/disponibilita           → api.cortesia.disponibilita  (auth, JSON FullCalendar)
GET  /impostazioni/lookup-targa-test       → impostazioni.lookup-test    (admin)
```

## Step 14 — Contabilità
```
GET  /contabilita/prima-nota       → contabilita.prima-nota       (admin, cassa)
GET  /contabilita/riepilogo        → contabilita.riepilogo        (admin, cassa)
GET  /contabilita/export-sdi-batch → contabilita.export-sdi-batch (admin, cassa)
```

## Step 15 — Acquisti
```
GET  /acquisti/ordini              → acquisti.ordini        (admin, accettatore)
GET  /acquisti/ordini/crea         → acquisti.ordini.create (admin, accettatore)
GET  /acquisti/ordini/{id}         → acquisti.ordini.show   (admin, accettatore)
GET  /acquisti/ordini/{id}/ricevi  → acquisti.ordini.ricevi (admin, accettatore)
GET  /acquisti/genera-ordini       → acquisti.genera-ordini (admin, accettatore)
GET  /acquisti/fatture             → acquisti.fatture       (admin, cassa)
```

## Step 16 — CRM
```
GET  /crm/dashboard  → crm.dashboard  (admin)
GET  /crm/campagne   → crm.campagne   (admin)
```

## Step 17 — Garanzie
```
GET  /garanzie/report          → garanzie.report          (admin, cassa)
GET  /impostazioni/case-madri  → impostazioni.case-madri  (admin)
```
