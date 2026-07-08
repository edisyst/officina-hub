<?php

namespace Tests\Feature\Livewire;

use App\Enums\StatoCommessa;
use App\Enums\TipoCliente;
use App\Enums\TipoCommessa;
use App\Enums\TipoRiga;
use App\Livewire\Reports\Profitability;
use App\Models\Cliente;
use App\Models\Commessa;
use App\Models\CommessaRiga;
use App\Models\User;
use App\Models\Veicolo;
use Database\Seeders\RuoliSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProfitabilityReportTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        $this->seed(RuoliSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        return $admin;
    }

    private function creaCommessa(string $numero = 'PR-001'): Commessa
    {
        $cliente = Cliente::create(['tipo' => TipoCliente::Fisica, 'nome' => 'Report', 'cognome' => 'Test']);
        $veicolo = Veicolo::create(['marca' => 'Alfa', 'modello' => 'Romeo']);

        return Commessa::create([
            'numero'              => $numero,
            'stato'               => StatoCommessa::InLavorazione,
            'tipo'                => TipoCommessa::Meccanica,
            'data_ingresso'       => now(),
            'descrizione_cliente' => '',
            'cliente_id'          => $cliente->id,
            'veicolo_id'          => $veicolo->id,
            'user_id'             => User::factory()->create()->id,
        ]);
    }

    public function test_report_visibile_ad_admin(): void
    {
        $admin = $this->adminUser();
        $this->actingAs($admin);

        Livewire::test(Profitability::class)
            ->assertStatus(200)
            ->assertSee('Commesse nel periodo');
    }

    public function test_gate_blocca_meccanico(): void
    {
        $this->seed(RuoliSeeder::class);
        $meccanico = User::factory()->create();
        $meccanico->assignRole('meccanico');

        $this->actingAs($meccanico);

        Livewire::test(Profitability::class)
            ->assertForbidden();
    }

    public function test_filtro_date_funziona(): void
    {
        $admin    = $this->adminUser();
        $commessa = $this->creaCommessa();

        CommessaRiga::create([
            'commessa_id'        => $commessa->id,
            'tipo'               => TipoRiga::Articolo,
            'descrizione'        => 'Ricambio',
            'quantita'           => 1,
            'prezzo_unitario'    => 100,
            'prezzo_acquisto'    => 40,
            'sconto_percentuale' => 0,
            'iva_percentuale'    => 22,
            'ordinamento'        => 1,
        ]);

        $this->actingAs($admin);

        Livewire::test(Profitability::class)
            ->set('dal', now()->startOfMonth()->format('Y-m-d'))
            ->set('al', now()->endOfMonth()->format('Y-m-d'))
            ->assertSee($commessa->numero);
    }

    public function test_totali_periodo_corretti(): void
    {
        $admin    = $this->adminUser();
        $commessa = $this->creaCommessa('PR-002');

        CommessaRiga::create([
            'commessa_id'        => $commessa->id,
            'tipo'               => TipoRiga::Articolo,
            'descrizione'        => 'Ricambio',
            'quantita'           => 2,
            'prezzo_unitario'    => 50,
            'prezzo_acquisto'    => 20,
            'sconto_percentuale' => 0,
            'iva_percentuale'    => 22,
            'ordinamento'        => 1,
        ]);

        $this->actingAs($admin);

        Livewire::test(Profitability::class)
            ->set('dal', now()->startOfMonth()->format('Y-m-d'))
            ->set('al', now()->endOfMonth()->format('Y-m-d'))
            ->assertSee($commessa->numero);
    }

    public function test_export_csv_restituisce_file(): void
    {
        $admin = $this->adminUser();
        $this->actingAs($admin);

        Livewire::test(Profitability::class)
            ->call('exportCsv')
            ->assertFileDownloaded();
    }
}
