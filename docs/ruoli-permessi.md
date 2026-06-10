# Ruoli e Permessi — Officina Hub

## Ruoli disponibili

| Ruolo | Descrizione |
|-------|-------------|
| `admin` | Accesso completo; bypassa tutte le Policy tramite `Gate::before()` |
| `accettatore` | Accettazione commesse, agenda, clienti, veicoli, deposito gomme, cortesia |
| `meccanico` | Board marcatempo, checklist tablet, DVI, etichette deposito |
| `cassa` | Fatturazione, magazzino (sola lettura), report |

I ruoli sono gestiti con `spatie/laravel-permission`.

---

## Policy registrate

Ogni modello sensibile ha una Policy registrata in `AppServiceProvider` con `Gate::policy()`.

| Policy | Modello |
|--------|---------|
| `ClientePolicy` | `Cliente` |
| `VeicoloPolicy` | `Veicolo` |
| `CommessaPolicy` | `Commessa` |
| `PontePolicy` | `Ponte` |
| `AppuntamentoPolicy` | `Appuntamento` |
| `LavorazionePolicy` | `Lavorazione` |
| `ArticoloPolicy` | `Articolo` |
| `FornitorePolicy` | `Fornitore` |
| `DocumentoPolicy` | `Documento` |
| `AllegatoPolicy` | `Allegato` |
| `ScadenzaPolicy` | `Scadenza` |

**Admin bypass:** `Gate::before(fn($user) => $user->hasRole('admin') ? true : null)` — l'admin non passa mai per le singole Policy.

---

## Accesso per area

| Area / Route | admin | accettatore | meccanico | cassa |
|---|:---:|:---:|:---:|:---:|
| Dashboard / Analytics | ✓ | ✓ | ✓ | ✓ |
| Clienti & Veicoli | ✓ | ✓ | — | — |
| Commesse (lista + dettaglio) | ✓ | ✓ | — | — |
| Agenda | ✓ | ✓ | — | — |
| Marcatempo / Checklist / DVI | ✓ | ✓ | ✓ | — |
| Magazzino (lettura) | ✓ | ✓ | — | ✓ |
| Magazzino (movimenti) | ✓ | — | — | — |
| Fatturazione | ✓ | — | — | ✓ |
| Analytics avanzate | ✓ | — | — | ✓ |
| Deposito Gomme | ✓ | ✓ | ✓ | — |
| Veicoli di Cortesia | ✓ | ✓ | — | — |
| Impostazioni | ✓ | — | — | — |
| Audit Log | ✓ | — | — | — |

---

## Workflow stati commessa

```
bozza ──► accettata ──► in_lavorazione ◄──► sospesa
                              │
                              ▼
                         completata ──► consegnata ──► fatturata
```

### Transizioni e requisiti

| Da | A | Requisiti |
|----|---|-----------|
| `bozza` | `accettata` | Firma SVG cliente |
| `accettata` | `in_lavorazione` | — |
| `in_lavorazione` | `sospesa` | — |
| `sospesa` | `in_lavorazione` | — |
| `in_lavorazione` | `completata` | — (trigger scarico magazzino) |
| `completata` | `consegnata` | Firma SVG cliente + creazione scadenze automatiche |
| `consegnata` | `fatturata` | Documento emesso |

Ogni transizione viene loggata in `commessa_log` (modello immutabile).

---

## Audit Log

Abilitato via `spatie/laravel-activitylog` su:

- `Commessa` → campi `stato`, `data_consegna`, `km_ingresso`
- `Documento` → campi rilevanti alla fatturazione
- `MovimentoMagazzino` → tutti i campi (il movimento è comunque immutabile)
- `User` → campi profilo

Vista `/audit-log` accessibile solo all'admin. Il log non è eliminabile dall'interfaccia.

---

## Portali pubblici (senza login)

| Portale | Autenticazione |
|---------|---------------|
| `GET /cliente/{token}` | `URL::signedRoute` con `encrypt($cliente->id)`, scadenza 30 gg |
| `GET /dvi/{token}` | Token opaco 64 char con `link_scade_at` nel DB |

I portali pubblici non espongono dati sensibili oltre a quanto strettamente necessario.
