# Architettura — Officina Hub

## Stack tecnologico

| Layer | Tecnologia |
|-------|-----------|
| Backend | Laravel 11 (PHP 8.3) |
| Frontend reattivo | Livewire 3 + Alpine.js |
| UI framework | AdminLTE 3 (Bootstrap 4) |
| Database | MariaDB 11 (MySQL 10.6+) |
| Calendario | FullCalendar v6 Premium (CC licence) |
| PDF | barryvdh/laravel-dompdf |
| PWA | erag/laravel-pwa + Service Worker custom |
| Asset bundler | Vite |
| Test DB | SQLite in-memory |

---

## Pattern architetturali

### Actions
La logica di business vive in classi `Action` (es. `AggiornaStatoAction`, `ScaricoCommessaAction`).  
I componenti Livewire e i Controller non contengono logica di dominio: delegano alle Action.

### Services
Operazioni trasversali o con effetti collaterali esterni: `PdfService`, `KpiService`, `FatturaPAService`, `LookupTargaService`, `EtichettaDepositoService`, ecc.

### Livewire Components
Ogni pagina funzionale è un componente Livewire con template Blade separato.  
Namespace: `app/Livewire/{Area}/{Componente}.php` → `resources/views/livewire/{area}/{componente}.blade.php`.

### Policies
Ogni modello sensibile ha una Policy registrata in `AppServiceProvider`.  
`Gate::before()` permette all'admin di bypassare tutti i controlli.

### Observers
`CommessaObserver` e `DocumentoObserver` reagiscono ai cambi di stato per triggherare scarichi magazzino, aggiornamenti registro IVA e invalidazione cache KPI.

### Enums PHP nativi
Tutti i tipi enumerati usano enum PHP 8.1+ (es. `StatoCommessa`, `TipoRiga`, `TipoMovimento`).

---

## Struttura directory chiave

```
app/
├── Actions/          # logica di business (Commessa/, Magazzino/, Fatturazione/)
├── Enums/            # enum PHP nativi
├── Http/Controllers/ # solo routing/download; Api/ per endpoint JSON
├── Livewire/         # componenti UI reattivi
├── Models/           # Eloquent con $table esplicita (nomi italiani)
├── Observers/        # side-effect al cambio di stato modelli
├── Policies/         # autorizzazione per-modello
└── Services/         # operazioni trasversali

resources/views/
├── layouts/          # app.blade.php (desktop), tablet.blade.php, marcatempo.blade.php
├── livewire/         # template dei componenti
└── pdf/              # template dompdf (scheda, preventivo, fattura, contratto)

routes/
├── web.php           # tutte le route (inclusi endpoint api/ con auth sessione)
└── console.php       # scheduler (InviaRichiamiScadenza, backup)
```

---

## Database

- **Produzione**: `officina_hub` su MariaDB/MySQL; credenziali in `.env`
- **Test**: SQLite in-memory (phpunit.xml); attenzione a `YEAR()`/`MONTH()` non supportati da SQLite (il service usa `datePartExpressions()` per la compatibilità)
- Tutti i modelli con nome italiano hanno `protected $table` esplicito

### Regole di integrità

- `MovimentoMagazzino`: immutabile dopo la creazione (mai update/delete)
- `DepositoPneumatico`: movimenti immutabili (come movimenti magazzino)
- `Documento`: immutabile dopo `emessa`; correzioni via nota di credito
- `giacenza_attuale` su `Articolo` è denormalizzata; aggiornata con `lockForUpdate()` in transaction
- `NumerazioneService::prossimo()` usa `lockForUpdate()` per unicità progressivi sotto carico

---

## Sicurezza

- `SecurityHeaders` middleware globale (X-Frame-Options, CSP, ecc.)
- CSP usa `'unsafe-inline'` intenzionalmente (richiesto da Livewire + Alpine.js inline)
- Rate limiting: `throttle:login` (5/min) su POST `/login`; `throttle:api-internal` (120/min) su `/api/*`
- Upload file: doppia validazione `mimes:` + `mimetypes:` + nome randomizzato
- Sessione: 8 ore di lifetime; `SESSION_SECURE_COOKIE=false` in `.env` per sviluppo HTTP
- Audit log via `spatie/laravel-activitylog` su `Commessa`, `Documento`, `MovimentoMagazzino`, `User`

---

## Comunicazione frontend-backend

- **Livewire**: binding reattivo via `wire:model`, azioni via `wire:click`; eventi cross-component via `dispatch()` / `#[On]`
- **Alpine.js**: stato UI locale (modal, tab, timer); inizializzazione in `x-init`
- **FullCalendar**: inizializzato in `Alpine init()` (non si re-inizializza ad ogni render Livewire); comunicazione via `@this.metodo()` e evento `calendar-refresh`
- **Chart.js 2.9.4**: esposto come `window.Chart`; grafici aggiornati via eventi Livewire dispatch
- **Badge menu**: aggiornati ogni 2 minuti con `fetch('/api/menu-badges')` (cache 120s per-utente)

---

## Queue e Scheduler

| Job / Task | Trigger |
|-----------|---------|
| Email notifiche stato commessa | `AggiornaStatoAction` (accodata, max 1/5min per commessa) |
| `InviaRichiamiScadenza` | Scheduler `dailyAt('08:00')` |
| `InviaNotificaCambioStagionale` | Lanciato dal flusso cambio stagionale massivo |
| `InviaDviCliente` | Invio DVI al cliente |
| `NotificaDviRisposta` | Risposta DVI ricevuta |
| `backup:run` | Scheduler `02:00` |
| `backup:clean` | Scheduler `02:30` |
| `backup:monitor` | Scheduler `03:00` |

Queue worker: `php artisan queue:work --queue=default --tries=3 --backoff=60`
