<?php

namespace Tests\Feature\Analytics;

use App\Services\Analytics\CsvExportService;
use Tests\TestCase;

class CsvExportServiceTest extends TestCase
{
    public function test_esporta_aggiunge_bom_utf8(): void
    {
        $service = new CsvExportService();

        $response = $service->esporta(
            ['Col1', 'Col2'],
            [['Valore 1', 100.50]],
            'test'
        );

        $content = $response->getContent();

        // BOM UTF-8
        $this->assertEquals("\xEF\xBB\xBF", substr($content, 0, 3));
    }

    public function test_esporta_intestazioni_e_righe(): void
    {
        $service = new CsvExportService();

        $response = $service->esporta(
            ['Nome', 'Totale'],
            [['Mario Rossi', 1234.56]],
            'test'
        );

        $content = $response->getContent();
        $lines   = explode("\r\n", substr($content, 3)); // salta BOM

        $this->assertStringContainsString('"Nome"', $lines[0]);
        $this->assertStringContainsString('"Totale"', $lines[0]);
        $this->assertStringContainsString('"Mario Rossi"', $lines[1]);
        $this->assertStringContainsString('1234,56', $lines[1]); // separatore decimale italiano
    }

    public function test_esporta_escapa_virgolette_doppie(): void
    {
        $service = new CsvExportService();

        $response = $service->esporta(
            ['Nome'],
            [['Valore "con" virgolette']],
            'test'
        );

        $content = $response->getContent();
        // Le virgolette doppie devono essere raddoppiate nel CSV
        $this->assertStringContainsString('""con""', $content);
    }

    public function test_content_type_corretto(): void
    {
        $service  = new CsvExportService();
        $response = $service->esporta(['A'], [['1']], 'test');

        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
    }
}
