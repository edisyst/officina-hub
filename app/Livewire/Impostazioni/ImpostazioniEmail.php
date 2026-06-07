<?php

namespace App\Livewire\Impostazioni;

use App\Models\Setting;
use App\Services\EmailTemplateService;
use App\Services\MailConfigService;
use Livewire\Component;

class ImpostazioniEmail extends Component
{
    // Campi SMTP
    public string $smtpHost       = '';
    public string $smtpPort       = '587';
    public string $smtpUsername   = '';
    public string $smtpPassword   = '';
    public string $smtpEncryption = 'tls';
    public string $fromAddress    = '';
    public string $fromName       = '';
    public bool   $abilitato      = false;

    // Template
    public string $templateAccettazione      = '';
    public string $templateCompletata        = '';
    public string $templateConsegnata        = '';
    public string $templateRichiamoScadenza  = '';

    // Promemoria giorni
    public int $promemoriaRevisione     = 30;
    public int $promemoriaTagliando     = 30;
    public int $promemoriaAssicurazione = 30;

    // Test connessione
    public string $emailTest     = '';
    public string $esito         = '';
    public string $esitoTipo     = '';
    public bool   $testInCorso   = false;

    protected function rules(): array
    {
        return [
            'smtpHost'       => 'nullable|string|max:255',
            'smtpPort'       => 'nullable|integer|min:1|max:65535',
            'smtpUsername'   => 'nullable|string|max:255',
            'smtpPassword'   => 'nullable|string|max:255',
            'smtpEncryption' => 'in:tls,ssl,none',
            'fromAddress'    => 'nullable|email|max:255',
            'fromName'       => 'nullable|string|max:255',
        ];
    }

    public function mount(): void
    {
        $this->smtpHost       = Setting::get('email_smtp_host', '');
        $this->smtpPort       = Setting::get('email_smtp_port', '587');
        $this->smtpUsername   = Setting::get('email_smtp_username', '');
        // La password NON viene popolata nel form per sicurezza
        $this->smtpEncryption = Setting::get('email_smtp_encryption', 'tls');
        $this->fromAddress    = Setting::get('email_from_address', '');
        $this->fromName       = Setting::get('email_from_name', '');
        $this->abilitato      = (bool) Setting::get('notifiche_email_abilitato', '0');

        $this->templateAccettazione     = Setting::get('template_email_accettazione', '');
        $this->templateCompletata       = Setting::get('template_email_completata', '');
        $this->templateConsegnata       = Setting::get('template_email_consegnata', '');
        $this->templateRichiamoScadenza = Setting::get('template_email_richiamo_scadenza', '');

        $this->promemoriaRevisione     = (int) Setting::get('promemoria_revisione_giorni', 30);
        $this->promemoriaTagliando     = (int) Setting::get('promemoria_tagliando_giorni', 30);
        $this->promemoriaAssicurazione = (int) Setting::get('promemoria_assicurazione_giorni', 30);

        $this->emailTest = Setting::get('officina_email', '');
    }

    public function salvaSmtp(): void
    {
        $this->validate();

        Setting::set('email_smtp_host', $this->smtpHost);
        Setting::set('email_smtp_port', $this->smtpPort);
        Setting::set('email_smtp_username', $this->smtpUsername);
        if ($this->smtpPassword !== '') {
            Setting::set('email_smtp_password', $this->smtpPassword);
        }
        Setting::set('email_smtp_encryption', $this->smtpEncryption);
        Setting::set('email_from_address', $this->fromAddress);
        Setting::set('email_from_name', $this->fromName);
        Setting::set('notifiche_email_abilitato', $this->abilitato ? '1' : '0');

        Setting::set('promemoria_revisione_giorni', (string) $this->promemoriaRevisione);
        Setting::set('promemoria_tagliando_giorni', (string) $this->promemoriaTagliando);
        Setting::set('promemoria_assicurazione_giorni', (string) $this->promemoriaAssicurazione);

        $this->esito     = 'Impostazioni SMTP salvate.';
        $this->esitoTipo = 'success';
        $this->smtpPassword = ''; // svuota sempre dopo il salvataggio
    }

    public function salvaTemplate(): void
    {
        Setting::set('template_email_accettazione', $this->templateAccettazione);
        Setting::set('template_email_completata', $this->templateCompletata);
        Setting::set('template_email_consegnata', $this->templateConsegnata);
        Setting::set('template_email_richiamo_scadenza', $this->templateRichiamoScadenza);

        $this->esito     = 'Template email salvati.';
        $this->esitoTipo = 'success';
    }

    public function testConnessione(): void
    {
        $this->validate(['emailTest' => 'required|email']);

        $this->testInCorso = true;
        $this->esito = '';

        // Salva temporaneamente i valori correnti nel form
        if ($this->smtpPassword !== '') {
            Setting::set('email_smtp_password', $this->smtpPassword);
        }
        Setting::set('email_smtp_host', $this->smtpHost);
        Setting::set('email_smtp_port', $this->smtpPort);
        Setting::set('email_smtp_username', $this->smtpUsername);
        Setting::set('email_smtp_encryption', $this->smtpEncryption);
        Setting::set('email_from_address', $this->fromAddress);
        Setting::set('email_from_name', $this->fromName);

        $risultato = app(MailConfigService::class)->testConnessione($this->emailTest);

        $this->esito     = $risultato['messaggio'];
        $this->esitoTipo = $risultato['successo'] ? 'success' : 'danger';
        $this->testInCorso = false;
    }

    public function variabiliCommessa(): array
    {
        return EmailTemplateService::variabiliCommessa();
    }

    public function variabiliScadenza(): array
    {
        return EmailTemplateService::variabiliScadenza();
    }

    public function render()
    {
        return view('livewire.impostazioni.impostazioni-email')
            ->layout('layouts.app', ['title' => 'Impostazioni Email']);
    }
}
