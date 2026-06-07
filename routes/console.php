<?php

use App\Jobs\InviaRichiamiScadenza;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Richiami scadenze automatici ogni giorno alle 08:00
Schedule::job(new InviaRichiamiScadenza)->dailyAt('08:00')->name('richiami-scadenze');

// Backup automatico ogni notte alle 02:00
Schedule::command('backup:run')->dailyAt('02:00')->name('backup-run');
Schedule::command('backup:clean')->dailyAt('02:30')->name('backup-clean');
Schedule::command('backup:monitor')->dailyAt('03:00')->name('backup-monitor');
