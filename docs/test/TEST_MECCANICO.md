# Test funzionali — Ruolo Meccanico

## Marcatempo (tablet)
- [ ] Apri board marcatempo `/officina/marcatempo`
- [ ] Scansiona QR commessa con fotocamera tablet
- [ ] Avvia lavorazione su commessa
- [ ] Metti in pausa / riprendi lavorazione
- [ ] Ferma lavorazione — verifica ore registrate
- [ ] Verifica che timer client-side rimanga allineato

## Checklist (tablet)
- [ ] Apri checklist da QR code o link diretto
- [ ] Compila ogni voce (radio sì/no/N.D.)
- [ ] Carica foto da fotocamera
- [ ] Finalizza checklist — verifica `completata_at` impostato

## DVI (tablet)
- [ ] Apri nuova ispezione DVI `/officina/dvi/{commessa}/nuova`
- [ ] Aggiungi voce con foto
- [ ] Carica video (chunked upload) su voce
- [ ] Verifica thumbnail video generata
- [ ] Anteprima ispezione prima dell'invio

## Deposito Gomme
- [ ] Scansiona QR etichetta deposito → redirect corretto
- [ ] Visualizza scheda pneumatico

## Commesse (read-only)
- [ ] Visualizza dettaglio commessa assegnata
- [ ] Visualizza righe e note lavorazione
