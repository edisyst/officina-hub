# Officina Hub

Gestionale web on-premise per officine meccaniche (auto, moto, carrozzerie). Copre l'intero ciclo di vita della commessa — dall'accettazione alla fatturazione elettronica FatturaPA — con agenda, magazzino, marcatempo tablet e checklist configurabili. Installabile come PWA su tablet di officina.

**Stack:** Laravel 11 · Livewire 3 · Alpine.js · AdminLTE 3 · MariaDB 11 · FullCalendar v6 · PWA

---

## Requisiti

| Strumento | Versione minima |
|-----------|----------------|
| PHP | 8.3 |
| Composer | 2.x |
| Node.js | 20.x |
| MySQL / MariaDB | 10.6 / 11 |
| Docker + Docker Compose | 24.x *(solo per installazione Docker)* |

---

## Installazione locale (Laragon / XAMPP / MySQL nativo)

### 1. Clona il repository

```bash
git clone <url-repository> officina-hub
cd officina-hub
```

### 2. Dipendenze PHP e Node

```bash
composer install
npm install
```

### 3. Configura l'ambiente

```bash
cp .env.example .env
php artisan key:generate
```

Apri `.env` e verifica le credenziali del database:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=officina_hub
DB_USERNAME=root
DB_PASSWORD=          # vuoto su Laragon, altrimenti inserisci la tua password
```

### 4. Crea il database

Crea il database `officina_hub` sul tuo server MySQL/MariaDB (da phpMyAdmin, MySQL Workbench o CLI):

```sql
CREATE DATABASE officina_hub CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 5. Esegui le migration e il seed

```bash
php artisan migrate --seed
```

Questo crea tutte le tabelle e inserisce i dati iniziali: ruoli, utente amministratore e impostazioni di default.

### 6. Compila gli asset

```bash
npm run build
```

### 7. Avvia il server di sviluppo

```bash
php artisan serve
```

L'applicazione è disponibile su **http://localhost:8000**.

> Per cambiare porta: `php artisan serve --port=8080`

---

## Installazione con Docker *(opzionale)*

> Usa questa sezione solo se non hai un ambiente PHP/MySQL locale (es. server Linux, CI, produzione).
> Con Laragon o XAMPP già installati, questa sezione non è necessaria.



### 1. Clona il repository e configura l'ambiente

```bash
git clone <url-repository> officina-hub
cd officina-hub
cp .env.example .env
php artisan key:generate   # oppure genera manualmente APP_KEY nel .env
```

Aggiorna `.env` con le credenziali Docker:

```dotenv
DB_HOST=db
DB_DATABASE=officina_hub
DB_USERNAME=officina
DB_PASSWORD=officina_secret
```

### 2. Avvia i container

```bash
docker-compose up -d
```

Questo avvia tre servizi: `app` (PHP-FPM 8.3), `web` (Caddy 2) e `db` (MariaDB 11).

### 3. Installa dipendenze e prepara l'applicazione

```bash
docker-compose exec app composer install
docker-compose exec app npm install
docker-compose exec app npm run build
docker-compose exec app php artisan migrate --seed
```

L'applicazione è disponibile su **http://localhost**.

---

## Credenziali di accesso (default)

| Ruolo | Email | Password |
|-------|-------|----------|
| Amministratore | `admin@admin.admin` | `admin` |

Per aggiungere altri utenti con ruoli diversi (`accettatore`, `meccanico`, `cassa`) usa la sezione Impostazioni dall'interfaccia amministratore oppure `php artisan tinker`.

---

## Funzionalità

| Area | Funzionalità |
|---|---|
| **Clienti & Veicoli** | Anagrafica, storico commesse |
| **Commesse** | Flusso bozza → fatturata, firma SVG, PDF scheda/preventivo |
| **Agenda** | FullCalendar v6, viste giorno/settimana/risorsa, drag & drop |
| **Marcatempo** | Board tablet, timer live, avvio/stop lavorazioni, scanner QR |
| **Magazzino** | Catalogo, movimenti transazionali, scarico automatico da commessa, report CSV |
| **Fatturazione** | FatturaPA FPR12 (AdE v1.9), nota di credito, scadenziario, registro IVA, PDF cortesia |
| **Checklist** | Template configurabili, compilazione tablet, foto, PDF |
| **PWA** | Installabile su tablet/smartphone, offline page, service worker custom |

