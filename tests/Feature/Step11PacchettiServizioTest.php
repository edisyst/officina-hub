<?php

namespace Tests\Feature;

use App\Actions\Commessa\ApplicaPacchettoAction;
use App\Actions\Commessa\GeneraNumeroProgressivoAction;
use App\Enums\StatoCommessa;
use App\Models\Articolo;
use App\Models\Cliente;
use App\Models\Commessa;
use App\Models\CommessaRiga;
use App\Models\PacchettoRiga;
use App\Models\PacchettoServizio;
use App\Models\TariffaManodopera;
use App\Models\User;
use App\Models\Veicolo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class Step11PacchettiServizioTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Cliente $cliente;
    private Veicolo $veicoloBenzina;
    private Veicolo $veicoloDiesel;
    private Commessa $commessa;

    protected function setUp(): void
    {
        parent::setUp();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (['admin', 'accettatore', 'meccanico', 'cassa'] as $r) {
            Role::create(['name' => $r, 'guard_name' => 'web']);
        }

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->cliente = Cliente::create([
            'tipo'    => 'fisica',
            'nome'    => 'Mario',
            'cognome' => 'Rossi',
        ]);

        $this->veicoloBenzina = Veicolo::create([
            'tipo'         => 'auto',
            'alimentazione'=> 'benzina',
            'targa'        => 'AA000AA',
            'marca'        => 'Fiat',
            'modello'      => 'Punto',
        ]);

        $this->veicoloDiesel = Veicolo::create([
            'tipo'         => 'auto',
            'alimentazione'=> 'diesel',
            'targa'        => 'BB111BB',
            'marca'        => 'Volkswagen',
            'modello'      => 'Golf',
        ]);

        $numero = app(GeneraNumeroProgressivoAction::class)->execute();
        $this->commessa = Commessa::create([
            'numero'              => $numero,
            'cliente_id'          => $this->cliente->id,
            'veicolo_id'          => $this->veicoloBenzina->id,
            'tipo'                => 'meccanica',
            'stato'               => StatoCommessa::Bozza,
            'descrizione_cliente' => 'Test',
            'km_ingresso'         => 10000,
            'data_ingresso'       => now(),
            'user_id'             => $this->admin->id,
        ]);
    }

    // --- TariffaManodopera ---

    public function test_crea_tariffa_manodopera(): void
    {
        $tariffa = TariffaManodopera::create([
            'codice'          => 'TEST-001',
            'descrizione'     => 'Test lavorazione',
            'categoria'       => 'Test',
            'minuti_standard' => 60,
            'prezzo_listino'  => 50.00,
            'iva_percentuale' => 22,
            'tipo_veicolo'    => 'entrambi',
            'attivo'          => true,
        ]);

        $this->assertDatabaseHas('tariffe_manodopera', ['codice' => 'TEST-001']);
        $this->assertEquals(1.0, $tariffa->ore_standard);
    }

    public function test_import_csv_tariffe(): void
    {
        $csv = "codice;descrizione;categoria;minuti_standard;prezzo_listino;iva_percentuale;tipo_veicolo\n";
        $csv .= "CSV-001;Cambio batteria;Elettrico;20;18;22;entrambi\n";
        $csv .= "CSV-002;Controllo freni;Freni;30;25;22;auto\n";

        // Simula il parsing CSV come fa il Livewire component
        $lines = explode("\n", trim($csv));
        $header = str_getcsv($lines[0], ';');
        $importate = 0;

        for ($i = 1; $i < count($lines); $i++) {
            $row = str_getcsv($lines[$i], ';');
            if (count($row) < 6) continue;

            [$codice, $descrizione, $categoria, $minuti, $prezzo, $iva, $tipoVeicolo] = array_pad($row, 7, 'entrambi');

            TariffaManodopera::updateOrCreate(
                ['codice' => trim(strtoupper($codice))],
                [
                    'descrizione'     => trim($descrizione),
                    'categoria'       => trim($categoria),
                    'minuti_standard' => (int) trim($minuti),
                    'prezzo_listino'  => (float) trim($prezzo),
                    'iva_percentuale' => (float) trim($iva) ?: 22,
                    'tipo_veicolo'    => in_array(trim($tipoVeicolo), ['auto', 'moto', 'entrambi']) ? trim($tipoVeicolo) : 'entrambi',
                    'attivo'          => true,
                ]
            );
            $importate++;
        }

        $this->assertEquals(2, $importate);
        $this->assertDatabaseHas('tariffe_manodopera', ['codice' => 'CSV-001', 'descrizione' => 'Cambio batteria']);
        $this->assertDatabaseHas('tariffe_manodopera', ['codice' => 'CSV-002', 'tipo_veicolo' => 'auto']);
    }

    // --- PacchettoServizio ---

    public function test_crea_pacchetto_con_righe(): void
    {
        $pacchetto = PacchettoServizio::create([
            'nome'         => 'Tagliando completo benzina',
            'tipo_commessa'=> 'entrambi',
            'tipo_veicolo' => 'auto',
            'alimentazione'=> 'benzina',
            'attivo'       => true,
            'ordinamento'  => 1,
        ]);

        $pacchetto->righe()->createMany([
            ['tipo' => 'manodopera', 'descrizione' => 'Cambio olio', 'quantita' => 0.5, 'prezzo_unitario' => 25, 'sconto_percentuale' => 0, 'iva_percentuale' => 22, 'ordinamento' => 0],
            ['tipo' => 'manodopera', 'descrizione' => 'Filtro olio', 'quantita' => 0.25, 'prezzo_unitario' => 10, 'sconto_percentuale' => 0, 'iva_percentuale' => 22, 'ordinamento' => 1],
            ['tipo' => 'nota', 'descrizione' => 'Controllo visivo incluso', 'quantita' => 1, 'prezzo_unitario' => 0, 'sconto_percentuale' => 0, 'iva_percentuale' => 22, 'ordinamento' => 2],
        ]);

        $this->assertDatabaseHas('pacchetti_servizio', ['nome' => 'Tagliando completo benzina']);
        $this->assertEquals(3, $pacchetto->righe()->count());

        // Verifica totale calcolato (nota non conta)
        $pacchetto->load('righe');
        $totale = $pacchetto->calcolaTotale();
        // (0.5 * 25 * 1.22) + (0.25 * 10 * 1.22) = 15.25 + 3.05 = 18.30
        $this->assertEqualsWithDelta(18.30, $totale, 0.01);
    }

    public function test_clona_pacchetto(): void
    {
        $originale = PacchettoServizio::create([
            'nome' => 'Tagliando 1.4', 'tipo_commessa' => 'entrambi', 'tipo_veicolo' => 'auto', 'alimentazione' => 'benzina', 'attivo' => true, 'ordinamento' => 1,
        ]);
        $originale->righe()->create(['tipo' => 'manodopera', 'descrizione' => 'Cambio olio', 'quantita' => 0.5, 'prezzo_unitario' => 25, 'sconto_percentuale' => 0, 'iva_percentuale' => 22, 'ordinamento' => 0]);

        // Simula clone
        $clone = $originale->replicate();
        $clone->nome       = $originale->nome . ' (copia)';
        $clone->utilizzi   = 0;
        $clone->ordinamento = 2;
        $clone->push();

        foreach ($originale->righe as $riga) {
            $cloneRiga = $riga->replicate();
            $cloneRiga->pacchetto_servizio_id = $clone->id;
            $cloneRiga->save();
        }

        $this->assertDatabaseHas('pacchetti_servizio', ['nome' => 'Tagliando 1.4 (copia)']);
        $this->assertEquals(1, $clone->righe()->count());
        // Modifica indipendente
        $clone->update(['nome' => 'Tagliando 2.0']);
        $this->assertEquals('Tagliando 1.4', $originale->fresh()->nome);
    }

    // --- Compatibilità pacchetto ---

    public function test_compatibilita_filtro_alimentazione(): void
    {
        $pacchettoSoloBenzina = PacchettoServizio::create([
            'nome' => 'Tagliando benzina', 'tipo_commessa' => 'entrambi', 'tipo_veicolo' => 'auto', 'alimentazione' => 'benzina', 'attivo' => true, 'ordinamento' => 1,
        ]);

        $pacchettoTutte = PacchettoServizio::create([
            'nome' => 'Tagliando universale', 'tipo_commessa' => 'entrambi', 'tipo_veicolo' => 'auto', 'alimentazione' => 'tutte', 'attivo' => true, 'ordinamento' => 2,
        ]);

        $commessaDiesel = Commessa::create([
            'numero'              => 'TEST-002',
            'cliente_id'          => $this->cliente->id,
            'veicolo_id'          => $this->veicoloDiesel->id,
            'tipo'                => 'meccanica',
            'stato'               => StatoCommessa::Bozza,
            'descrizione_cliente' => 'Test diesel',
            'km_ingresso'         => 5000,
            'data_ingresso'       => now(),
            'user_id'             => $this->admin->id,
        ]);
        $commessaDiesel->load('veicolo');

        // Pacchetto solo benzina non compatibile con commessa diesel
        $this->assertFalse($pacchettoSoloBenzina->isCompatibile($commessaDiesel));

        // Pacchetto "tutte" compatibile con entrambe
        $this->assertTrue($pacchettoTutte->isCompatibile($commessaDiesel));
        $this->commessa->load('veicolo');
        $this->assertTrue($pacchettoTutte->isCompatibile($this->commessa));
    }

    // --- ApplicaPacchettoAction ---

    public function test_applica_pacchetto_crea_righe_e_incrementa_counter(): void
    {
        $pacchetto = PacchettoServizio::create([
            'nome' => 'Kit freni', 'tipo_commessa' => 'entrambi', 'tipo_veicolo' => 'auto', 'alimentazione' => 'tutte', 'attivo' => true, 'ordinamento' => 1, 'utilizzi' => 5,
        ]);

        $righeInput = [
            ['tipo' => 'manodopera', 'descrizione' => 'Sostituzione pastiglie', 'articolo_id' => null, 'tariffa_manodopera_id' => null, 'quantita' => 0.75, 'prezzo_unitario' => 40, 'sconto_percentuale' => 0, 'iva_percentuale' => 22],
            ['tipo' => 'nota', 'descrizione' => 'Controllo incluso', 'articolo_id' => null, 'tariffa_manodopera_id' => null, 'quantita' => 1, 'prezzo_unitario' => 0, 'sconto_percentuale' => 0, 'iva_percentuale' => 22],
        ];

        $action = app(ApplicaPacchettoAction::class);
        $righeCreate = $action->execute($this->commessa, $pacchetto, $righeInput);

        $this->assertCount(2, $righeCreate);
        $this->assertDatabaseHas('commessa_righe', [
            'commessa_id'          => $this->commessa->id,
            'tipo'                 => 'manodopera',
            'pacchetto_servizio_id'=> $pacchetto->id,
        ]);
        $this->assertDatabaseHas('commessa_righe', [
            'commessa_id' => $this->commessa->id,
            'tipo'        => 'nota',
        ]);

        // Counter deve essere incrementato
        $this->assertEquals(6, $pacchetto->fresh()->utilizzi);
    }

    public function test_applica_pacchetto_preventivo_rapido(): void
    {
        $pacchetto = PacchettoServizio::create([
            'nome' => 'Tagliando rapido', 'tipo_commessa' => 'entrambi', 'tipo_veicolo' => 'entrambi', 'alimentazione' => 'tutte', 'attivo' => true, 'ordinamento' => 1,
        ]);
        $pacchetto->righe()->create([
            'tipo' => 'manodopera', 'descrizione' => 'Cambio olio', 'quantita' => 0.5, 'prezzo_unitario' => 25, 'sconto_percentuale' => 0, 'iva_percentuale' => 22, 'ordinamento' => 0,
        ]);

        // Simula il comportamento di FormCommessa: crea la commessa poi applica
        $righeInput = [
            ['tipo' => 'manodopera', 'descrizione' => 'Cambio olio', 'articolo_id' => null, 'tariffa_manodopera_id' => null, 'quantita' => 0.5, 'prezzo_unitario' => 25, 'sconto_percentuale' => 0, 'iva_percentuale' => 22],
        ];

        $numero = app(GeneraNumeroProgressivoAction::class)->execute();
        $nuovaCommessa = Commessa::create([
            'numero'              => $numero,
            'cliente_id'          => $this->cliente->id,
            'veicolo_id'          => $this->veicoloBenzina->id,
            'tipo'                => 'meccanica',
            'stato'               => StatoCommessa::Bozza,
            'descrizione_cliente' => 'Tagliando',
            'km_ingresso'         => 50000,
            'data_ingresso'       => now(),
            'user_id'             => $this->admin->id,
        ]);

        app(ApplicaPacchettoAction::class)->execute($nuovaCommessa, $pacchetto, $righeInput);

        $this->assertEquals(1, $nuovaCommessa->righe()->count());
        $this->assertEquals(1, $pacchetto->fresh()->utilizzi);
    }

    public function test_tariffa_collegata_a_riga_commessa(): void
    {
        $tariffa = TariffaManodopera::create([
            'codice' => 'FRE-TEST', 'descrizione' => 'Pastiglie anteriori', 'categoria' => 'Freni',
            'minuti_standard' => 45, 'prezzo_listino' => 40.00, 'iva_percentuale' => 22, 'tipo_veicolo' => 'auto', 'attivo' => true,
        ]);

        $riga = $this->commessa->righe()->create([
            'tipo'                  => 'manodopera',
            'tariffa_manodopera_id' => $tariffa->id,
            'descrizione'           => $tariffa->descrizione,
            'quantita'              => 0.75,
            'prezzo_unitario'       => 40.00,
            'sconto_percentuale'    => 0,
            'iva_percentuale'       => 22,
            'ordinamento'           => 1,
        ]);

        $this->assertDatabaseHas('commessa_righe', ['tariffa_manodopera_id' => $tariffa->id]);
        $this->assertEquals($tariffa->id, $riga->fresh()->tariffa_manodopera_id);
    }
}
