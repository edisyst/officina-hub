<?php

namespace Tests\Feature;

use App\Actions\Lavorazione\AvviaLavorazioneAction;
use App\Actions\Lavorazione\FermaLavorazioneAction;
use App\Enums\StatoCommessa;
use App\Enums\TipoCliente;
use App\Enums\TipoCommessa;
use App\Models\Cliente;
use App\Models\Commessa;
use App\Models\Lavorazione;
use App\Models\User;
use App\Models\Veicolo;
use Database\Seeders\RuoliSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class LavorazioneTest extends TestCase
{
    use RefreshDatabase;

    private User $meccanico;
    private Commessa $commessa;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RuoliSeeder::class);

        $this->meccanico = User::factory()->create();
        $this->meccanico->assignRole('meccanico');

        $cliente = Cliente::create([
            'tipo'    => TipoCliente::Fisica,
            'nome'    => 'Test',
            'cognome' => 'Cliente',
        ]);

        $veicolo = Veicolo::create([
            'targa' => 'AA000AA',
            'marca' => 'Fiat',
            'modello' => 'Punto',
        ]);

        $this->commessa = Commessa::create([
            'numero'              => 'C-2026-001',
            'stato'               => StatoCommessa::InLavorazione,
            'tipo'                => TipoCommessa::Meccanica,
            'data_ingresso'       => now(),
            'descrizione_cliente' => '',
            'cliente_id'          => $cliente->id,
            'veicolo_id'          => $veicolo->id,
            'user_id'             => $this->meccanico->id,
        ]);
    }

    public function test_avvio_lavorazione_registra_started_at(): void
    {
        $lavorazione = Lavorazione::create([
            'commessa_id'        => $this->commessa->id,
            'user_id'            => $this->meccanico->id,
            'descrizione'        => 'Cambio olio',
            'minuti_preventivati'=> 30,
        ]);

        app(AvviaLavorazioneAction::class)->execute($lavorazione, $this->meccanico);

        $lavorazione->refresh();
        $this->assertNotNull($lavorazione->started_at);
        $this->assertNull($lavorazione->stopped_at);
    }

    public function test_stop_lavorazione_calcola_minuti_effettivi(): void
    {
        $lavorazione = Lavorazione::create([
            'commessa_id'        => $this->commessa->id,
            'user_id'            => $this->meccanico->id,
            'descrizione'        => 'Freni',
            'minuti_preventivati'=> 60,
            'started_at'         => now()->subMinutes(45),
        ]);

        app(FermaLavorazioneAction::class)->execute($lavorazione);

        $lavorazione->refresh();
        $this->assertNotNull($lavorazione->stopped_at);
        $this->assertNotNull($lavorazione->minuti_effettivi);
        $this->assertGreaterThanOrEqual(45, $lavorazione->minuti_effettivi);
    }

    public function test_blocco_lavorazione_concorrente(): void
    {
        // Prima lavorazione già attiva
        Lavorazione::create([
            'commessa_id'        => $this->commessa->id,
            'user_id'            => $this->meccanico->id,
            'descrizione'        => 'Prima lavorazione',
            'minuti_preventivati'=> 30,
            'started_at'         => now()->subMinutes(10),
        ]);

        $secondaLavorazione = Lavorazione::create([
            'commessa_id'        => $this->commessa->id,
            'user_id'            => $this->meccanico->id,
            'descrizione'        => 'Seconda lavorazione',
            'minuti_preventivati'=> 30,
        ]);

        $this->expectException(ValidationException::class);
        app(AvviaLavorazioneAction::class)->execute($secondaLavorazione, $this->meccanico);
    }

    public function test_scope_attive_filtra_correttamente(): void
    {
        Lavorazione::create([
            'commessa_id'        => $this->commessa->id,
            'user_id'            => $this->meccanico->id,
            'descrizione'        => 'Attiva',
            'minuti_preventivati'=> 30,
            'started_at'         => now(),
        ]);

        Lavorazione::create([
            'commessa_id'        => $this->commessa->id,
            'user_id'            => $this->meccanico->id,
            'descrizione'        => 'Non avviata',
            'minuti_preventivati'=> 30,
        ]);

        Lavorazione::create([
            'commessa_id'        => $this->commessa->id,
            'user_id'            => $this->meccanico->id,
            'descrizione'        => 'Completata',
            'minuti_preventivati'=> 30,
            'started_at'         => now()->subHour(),
            'stopped_at'         => now(),
        ]);

        $this->assertEquals(1, Lavorazione::attive()->count());
    }
}
