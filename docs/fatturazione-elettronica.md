# Fatturazione Elettronica — Officina Hub

## Formato supportato

**FatturaPA FPR12** — standard AdE v1.9 (privati e PA).  
Schema XSD: `storage/app/fatturapa/xsd/Schema_VFPR12.xsd` (da scaricare dal sito AdE).

---

## Ciclo di vita del documento

```
bozza ──► emessa ──► (inviata_sdi) ──► accettata / rifiutata / scartata
              │
              └──► nota_di_credito (se correzione necessaria)
```

- Il documento è **immutabile** una volta in stato `emessa`.
- Correzioni: emettere una Nota di Credito collegata via `documento_correlato_id`.
- `DocumentoObserver` popola la tabella `registro_iva` al passaggio a `emessa`.

---

## Generazione XML

`FatturaPAService::genera()` renderizza il template Blade `fatturapa/fattura.blade.php` e ritorna la stringa XML.

`FatturaPAService::valida()` valida l'XML contro lo schema XSD (richiede il file XSD nella path sopra indicata).

---

## Download disponibili

| Endpoint | Contenuto |
|----------|-----------|
| `GET /fatturazione/documenti/{id}/xml` | XML FatturaPA |
| `GET /fatturazione/documenti/{id}/zip` | ZIP pronto per caricamento manuale su SdI |
| `GET /fatturazione/documenti/{id}/pdf` | PDF cortesia (dicitura "Documento privo di valore fiscale") |

Il file ZIP viene generato in `storage/app/fatturapa/temp/` e può essere eliminato dopo il download.

---

## Numerazione

`NumerazioneService::prossimo()` usa `lockForUpdate()` in transaction per garantire l'unicità dei progressivi sotto carico concorrente.

---

## Registro IVA

Tabella `registro_iva` popolata automaticamente dall'Observer.  
Visualizzazione in `/fatturazione/registro-iva` con filtro per periodo e tipo registro.

---

## Scadenziario

Pagamenti con scadenze gestiti in `/fatturazione/scadenziario`.  
I metodi di pagamento e i termini sono configurabili per ogni documento.

---

## Fattura doppia (Carrozzeria)

Per commesse carrozzeria con sinistro: `GeneraFatturaDoppiaAction` crea due documenti collegati:
- Fattura al cliente (quota a carico)
- Fattura all'assicurazione (quota sinistro)

I progressivi sono assegnati in ordine sequenziale (prima cliente, poi assicurazione).

---

## Canale SdI Diretto

Il modulo di invio diretto al Sistema di Interscambio è presente ma **disabilitato di default** (`SDI_ABILITATO=false` in `.env`).

Il pulsante "Invia al SdI" nel dettaglio documento appare solo se `config('sdi.abilitato') = true`.

Per l'attivazione completa (accreditamento, certificati, configurazione SFTP/Web Service) seguire la guida in [`docs/sdi-diretto.md`](sdi-diretto.md).

---

## Ricevute SdI

Le ricevute SdI caricate manualmente sono salvate in `storage/app/fatturapa/ricevute/`.  
Il tracciamento è gestito tramite il modello `SdiLog`.

---

## Dati azienda

I dati emittente (P.IVA, ragione sociale, indirizzo, codice destinatario SdI, ecc.) sono configurabili in **Impostazioni → Dati Azienda** dall'interfaccia admin, senza modificare codice o `.env`.
