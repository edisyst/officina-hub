<?php

namespace Tests\Feature\Livewire;

use App\Enums\StatoAppuntamento;
use App\Enums\StatoCommessa;
use App\Enums\TipoCliente;
use App\Enums\TipoCommessa;
use App\Livewire\TechBoard;
use App\Models\Appuntamento;
use App\Models\Cliente;
use App\Models\Commessa;
use App\Models\Lavorazione;
use App\Models\User;
use App\Models\Veicolo;
use Database\Seeders\RuoliSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TechBoardTest extends TestCase
{
    use RefreshDatabase;

    private Cliente $cliente;
    private Veicolo $veicolo;
    private User $meccanico;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RuoliSeeder::class);

        $this->meccanico = User::factory()->create(['name' => 'Luigi Test']);
        $this->meccanico->assignRole('meccanico');

        $this->cliente = Cliente::create(['tipo' => TipoCliente::Fisica, 'nome' => 'Luigi', 'cognome' => 'Esposito']);
        $this->veicolo = Veicolo::create(['targa' => 'TV100TV', 'marca' => 'Fiat', 'modello' => 'Punto']);
    }

    private function makeCommessa(string $numero, StatoCommessa $stato): Commessa
    {
        return Commessa::create([
            'numero'             => $numero,
            'stato'              => $stato,
            'tipo'               => TipoCommessa::Tagliando,
            'cliente_id'         => $this->cliente->id,
            'veicolo_id'         => $this->veicolo->id,
            'user_id'            => $this->meccanico->id,
            'data_ingresso'      => now(),
            'descrizione_cliente' => 'Test',
        ]);
    }

    public function test_renders_empty_states(): void
    {
        Livewire::test(TechBoard::class)
            ->assertSee('Nessuna lavorazione attiva')
            ->assertSee('Nessun veicolo in attesa')
            ->assertSee('Nessun appuntamento');
    }

    public function test_renders_active_lavorazione(): void
    {
        $commessa = $this->makeCommessa('C-2026-001', StatoCommessa::InLavorazione);

        Lavorazione::create([
            'commessa_id' => $commessa->id,
            'user_id'     => $this->meccanico->id,
            'started_at'  => now()->subMinutes(10),
            'stopped_at'  => null,
            'descrizione' => 'Freni anteriori',
        ]);

        Livewire::test(TechBoard::class)
            ->assertSee('TV100TV')
            ->assertSee('Luigi Test');
    }

    public function test_renders_sospesa_commessa(): void
    {
        $this->makeCommessa('C-2026-002', StatoCommessa::Sospesa);

        Livewire::test(TechBoard::class)
            ->assertSee('TV100TV')
            ->assertSee('Esposito');
    }

    public function test_renders_prossimo_appuntamento(): void
    {
        Appuntamento::create([
            'data_ora_inizio' => now()->setHour(11)->setMinute(0),
            'data_ora_fine'   => now()->setHour(12)->setMinute(0),
            'cliente_id'      => $this->cliente->id,
            'veicolo_id'      => $this->veicolo->id,
            'titolo'          => 'Cambio olio',
            'stato'           => StatoAppuntamento::Confermato,
        ]);

        Livewire::test(TechBoard::class)
            ->assertSee('TV100TV')
            ->assertSee('Cambio olio');
    }
}
