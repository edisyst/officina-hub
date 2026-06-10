<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $defaults = [
            'export_contabile_formato'                  => 'csv_generico',
            'export_contabile_codice_conto_vendite'     => '70000',
            'export_contabile_codice_conto_iva_vendite' => '26000',
            'export_contabile_codice_conto_clienti'     => '15000',
            'export_contabile_codice_conto_cassa'       => '10000',
            'export_contabile_codice_conto_banca'       => '11000',
            'conservazione_sostitutiva_abilitata'       => '0',
        ];

        foreach ($defaults as $key => $value) {
            Setting::firstOrCreate(['key' => $key], ['value' => $value]);
        }
    }

    public function down(): void
    {
        $keys = [
            'export_contabile_formato',
            'export_contabile_codice_conto_vendite',
            'export_contabile_codice_conto_iva_vendite',
            'export_contabile_codice_conto_clienti',
            'export_contabile_codice_conto_cassa',
            'export_contabile_codice_conto_banca',
            'conservazione_sostitutiva_abilitata',
        ];

        Setting::whereIn('key', $keys)->delete();
    }
};
