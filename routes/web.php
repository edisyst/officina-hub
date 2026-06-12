<?php

use App\Http\Controllers\Api\AppuntamentiController;
use App\Http\Controllers\Api\CortesiaDisponibilitaController;
use App\Http\Controllers\DepositoController;
use App\Http\Controllers\Api\DviUploadController;
use App\Http\Controllers\Api\MenuBadgesController;
use App\Http\Controllers\Api\RisorseAgendaController;
use App\Http\Controllers\CarrozzeriaController;
use App\Http\Controllers\CommessaController;
use App\Http\Controllers\CortesiaController;
use App\Http\Controllers\DviController;
use App\Http\Controllers\FatturazioneController;
use App\Http\Controllers\PdfController;
use App\Http\Controllers\PortaleClienteController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\QrCodeController;
use App\Http\Controllers\SettingsController;
use App\Models\ChecklistTemplate;
use App\Models\Commessa;
use App\Models\DviIspezione;
use App\Models\PrestitoCortesia;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/', fn() => redirect()->route('login'));

Route::middleware(['auth', 'verified'])->group(function () {

    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    // Analytics
    Route::get('/analytics/meccanici', fn() => view('analytics.meccanici'))
        ->middleware('role:admin')
        ->name('analytics.meccanici');

    Route::get('/analytics/marginalita', fn() => view('analytics.marginalita'))
        ->middleware('role:admin|cassa')
        ->name('analytics.marginalita');

    Route::get('/analytics/commesse', fn() => view('analytics.commesse'))
        ->middleware('role:admin|accettatore|cassa')
        ->name('analytics.commesse');

    // Alias rotta dashboard KPI
    Route::get('/analytics/dashboard', fn() => view('analytics.dashboard'))
        ->name('analytics.dashboard');

    // Clienti — accesso limitato ai ruoli con viewAny
    Route::get('/clienti', fn() => view('clienti.index'))
        ->middleware('can:viewAny,App\Models\Cliente')
        ->name('clienti.index');

    Route::get('/clienti/{cliente}', fn($cliente) => view('clienti.show', ['clienteId' => $cliente]))
        ->middleware('can:viewAny,App\Models\Cliente')
        ->name('clienti.show');

    // Veicoli — accesso limitato ai ruoli con viewAny
    Route::get('/veicoli', fn() => view('veicoli.index'))
        ->middleware('can:viewAny,App\Models\Veicolo')
        ->name('veicoli.index');

    Route::get('/veicoli/{veicolo}', fn($veicolo) => view('veicoli.show', ['veicoloId' => $veicolo]))
        ->middleware('can:viewAny,App\Models\Veicolo')
        ->name('veicoli.show');

    // Commesse
    Route::get('/commesse', fn() => view('commesse.index'))
        ->middleware('can:viewAny,App\Models\Commessa')
        ->name('commesse.index');

    Route::get('/commesse/create', fn() => view('commesse.create'))
        ->middleware('can:create,App\Models\Commessa')
        ->name('commesse.create');

    Route::get('/commesse/{commessa}', fn($commessa) => view('commesse.show', ['commessaId' => $commessa]))
        ->middleware('can:viewAny,App\Models\Commessa')
        ->name('commesse.show');

    // PDF download (controller thin)
    Route::get('/commesse/{commessa}/pdf/scheda', [PdfController::class, 'scheda'])->name('pdf.scheda');
    Route::get('/commesse/{commessa}/pdf/preventivo', [PdfController::class, 'preventivo'])->name('pdf.preventivo');
    Route::get('/commesse/{commessa}/pdf/carrozzeria', [CarrozzeriaController::class, 'schedaCarrozzeria'])->name('pdf.carrozzeria');

    // Carrozzeria: download ZIP foto danni
    Route::get('/commesse/{commessa}/foto-danni/zip', [CarrozzeriaController::class, 'downloadZipFoto'])->name('carrozzeria.foto.zip');

    // QR code commessa (admin + accettatore)
    Route::get('/commesse/{commessa}/qr-code', [QrCodeController::class, 'commessa'])
        ->middleware('role:admin|accettatore')
        ->name('commesse.qr-code');

    // Allegati download
    Route::get('/allegati/{allegato}/download', [CommessaController::class, 'downloadAllegato'])->name('allegati.download');

    // Agenda (admin + accettatore)
    Route::get('/agenda', fn() => view('agenda.index'))
        ->middleware('can:viewAny,App\Models\Appuntamento')
        ->name('agenda');

    // Marcatempo tablet (admin + meccanico)
    Route::get('/officina/marcatempo', fn() => view('marcatempo.index'))
        ->middleware('role:admin|meccanico')
        ->name('marcatempo');

    // Checklist compilazione tablet
    Route::get('/officina/checklist/{commessa}/{template}', function (Commessa $commessa, ChecklistTemplate $template) {
        return view('officina.checklist', compact('commessa', 'template'));
    })
        ->middleware('role:admin|meccanico')
        ->name('officina.checklist');

    // Foto checklist — serve file autenticato
    Route::get('/allegati/checklist/{filename}', function (string $filename) {
        $path = 'allegati/checklist/' . $filename;
        abort_unless(Storage::disk('local')->exists($path), 404);
        return response()->file(Storage::disk('local')->path($path));
    })
        ->middleware('role:admin|meccanico|accettatore')
        ->name('checklist.foto');

    // Foto danni carrozzeria — serve file autenticato
    Route::get('/allegati/foto-danni/{filename}', function (string $filename) {
        $path = 'allegati/foto-danni/' . $filename;
        abort_unless(Storage::disk('local')->exists($path), 404);
        return response()->file(Storage::disk('local')->path($path));
    })->name('allegati.foto-danni');

    // Allegato perizia PDF — serve file autenticato
    Route::get('/allegati/perizie/{filename}', function (string $filename) {
        $path = 'allegati/perizie/' . $filename;
        abort_unless(Storage::disk('local')->exists($path), 404);
        return response()->file(Storage::disk('local')->path($path), ['Content-Type' => 'application/pdf']);
    })->name('allegati.perizia');

    // Endpoint JSON per FullCalendar — usa sessione web (non API token)
    Route::middleware('throttle:api-internal')->group(function () {
        Route::get('/api/appuntamenti', [AppuntamentiController::class, 'index'])->name('api.appuntamenti');
        Route::get('/api/risorse-agenda', [RisorseAgendaController::class, 'index'])->name('api.risorse-agenda');
        Route::get('/api/menu-badges', [MenuBadgesController::class, 'index'])->name('api.menu-badges');
        Route::get('/api/cortesia/disponibilita', [CortesiaDisponibilitaController::class, 'index'])->name('api.cortesia.disponibilita');
    });

    // Veicoli di cortesia
    Route::middleware('role:admin|accettatore')->group(function () {
        Route::get('/cortesia', fn() => view('cortesia.index'))->name('cortesia.index');
        Route::get('/cortesia/consegna', fn() => view('cortesia.consegna'))->name('cortesia.consegna');
        Route::get('/cortesia/consegna/commessa/{commessa}', function (Commessa $commessa) {
            return view('cortesia.consegna', ['commessaId' => $commessa->id]);
        })->name('cortesia.consegna.commessa');
        Route::get('/cortesia/prestiti/{prestito}/rientro', function (PrestitoCortesia $prestito) {
            return view('cortesia.rientro', ['prestito' => $prestito]);
        })->name('cortesia.rientro');
    });

    Route::middleware('role:admin')->group(function () {
        Route::get('/cortesia/flotta', fn() => view('cortesia.flotta'))->name('cortesia.flotta');
    });

    Route::middleware('role:admin|accettatore|cassa')->group(function () {
        Route::get('/cortesia/report', fn() => view('cortesia.report'))->name('cortesia.report');
    });

    // PDF contratto cortesia
    Route::get('/cortesia/prestiti/{prestito}/contratto', [CortesiaController::class, 'contrattoPdf'])
        ->middleware('role:admin|accettatore')
        ->name('cortesia.contratto');

    // DVI — creazione e gestione (staff)
    Route::middleware('role:admin|meccanico|accettatore')->group(function () {
        Route::get('/officina/dvi/{commessa}/nuova', function (Commessa $commessa) {
            return view('officina.dvi', compact('commessa'));
        })->name('dvi.nuova');

        Route::get('/officina/dvi/{ispezione}/anteprima', [DviController::class, 'anteprima'])
            ->name('dvi.anteprima');
    });

    // DVI media — serve file autenticato per staff
    Route::get('/dvi/media/{media}', [DviController::class, 'serveMedia'])
        ->middleware('auth')
        ->name('dvi.media');

    Route::get('/dvi/media/{media}/thumb', [DviController::class, 'serveThumbnail'])
        ->middleware('auth')
        ->name('dvi.media.thumb');

    // DVI chunked upload video
    Route::post('/api/dvi/upload-chunk', [DviUploadController::class, 'uploadChunk'])
        ->middleware(['auth', 'throttle:api-internal'])
        ->name('api.dvi.upload-chunk');

    // Magazzino
    Route::middleware('can:viewAny,App\Models\Articolo')->group(function () {
        Route::get('/magazzino/articoli', fn() => view('magazzino.articoli'))
            ->name('magazzino.articoli');
        Route::get('/magazzino/articoli/{articolo}', fn($articolo) => view('magazzino.articolo', ['articoloId' => $articolo]))
            ->name('magazzino.articoli.show');
        Route::get('/magazzino/report', fn() => view('magazzino.report'))
            ->middleware('role:admin|cassa')
            ->name('magazzino.report');
    });

    Route::middleware('can:viewAny,App\Models\Fornitore')->group(function () {
        Route::get('/magazzino/fornitori', fn() => view('magazzino.fornitori'))
            ->name('magazzino.fornitori');
    });

    Route::middleware('role:admin')->group(function () {
        Route::get('/magazzino/movimenti', fn() => view('magazzino.movimenti'))
            ->name('magazzino.movimenti');
        Route::get('/magazzino/categorie', fn() => view('magazzino.categorie'))
            ->name('magazzino.categorie');
    });

    // Scadenziario (admin + accettatore)
    Route::middleware('role:admin|accettatore')->group(function () {
        Route::get('/scadenziario', fn() => view('scadenziario.index'))
            ->name('scadenziario.index');
    });

    // Audit log (solo admin)
    Route::get('/audit-log', fn() => view('admin.audit-log'))
        ->middleware('role:admin')
        ->name('audit-log');

    // Impostazioni (solo admin)
    Route::middleware('role:admin')->group(function () {
        Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');
        Route::post('/settings/logo', [SettingsController::class, 'uploadLogo'])->name('settings.logo');
        Route::get('/impostazioni/ponti', fn() => view('impostazioni.ponti'))->name('impostazioni.ponti');
        Route::get('/impostazioni/checklist', fn() => view('impostazioni.checklist'))->name('impostazioni.checklist');
        Route::get('/impostazioni/email', fn() => view('impostazioni.email'))->name('impostazioni.email');
        Route::get('/impostazioni/compagnie', fn() => view('impostazioni.compagnie'))->name('impostazioni.compagnie');
        Route::get('/impostazioni/dvi-categorie', fn() => view('impostazioni.dvi-categorie'))->name('impostazioni.dvi-categorie');
        Route::get('/impostazioni/tariffe', fn() => view('impostazioni.tariffe'))->name('impostazioni.tariffe');
        Route::get('/impostazioni/pacchetti', fn() => view('impostazioni.pacchetti'))->name('impostazioni.pacchetti');
        Route::get('/analytics/pacchetti', fn() => view('analytics.pacchetti'))->name('analytics.pacchetti');

        // Test connessione lookup targa
        Route::get('/impostazioni/lookup-targa-test', function () {
            $service = app(\App\Services\LookupTarga\LookupTargaService::class);
            if (! $service->isAbilitato()) {
                return response('<pre>Lookup targa disabilitato nelle impostazioni.</pre>');
            }
            $risultato = $service->cercaSenzaCache('AB123CD');
            return response('<pre>' . json_encode($risultato, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . '</pre>');
        })->name('impostazioni.lookup-test');
    });

    // Contabilità (admin + cassa)
    Route::middleware('role:admin|cassa')->group(function () {
        Route::get('/contabilita/prima-nota', fn() => view('contabilita.prima-nota'))
            ->name('contabilita.prima-nota');
        Route::get('/contabilita/riepilogo', fn() => view('contabilita.riepilogo'))
            ->name('contabilita.riepilogo');
        Route::get('/contabilita/export-sdi-batch', fn() => view('contabilita.export-sdi-batch'))
            ->name('contabilita.export-sdi-batch');
    });

    // Acquisti (admin + accettatore per ordini; admin + cassa per fatture)
    Route::middleware('role:admin|accettatore')->group(function () {
        Route::get('/acquisti/ordini', fn() => view('acquisti.ordini'))
            ->name('acquisti.ordini');
        Route::get('/acquisti/ordini/crea', fn() => view('acquisti.dettaglio-ordine'))
            ->name('acquisti.ordini.create');
        Route::get('/acquisti/ordini/{ordine}', fn($ordine) => view('acquisti.dettaglio-ordine', ['ordineId' => $ordine]))
            ->name('acquisti.ordini.show');
        Route::get('/acquisti/ordini/{ordine}/ricevi', fn($ordine) => view('acquisti.ricevi', ['ordineId' => $ordine]))
            ->name('acquisti.ordini.ricevi');
        Route::get('/acquisti/genera-ordini', fn() => view('acquisti.genera-ordini'))
            ->name('acquisti.genera-ordini');
    });

    Route::middleware('role:admin|cassa')->group(function () {
        Route::get('/acquisti/fatture', fn() => view('acquisti.fatture'))
            ->name('acquisti.fatture');
    });

    // Fatturazione (admin + cassa)
    Route::middleware('role:admin|cassa')->group(function () {
        Route::get('/fatturazione/documenti', fn() => view('fatturazione.documenti'))
            ->name('fatturazione.documenti');
        Route::get('/fatturazione/documenti/{documento}', fn($documento) => view('fatturazione.dettaglio', ['documentoId' => $documento]))
            ->name('fatturazione.documenti.show');
        Route::get('/fatturazione/scadenziario', fn() => view('fatturazione.scadenziario'))
            ->name('fatturazione.scadenziario');
        Route::get('/fatturazione/registro-iva', fn() => view('fatturazione.registro-iva'))
            ->name('fatturazione.registro-iva');

        // Download file (controller thin)
        Route::get('/fatturazione/documenti/{documento}/xml', [FatturazioneController::class, 'scaricaXml'])
            ->name('fatturazione.documenti.xml');
        Route::get('/fatturazione/documenti/{documento}/zip', [FatturazioneController::class, 'scaricaZip'])
            ->name('fatturazione.documenti.zip');
        Route::get('/fatturazione/documenti/{documento}/pdf', [FatturazioneController::class, 'scaricaPdf'])
            ->name('fatturazione.documenti.pdf');
    });

    // Deposito pneumatici
    Route::middleware('role:admin|accettatore')->group(function () {
        Route::get('/deposito', fn() => view('deposito.index'))
            ->name('deposito.index');
        Route::get('/deposito/commessa/{commessa}', fn($commessa) => view('deposito.commessa', ['commessaId' => $commessa]))
            ->name('deposito.commessa');
        Route::get('/deposito/cambio-stagionale', fn() => view('deposito.cambio-stagionale'))
            ->name('deposito.cambio-stagionale');
    });

    Route::middleware('role:admin|accettatore|cassa')->group(function () {
        Route::get('/deposito/report', fn() => view('deposito.report'))
            ->name('deposito.report');
    });

    // Etichette PDF deposito (admin + accettatore + meccanico)
    Route::middleware('role:admin|accettatore|meccanico')->group(function () {
        Route::get('/deposito/etichetta/{pneumatico}', [DepositoController::class, 'etichetta'])
            ->name('deposito.etichetta');
        Route::post('/deposito/etichette-multiple', [DepositoController::class, 'etichettaMultiple'])
            ->name('deposito.etichette-multiple');
        Route::get('/deposito/qr/{codice}', [DepositoController::class, 'cercaQr'])
            ->name('deposito.qr');
    });

    // Garanzie
    Route::middleware('role:admin|cassa')->group(function () {
        Route::get('/garanzie/report', fn() => view('garanzie.report'))->name('garanzie.report');
    });

    // Case madri (solo admin, in impostazioni)
    Route::middleware('role:admin')->group(function () {
        Route::get('/impostazioni/case-madri', fn() => view('impostazioni.case-madri'))->name('impostazioni.case-madri');
    });

    // CRM
    Route::middleware('role:admin')->group(function () {
        Route::get('/crm/dashboard', fn() => view('crm.dashboard'))->name('crm.dashboard');
        Route::get('/crm/campagne', fn() => view('crm.campagne'))->name('crm.campagne');
    });

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Portale cliente — URL firmato, nessun login richiesto
Route::get('/cliente/{token}', [PortaleClienteController::class, 'show'])
    ->name('cliente.portale');

// DVI portale cliente — token opaco, nessun login richiesto
Route::get('/dvi/{token}', [DviController::class, 'portaleCliente'])->name('dvi.portale');
Route::post('/dvi/{token}/risposte', [DviController::class, 'salvaRisposte'])->name('dvi.salva-risposte');
Route::get('/dvi/{token}/conferma', [DviController::class, 'conferma'])->name('dvi.conferma');

// DVI media per il cliente (token-based, senza login)
Route::get('/dvi/{token}/media/{media}', [DviController::class, 'serveMediaCliente'])
    ->name('dvi.media.cliente');

require __DIR__.'/auth.php';
