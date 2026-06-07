# Canale Diretto SdI — Guida all'Attivazione

Questo documento descrive i passi necessari per attivare l'invio diretto di fatture
elettroniche al Sistema di Interscambio (SdI) dell'Agenzia delle Entrate tramite
canale SFTP o Web Service.

**Stato attuale:** il modulo è presente nel codice ma disabilitato
(`SDI_ABILITATO=false` in `.env`). Attivare solo dopo aver completato tutti
i passi formali descritti di seguito.

---

## E1. Pre-requisiti formali (fuori scope del codice)

### 1. Accreditamento canale SdI

L'accreditamento si richiede tramite il portale **"Fatture e Corrispettivi"**
dell'Agenzia delle Entrate ([fatturazione.agenziaentrate.gov.it](https://fatturazione.agenziaentrate.gov.it)):

- Accedere con SPID/CIE dell'amministratore o del delegato
- Scegliere il canale: **SFTP** (consigliato per volumi elevati) o **Web Service**
- Indicare il **Codice Destinatario** (7 caratteri) che verrà associato alla P.IVA
- Attendere approvazione (solitamente 3–10 giorni lavorativi)

### 2. Certificato di firma qualificata

La firma digitale dell'XML è obbligatoria per il canale Web Service e opzionale
(ma consigliata) per SFTP. CA accreditate:

| Provider | Prezzo indicativo | URL |
|----------|-------------------|-----|
| Aruba    | ~35–50 €/anno     | aruba.it |
| InfoCert | ~40–70 €/anno     | infocert.it |
| Namirial | ~40–80 €/anno     | namirial.com |

Il certificato viene fornito come file `.p12` o `.pfx` con password.
Copiarlo in `storage/app/fatturapa/cert/` e impostare le variabili:

```dotenv
SDI_CERT_PATH=storage/app/fatturapa/cert/cert.p12
SDI_CERT_PASSWORD=password_del_certificato
```

### 3. Test di interoperabilità

L'AdE fornisce un **ambiente di test** (ambiente di qualità) accessibile dalla
stessa interfaccia del portale Fatture e Corrispettivi. Eseguire almeno 3 invii
di prova con fatture di test prima di attivare il canale in produzione.

### 4. Configurazione canale SFTP

Dopo l'accreditamento, l'AdE fornisce:
- Hostname del server SFTP
- Credenziali (username e chiave SSH)
- Directory di caricamento (`/trasmissione`) e ricezione ricevute (`/ricezione`)

Impostare in `.env` di produzione:

```dotenv
SDI_ABILITATO=true
SDI_CANALE=sftp
SDI_SFTP_HOST=sftps.fatturapa.it
SDI_SFTP_PORT=22
SDI_SFTP_USERNAME=il_tuo_username_ade
SDI_SFTP_PRIVATE_KEY_PATH=storage/app/fatturapa/keys/id_rsa
SDI_SFTP_DIR_IN=/ricezione
SDI_SFTP_DIR_OUT=/trasmissione
```

---

## E2. Struttura codice predisposta

Il codice scheletro è già presente:

| File | Descrizione |
|------|-------------|
| `config/sdi.php` | Configurazione canale (tutti i parametri) |
| `app/Services/SdiService.php` | Servizio skeleton (TODO implementare) |
| `app/Jobs/InviaDocumentoSdi.php` | Job asincrono (TODO implementare) |

### Attivazione del pulsante UI

Il pulsante "Invia al SdI" nella pagina dettaglio documento è visibile
**solo se** `config('sdi.abilitato') === true`. Di default è nascosto.

### Implementazione dopo accreditamento

Dopo aver completato i passi formali, implementare in `SdiService`:

1. `inviaSftp()`: connessione SFTP con `phpseclib/phpseclib` o `league/flysystem-sftp`
2. `firma()`: firma XML con `openssl_pkcs12_read()` + `openssl_pkcs7_sign()`
3. `scaricaRicevute()`: polling periodico della cartella di ricezione

---

## Note importanti

- La **firma digitale** del file XML è gestita separatamente dalla generazione XML
  (già implementata in `FatturaPAService`)
- Il **namespace XML** della FatturaPA deve essere `http://ivaservizi.agenziaentrate.gov.it/docs/xsd/fatture/v1.2`
- Il nome file deve seguire il formato: `{CodiceTrasmittente}_{Progressivo}.xml`
  (es. `01234567890_00001.xml`)
- I file di ricevuta SdI (`.xml`) vanno salvati in `storage/app/fatturapa/ricevute/`
  e collegati alla tabella `sdi_log`