---

## Eseguire i test

I test usano SQLite in-memory e non toccano il database di sviluppo.

```bash
# Tutti i test
php artisan test

# Solo feature test
php artisan test --testsuite=Feature

# Test specifico
php artisan test --filter CommessaFlussoTest
```

> **Attenzione:** se hai eseguito `php artisan config:cache`, pulisci la cache prima di lanciare i test:
> ```bash
> php artisan config:clear
> ```

---

## Comandi utili

```bash
# Ricrea il database di sviluppo da zero (migration + seed)
php artisan migrate:fresh --seed

# Svuota tutte le cache
php artisan cache:clear && php artisan view:clear && php artisan config:clear

# Avvia il queue worker (necessario per job in coda)
php artisan queue:work --queue=default --tries=3

# Log Docker in tempo reale
docker-compose logs -f app
```

---

## Riprodurre lo sviluppo

Sequenza completa dei comandi usati per costruire il progetto da zero.

### 1. Scaffolding Laravel + Breeze

```bash
composer create-project laravel/laravel officina-hub
cd officina-hub

# Autenticazione (login, registrazione, reset password)
composer require laravel/breeze --dev
php artisan breeze:install blade
```

### 2. Pacchetti PHP

```bash
# Livewire 3 (componenti reattivi)
composer require livewire/livewire

# Ruoli e permessi
composer require spatie/laravel-permission
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
php artisan migrate

# Generazione PDF
composer require barryvdh/laravel-dompdf
php artisan vendor:publish --provider="Barryvdh\DomPDF\ServiceProvider"

# PWA (Progressive Web App)
composer require erag/laravel-pwa
php artisan erag:install-pwa   # pubblica config, manifest.json, sw.js, offline.html

# QR code
composer require simplesoftwareio/simple-qrcode
```

### 3. Pacchetti Node

```bash
npm install admin-lte@^3          # AdminLTE 3 (Bootstrap 4 UI)
npm install alpinejs @alpinejs/sort
npm install signature_pad          # firma digitale SVG

# Copia gli asset AdminLTE in public/ (serviti staticamente, non via Vite)
cp -r node_modules/admin-lte/dist     public/vendor/adminlte/dist
cp -r node_modules/admin-lte/plugins  public/vendor/adminlte/plugins
```

### 4. Enum PHP nativi

Creati manualmente in `app/Enums/`:

```
StatoCommessa.php    — bozza, accettata, in_lavorazione, sospesa, completata, consegnata, fatturata
TipoCommessa.php     — meccanica, carrozzeria, tagliando
TipoCliente.php      — fisica, giuridica
TipoVeicolo.php      — auto, moto, furgone
Alimentazione.php    — benzina, diesel, ibrido, elettrico, gpl, metano
TipoRiga.php         — manodopera, articolo, altro
```

### 5. Migration

```bash
php artisan make:migration create_clienti_table
php artisan make:migration create_veicoli_table
php artisan make:migration create_cliente_veicolo_table
php artisan make:migration create_commesse_table
php artisan make:migration create_commessa_righe_table
php artisan make:migration create_allegati_table
php artisan make:migration create_commessa_log_table
php artisan make:migration create_settings_table

php artisan migrate
```

### 6. Model

```bash
php artisan make:model Cliente
php artisan make:model Veicolo
php artisan make:model Commessa
php artisan make:model CommessaRiga
php artisan make:model Allegato
php artisan make:model CommessaLog
php artisan make:model Setting
```

Tutti i modelli con nome italiano richiedono `protected $table` esplicita
(Laravel pluralizza in inglese: `Cliente` → `clientes` anziché `clienti`).

### 7. Policy

```bash
php artisan make:policy ClientePolicy  --model=Cliente
php artisan make:policy VeicoloPolicy  --model=Veicolo
php artisan make:policy CommessaPolicy --model=Commessa
```

