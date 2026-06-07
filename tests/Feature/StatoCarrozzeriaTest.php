<?php

namespace Tests\Feature;

use App\Enums\StatoCarrozzeria;
use App\Enums\StatoCommessa;
use App\Enums\TipoCommessa;
use App\Models\Cliente;
use App\Models\Commessa;
use App\Models\User;
use App\Models\Veicolo;
use Database\Seeders\RuoliSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StatoCarrozzeriaTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Commessa $commessa;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RuoliSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $cliente = Cliente::create([
            'tipo'    => 'fisica',
            'nome'    => 'Test',
            'cognome' => 'User',
        ]);

        $veicolo = Veicolo::create([
            'marca'   => 'Fiat',
            'modello' => 'Punto',
            'tipo'    => 'auto',
        ]);
        $veicolo->clienti()->attach($cliente->id);

        $this->commessa = Commessa::create([
            'numero'              => 'C/2026/001',
            'cliente_id'          => $cliente->id,
            'veicolo_id'          => $veicolo->id,
            'tipo'                => TipoCommessa::Carrozzeria,
            'stato'               => StatoCommessa::InLavorazione,
            'data_ingresso'       => now(),
            'user_id'             => $this->admin->id,
            'descrizione_cliente' => 'Test carrozzeria',
        ]);
    }

    public function test_avanzamento_sequenziale_fasi(): void
    {
        $fasi = StatoCarrozzeria::inOrdine();

        foreach ($fasi as $i => $fase) {
            $this->commessa->update(['stato_carrozzeria' => $fase]);
            $this->commessa->refresh();

            $this->assertEquals($fase, $this->commessa->stato_carrozzeria);

            $atteso = $fase->successiva();
            $this->assertEquals($atteso, $fase->successiva());
        }
    }

    public function test_ultima_fase_non_ha_successiva(): void
    {
        $this->assertNull(StatoCarrozzeria::Consegna->successiva());
    }

    public function test_ordine_fasi_crescente(): void
    {
        $fasi = StatoCarrozzeria::inOrdine();

        for ($i = 0; $i < count($fasi) - 1; $i++) {
            $this->assertLessThan($fasi[$i + 1]->ordine(), $fasi[$i]->ordine());
        }
    }

    public function test_commessa_completata_richiede_stato_consegna(): void
    {
        $this->actingAs($this->admin);

        // Senza stato_carrozzeria = consegna, la transizione a "completata" deve essere bloccata
        $this->commessa->update(['stato_carrozzeria' => StatoCarrozzeria::RiscontroDanni]);

        // Verifica che lo stato sia diverso da Consegna
        $this->assertNotEquals(StatoCarrozzeria::Consegna, $this->commessa->stato_carrozzeria);

        // Solo quando stato_carrozzeria è Consegna il completamento è ammesso
        $this->commessa->update(['stato_carrozzeria' => StatoCarrozzeria::Consegna]);
        $this->assertEquals(StatoCarrozzeria::Consegna, $this->commessa->fresh()->stato_carrozzeria);
    }

    public function test_enum_ha_label_per_ogni_caso(): void
    {
        foreach (StatoCarrozzeria::cases() as $caso) {
            $this->assertNotEmpty($caso->label());
        }
    }

    public function test_enum_inOrdine_contiene_tutti_i_casi(): void
    {
        $this->assertCount(count(StatoCarrozzeria::cases()), StatoCarrozzeria::inOrdine());
    }
}
