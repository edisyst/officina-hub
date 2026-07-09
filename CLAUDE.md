cc
# Officina Hub — CLAUDE.md

Gestionale web on-premise officine meccaniche (auto, moto, carrozzerie).
Stack: Laravel 11 + Livewire 3 + Alpine.js + AdminLTE 3 + MariaDB 11 + FullCalendar v6.

> Route complete: `docs/routes.md` — Note architetturali per modulo: `docs/architettura.md`

## Comandi Principali

```bash
# Dev
php artisan serve
npm run dev                    # watch mode
npm run build                  # build produzione
php artisan migrate:fresh --seed

# Worker / Scheduler
php artisan queue:work --queue=default --tries=3 --backoff=60
php artisan schedule:run       # ogni minuto in produzione

# Backup
php artisan backup:run && php artisan backup:clean && php artisan backup:monitor

# Cache
php artisan config:cache && php artisan view:clear && php artisan cache:clear
php artisan tinker --execute="Cache::forget('kpi_sparkline_fatturato'); Cache::forget('kpi_grafico_fatturato');"

# Test
php artisan test
php artisan test --testsuite=Feature
php artisan test --filter CommessaFlussoTest
```

### Docker
```bash
docker-compose up -d
docker-compose exec app bash
docker-compose logs -f app
# Cron e queue già avviati da docker/php/entrypoint.sh
```

## Database

- **DB locale**: `officina_hub` su MySQL/MariaDB (Laragon: root senza password)
- **DB test**: SQLite in-memory (phpunit.xml)
- Tutti i modelli con nomi italiani hanno `$table` esplicita

## Credenziali default (seeder)

| Ruolo       | Email                                   | Password |
|-------------|------------------------------------------|----------|
| admin       | admin@admin.admin                        | admin    |
| accettatore | accettatore@accettatore.accettatore      | admin    |
| meccanico   | meccanico@meccanico.meccanico            | admin    |
| cassa       | cassa@cassa.cassa                        | admin    |

## Workflow stati commessa

```
bozza → accettata → in_lavorazione ⇄ sospesa
                   → completata → consegnata → fatturata
```

Ogni transizione loggata in `commessa_log`. Accettazione e consegna richiedono firma SVG.

## Struttura chiave

```
app/
├── Actions/        # Commessa/, Lavorazione/, Magazzino/, Fatturazione/
├── Enums/          # StatoCommessa, TipoCommessa, TipoRiga, TipoDocumento, ...
├── Http/Controllers/Api/
├── Livewire/       # Agenda/, Clienti/, Commesse/, Dashboard/, Fatturazione/,
│                   # Impostazioni/, Magazzino/, Marcatempo/, Veicoli/
├── Models/         # Cliente, Veicolo, Commessa, Documento, Articolo, ...
├── Observers/      # CommessaObserver, DocumentoObserver, PagamentoObserver, ...
├── Policies/       # 13 policy; Gate::before per admin bypass in AppServiceProvider
└── Services/       # PdfService, MarginalitaService, NumerazioneService, FatturaPAService, ...

resources/views/
├── layouts/app.blade.php       # AdminLTE (usa {{ $slot }}, non @extends)
├── layouts/tablet.blade.php    # Ottimizzato tablet (18px, btn 56px, jsQR CDN)
├── livewire/
└── pdf/                        # Template dompdf
```

## Note architetturali generali

- Livewire component non contengono logica di business: delegano ad Actions/Services.
- PDF generati da `barryvdh/laravel-dompdf` con template Blade in `resources/views/pdf/`.
- Firma digitale via `signature_pad` (npm), salvata come SVG nel DB.
- Numero progressivo commessa usa `lockForUpdate()` per evitare race condition.
- FullCalendar v6 Premium (CC licence) inizializzato in `Alpine.js init()`, non si re-inizializza ad ogni render Livewire.
- Endpoint API agenda in `routes/web.php` (prefisso `api/`), autenticati via sessione Laravel.
- Timer marcatempo lato client in `setInterval` Alpine, timestamp inizio salvato in JS (non Livewire).
- `MarginalitaService::calcola()` usa `costo_orario` utente o `setting.costo_orario_default`.
- CSV export: sempre BOM UTF-8 (`\xEF\xBB\xBF`) + separatore `;` per Excel italiano.
- Eager-load obbligatorio: `Commessa::with(['cliente','veicolo','righe','lavorazioni.user','allegati'])`.

## PWA

- Service Worker attivo su HTTPS (Docker+Caddy) o `localhost`.
- `public/sw.js` custom (non generato dal pacchetto): cache-first asset, network-first Livewire/API, stale-while-revalidate pagine tablet.
- Manifest: `config/pwa.php`; rigenerare con `php artisan erag:update-manifest`.

## Asset statici

AdminLTE 3 in `public/vendor/adminlte/` (da `node_modules/admin-lte`):
```bash
cp -r node_modules/admin-lte/dist public/vendor/adminlte/dist
cp -r node_modules/admin-lte/plugins public/vendor/adminlte/plugins
```
