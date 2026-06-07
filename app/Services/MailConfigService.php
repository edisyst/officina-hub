<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Config;

class MailConfigService
{
    /** Applica la configurazione SMTP dal DB a runtime */
    public function applica(): void
    {
        $host       = Setting::get('email_smtp_host');
        $port       = Setting::get('email_smtp_port', '587');
        $username   = Setting::get('email_smtp_username');
        $password   = Setting::get('email_smtp_password');
        $encryption = Setting::get('email_smtp_encryption', 'tls');
        $fromAddr   = Setting::get('email_from_address');
        $fromName   = Setting::get('email_from_name', config('app.name'));

        // Non sovrascrivere se la configurazione DB è vuota
        if (empty($host) || empty($fromAddr)) {
            return;
        }

        Config::set('mail.mailers.smtp.host', $host);
        Config::set('mail.mailers.smtp.port', (int) $port);
        Config::set('mail.mailers.smtp.username', $username);
        Config::set('mail.mailers.smtp.password', $password);
        Config::set('mail.mailers.smtp.encryption', $encryption === 'none' ? null : $encryption);
        Config::set('mail.from.address', $fromAddr);
        Config::set('mail.from.name', $fromName);
        Config::set('mail.default', 'smtp');
    }

    /** Verifica la connessione SMTP inviando una email di test */
    public function testConnessione(string $destinatario): array
    {
        $this->applica();

        try {
            \Illuminate\Support\Facades\Mail::raw(
                'Email di test dalla configurazione SMTP di Officina Hub.',
                function ($message) use ($destinatario) {
                    $message->to($destinatario)
                            ->subject('Test SMTP — Officina Hub');
                }
            );

            return ['successo' => true, 'messaggio' => 'Email inviata correttamente.'];
        } catch (\Throwable $e) {
            return ['successo' => false, 'messaggio' => $e->getMessage()];
        }
    }
}
