# Test funzionali — Ruolo Admin

Credenziali: `admin@officinahub.local` / `password`

## Dashboard
- [ ] Dashboard carica KPI (fatturato, commesse aperte, meccanici attivi)
- [ ] Badge menu si aggiornano ogni 2 minuti

## Commesse
- [ ] Crea nuova commessa (bozza → accettata con firma SVG)
- [ ] Avanza stato: accettata → in_lavorazione → completata → consegnata (con firma)
- [ ] Aggiungi riga manodopera e riga ricambio
- [ ] Applica pacchetto servizio
- [ ] Carica allegato PDF/immagine
- [ ] Genera numero progressivo univoco

## Fatturazione
- [ ] Genera fattura da commessa consegnata
- [ ] Scarica PDF cortesia
- [ ] Scarica XML FatturaPA
- [ ] Registra pagamento (contanti / carta)
- [ ] Emetti nota di credito
- [ ] Visualizza registro IVA vendite
- [ ] Visualizza scadenziario clienti

## Magazzino
- [ ] Crea articolo con scorta minima
- [ ] Carico manuale stock
- [ ] Verifica scarico automatico al completamento commessa
- [ ] Esporta report CSV magazzino

## Agenda
- [ ] Crea appuntamento su FullCalendar
- [ ] Sposta appuntamento drag&drop
- [ ] Verifica disponibilità ponte

## Analytics
- [ ] Dashboard analytics: grafici fatturato/commesse
- [ ] Report marginalità commesse
- [ ] Report produttività meccanici
- [ ] Export CSV report

## Impostazioni
- [ ] Modifica dati officina e SMTP
- [ ] CRUD ponti sollevamento
- [ ] CRUD tariffe manodopera + import CSV
- [ ] CRUD pacchetti servizio
- [ ] CRUD case madri (garanzie)
- [ ] Gestione template email

## CRM
- [ ] Dashboard retention: clienti a rischio / persi / attivi
- [ ] Crea campagna email (solo clienti con consenso marketing)
- [ ] Aggiungi nota CRM su cliente

## Acquisti
- [ ] Crea ordine fornitore manuale
- [ ] Genera ordini da articoli sottoscorta
- [ ] Ricevi merce (parziale e completa)
- [ ] Importa fattura acquisto XML

## Contabilità
- [ ] Visualizza prima nota (entrate/uscite automatiche da pagamenti)
- [ ] Aggiungi movimento manuale
- [ ] Export batch XML FatturaPA

## Deposito Gomme
- [ ] Accettazione set pneumatici
- [ ] Cambio stagionale massivo
- [ ] Stampa etichetta A6 con QR
- [ ] Scansiona QR → redirect corretto

## Veicoli di Cortesia
- [ ] Crea veicolo flotta cortesia
- [ ] Avvia prestito con firma
- [ ] Registra rientro con km e carburante
- [ ] Scarica PDF contratto comodato

## Garanzie
- [ ] Crea garanzia su veicolo
- [ ] Aggiungi riga commessa "in garanzia" con casa madre
- [ ] Genera fattura doppia (cliente + casa madre)
- [ ] Visualizza report rimborsi case madri

## DVI
- [ ] Crea ispezione DVI da commessa
- [ ] Aggiungi foto/video voce ispezione
- [ ] Invia link DVI al cliente
- [ ] Converti voci approvate in righe commessa

## Sicurezza / Admin
- [ ] Visualizza audit log e filtra per modello
- [ ] Esegui backup manuale
- [ ] Visualizza log SdI

## Marcatempo (verifica supervisione)
- [ ] Visualizza lavorazioni attive in tempo reale
- [ ] Forza stop lavorazione in corso