Registrate in `AppServiceProvider` con `Gate::policy()`.
L'admin bypassa tutto tramite `Gate::before()`.

### 8. Actions

Creati manualmente in `app/Actions/Commessa/`:

```
GeneraNumeroProgressivoAction.php   — genera COM-YYYY-NNNN con lockForUpdate()
AggiornaStatoAction.php             — valida la transizione, aggiorna stato, scrive CommessaLog
```

### 9. Controller

```bash
php artisan make:controller SettingsController
php artisan make:controller PdfController
php artisan make:controller CommessaController
```

`ProfileController` è incluso da Breeze.

### 10. Service

Creato manualmente in `app/Services/`:

```
PdfService.php   — generaScheda(), generaPreventivo() via barryvdh/laravel-dompdf
```

### 11. Componenti Livewire

```bash
php artisan make:livewire Clienti/ListaClienti
php artisan make:livewire Clienti/DettaglioCliente
php artisan make:livewire Veicoli/ListaVeicoli
php artisan make:livewire Veicoli/DettaglioVeicolo
php artisan make:livewire Commesse/ListaCommesse
php artisan make:livewire Commesse/FormCommessa
php artisan make:livewire Commesse/DettaglioCommessa
php artisan make:livewire Commesse/GestioneRighe
php artisan make:livewire Commesse/GestioneAllegati
```

Ogni comando genera la classe PHP in `app/Livewire/` e il template Blade in
`resources/views/livewire/`.

### 12. Seeder

```bash
php artisan make:seeder RuoliSeeder
php artisan make:seeder AdminSeeder
php artisan make:seeder SettingsSeeder
php artisan make:seeder UtentiSeeder
php artisan make:seeder ClientiSeeder
php artisan make:seeder VeicoliSeeder
php artisan make:seeder CommesseSeeder
```

### 13. Test

```bash
php artisan make:test CommessaFlussoTest
php artisan make:test ClienteTest
php artisan make:test VeicoloTest
```

`ExampleTest` è incluso da Laravel. I test usano SQLite in-memory
(`phpunit.xml`: `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`).

---

## PWA — note per lo sviluppo

Il Service Worker richiede **HTTPS**. In locale usare Docker con Caddy (già configurato), oppure:

```bash
# Chromium/Chrome permette localhost senza TLS — aprire:
http://localhost:8000/officina/marcatempo
```

Per aggiornare il manifest/service worker dopo modifiche a `config/pwa.php`:

```bash
php artisan config:cache
php artisan view:clear
```

Gli **icon** PWA devono essere presenti in `public/images/icons/` (file PNG nelle dimensioni
72, 96, 128, 144, 152, 192, 384, 512 px). Le icone placeholder sono generate con PHP GD;
sostituirle con grafica professionale per la produzione.

Il file `public/sw.js` è personalizzato e NON viene sovrascritto da `erag:install-pwa` dopo
la prima installazione — modificare direttamente.

---

## QR code

Ogni commessa ha un QR code (SVG) che codifica l'URL `/commesse/{id}`.
Accessibile via `GET /commesse/{id}/qr-code` (ruoli: admin, accettatore).

Il **board marcatempo** (`/officina/marcatempo`) integra uno scanner QR tramite fotocamera
(libreria jsQR via CDN, richiede `getUserMedia` = HTTPS o localhost).

---

## Checklist

Template configurabili in **Impostazioni → Checklist** (admin).
I meccanici compilano la checklist da tablet su `/officina/checklist/{commessa}/{template}`.

Tipi di voce supportati: `si_no`, `numerico`, `testo_libero`, `foto_obbligatoria`.

Le foto delle checklist sono servite tramite Laravel (route autenticata) da
`storage/app/allegati/checklist/`.

---

## Aggiornare gli asset AdminLTE

Gli asset AdminLTE 3 sono serviti staticamente da `public/vendor/adminlte/`. Dopo un aggiornamento del pacchetto npm:

```bash
npm install admin-lte@^3
cp -r node_modules/admin-lte/dist public/vendor/adminlte/dist
cp -r node_modules/admin-lte/plugins public/vendor/adminlte/plugins
```
