<?php

namespace Tests\Feature;

use App\Actions\Fatturazione\GeneraFatturaDoppiaAction;
use App\Enums\StatoCommessa;
use App\Enums\TipoCommessa;
use App\Enums\TipoEmissione;
use App\Enums\TipoSinistro;
use App\Models\Cliente;
use App\Models\Commessa;
use App\Models\CommessaRiga;
use App\Models\CompagniaAssicurativa;
use App\Models\Documento;
use App\Models\Perizia;
use App\Models\Sinistro;
use App\Models\User;
use App\Models\Veicolo;
use App\Services\NumerazioneService;
use Database\Seeders\RuoliSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DoppiaFatturazioneTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Commessa $commessa;
    protected Sinistro $sinistro;
    protected Perizia $perizia;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RuoliSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $cliente = Cliente::create([
            'tipo'           => 'fisica',
            'nome'           => 'Mario',
            'cognome'        => 'Rossi',
            'codice_fiscale' => 'RSSMRA80A01H501Z',
            'email'          => 'mario@test.it',
        ]);

        $veicolo = Veicolo::create([
            'marca'   => 'Fiat',
            'modello' => 'Panda',
            'tipo'    => 'auto',
        ]);
        $veicolo->clienti()->attach($cliente->id);

        $this->commessa = Commessa::create([
            'numero'              => 'C/2026/001',
            'cliente_id'          => $cliente->id,
            'veicolo_id'          => $veicolo->id,
            'tipo'                => TipoCommessa::Carrozzeria,
            'stato'               => StatoCommessa::Consegnata,
            'data_ingresso'       => now(),
            'user_id'             => $this->admin->id,
            'descrizione_cliente' => 'Danno carrozzeria test',
        ]);

        // Righe commessa: totale imponibile = 1000 + 500 = 1500 (IVA 22%)
        CommessaRiga::create([
            'commessa_id'       => $this->commessa->id,
            'tipo'              => 'manodopera',
            'descrizione'       => 'Ripristino carrozzeria',
            'quantita'          => 1,
            'prezzo_unitario'   => 1000,
            'sconto_percentuale'=> 0,
            'iva_percentuale'   => 22,
            'ordinamento'       => 0,
        ]);
        CommessaRiga::create([
            'commessa_id'       => $this->commessa->id,
            'tipo'              => 'articolo',
            'descrizione'       => 'Verniciatura paraurti',
            'quantita'          => 1,
            'prezzo_unitario'   => 500,
            'sconto_percentuale'=> 0,
            'iva_percentuale'   => 22,
            'ordinamento'       => 1,
        ]);

        $compagnia = CompagniaAssicurativa::create(['nome' => 'Test Assicurazioni']);

        $this->sinistro = Sinistro::create([
            'commessa_id'              => $this->commessa->id,
            'compagnia_assicurativa_id'=> $compagnia->id,
            'tipo_sinistro'            => TipoSinistro::RcaTerzi,
            'numero_sinistro'          => 'SIN-2026-001',
            'stato'                    => 'in_lavorazione',
        ]);

        $this->commessa->update(['sinistro_id' => $this->sinistro->id]);

        $this->perizia = Perizia::create([
            'sinistro_id'                  => $this->sinistro->id,
            'importo_liquidato'            => 900,
            'importo_franchigia'           => 100,
            'importo_scoperto_percentuale' => 0,
            'importo_netto_liquidato'      => 800, // 900 - 100 = 800
            'accettata'                    => true,
        ]);
    }

    public function test_genera_due_documenti(): void
    {
        $this->actingAs($this->admin);

        $this->commessa->load('righe');

        $action = app(GeneraFatturaDoppiaAction::class);
        [$docCliente, $docAssicurazione] = $action->execute($this->commessa);

        $this->assertInstanceOf(Documento::class, $docCliente);
        $this->assertInstanceOf(Documento::class, $docAssicurazione);
        $this->assertNotEquals($docCliente->id, $docAssicurazione->id);
    }

    public function test_somma_totali_corretta(): void
    {
        $this->actingAs($this->admin);
        $this->commessa->load('righe');

        $action = app(GeneraFatturaDoppiaAction::class);
        [$docCliente, $docAssicurazione] = $action->execute($this->commessa);

        // imponibile_totale = 1500, IVA 22% = 330, lordo = 1830
        $totaleCommessa = $this->commessa->totale_lordo;

        $sommaTotali = (float) $docCliente->totale + (float) $docAssicurazione->totale;
        $this->assertEqualsWithDelta($totaleCommessa, $sommaTotali, 0.01,
            "La somma delle due fatture deve essere uguale al totale della commessa.");
    }

    public function test_tipo_emissione_corretto(): void
    {
        $this->actingAs($this->admin);
        $this->commessa->load('righe');

        $action = app(GeneraFatturaDoppiaAction::class);
        [$docCliente, $docAssicurazione] = $action->execute($this->commessa);

        $this->assertEquals(TipoEmissione::Cliente, $docCliente->tipo_emissione);
        $this->assertEquals(TipoEmissione::Assicurazione, $docAssicurazione->tipo_emissione);
    }

    public function test_documenti_collegati(): void
    {
        $this->actingAs($this->admin);
        $this->commessa->load('righe');

        $action = app(GeneraFatturaDoppiaAction::class);
        [$docCliente, $docAssicurazione] = $action->execute($this->commessa);

        $this->assertEquals($docAssicurazione->id, $docCliente->documento_correlato_id);
        $this->assertEquals($docCliente->id, $docAssicurazione->documento_correlato_id);
    }

    public function test_imponibile_assicurazione_uguale_netto_liquidato(): void
    {
        $this->actingAs($this->admin);
        $this->commessa->load('righe');

        $action = app(GeneraFatturaDoppiaAction::class);
        [, $docAssicurazione] = $action->execute($this->commessa);

        $this->assertEqualsWithDelta(800.0, (float) $docAssicurazione->imponibile, 0.01);
    }

    public function test_lancia_eccezione_senza_perizia(): void
    {
        $this->actingAs($this->admin);
        $this->commessa->load('righe');

        $this->perizia->delete();

        $this->expectException(\InvalidArgumentException::class);

        $action = app(GeneraFatturaDoppiaAction::class);
        $action->execute($this->commessa);
    }
}
