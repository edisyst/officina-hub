<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $settings = [
            'sconto_compleanno_percentuale' => '10',
            'template_email_compleanno' => implode("\n", [
                'Oggetto: Auguri {{NOME_CLIENTE}} da {{NOME_OFFICINA}}!',
                '',
                'Gentile {{NOME_CLIENTE}},',
                'in occasione del suo compleanno, tutto il team di {{NOME_OFFICINA}}',
                'Le augura una splendida giornata.',
                '',
                'Come piccolo omaggio, Le offriamo il {{SCONTO_COMPLEANNO}}% di sconto',
                'sul prossimo intervento, valido per tutto il mese del suo compleanno.',
                '',
                '{{NOME_OFFICINA}} — {{TELEFONO_OFFICINA}}',
            ]),
        ];

        foreach ($settings as $key => $value) {
            Setting::firstOrCreate(['key' => $key], ['value' => $value]);
        }
    }

    public function down(): void
    {
        Setting::whereIn('key', [
            'sconto_compleanno_percentuale',
            'template_email_compleanno',
        ])->delete();
    }
};
