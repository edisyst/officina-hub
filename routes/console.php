<?php

use App\Jobs\AggiornaPunteggiCrm;
use App\Jobs\InviaAuguriCompleanno;
use App\Jobs\InviaRichiamiScadenza;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Richiami scadenze automatici ogni giorno alle 08:00
Schedule::job(new InviaRichiamiScadenza)->dailyAt('08:00')->name('richiami-scadenze');

// CRM: aggiorna punteggi e segmenti clienti ogni notte alle 03:30
Schedule::job(new AggiornaPunteggiCrm)->dailyAt('03:30')->name('crm-punteggi');

// CRM: auguri di compleanno ogni giorno alle 09:00
Schedule::job(new InviaAuguriCompleanno)->dailyAt('09:00')->name('crm-compleanni');

// Backup automatico ogni notte alle 02:00
Schedule::command('backup:run')->dailyAt('02:00')->name('backup-run');
Schedule::command('backup:clean')->dailyAt('02:30')->name('backup-clean');
Schedule::command('backup:monitor')->dailyAt('03:00')->name('backup-monitor');
