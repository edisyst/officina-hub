<?php

namespace Tests\Unit;

use App\Enums\TipoCliente;
use App\Enums\TipoCommessa;
use App\Enums\StatoCommessa;
use App\Models\Cliente;
use App\Models\Commessa;
use App\Models\CommessaRiga;
use App\Models\MaintenanceRule;
use App\Models\VehicleRecommendation;
use App\Models\Veicolo;
use App\Services\Recommendations\RecommendationEngineService;
use Database\Seeders\RuoliSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecommendationEngineServiceTest extends TestCase
{
    use RefreshDatabase;

    private RecommendationEngineService $engine;
    private Veicolo $veicolo;
    private Commessa $commessa;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RuoliSeeder::class);
        $this->engine = app(RecommendationEngineService::class);

        $user          = \App\Models\User::factory()->create();
        $cliente       = Cliente::create(['tipo' => TipoCliente::Fisica, 'nome' => 'Test', 'cognome' => 'Rec']);
        $this->veicolo = Veicolo::create(['marca' => 'Fiat', 'modello' => 'Punto', 'km_attuali' => 50000, 'cliente_id' => $cliente->id]);
        $this->commessa = Commessa::create([
            'numero'              => 'REC-001',
            'tipo'                => TipoCommessa::Meccanica->value,
            'stato'               => StatoCommessa::Bozza->value,
            'cliente_id'          => $cliente->id,
            'veicolo_id'          => $this->veicolo->id,
            'user_id'             => $user->id,
            'data_ingresso'       => now(),
            'km_ingresso'         => 50000,
            'descrizione_cliente' => '',
        ]);
    }

    public function test_declined_labor_crea_recommendation(): void
    {
        $riga = CommessaRiga::create([
            'commessa_id'        => $this->commessa->id,
            'tipo'               => 'manodopera',
            'descrizione'        => 'Sostituzione pastiglie freno',
            'quantita'           => 1,
            'prezzo_unitario'    => 80,
            'sconto_percentuale' => 0,
            'iva_percentuale'    => 22,
            'ordinamento'        => 1,
            'in_garanzia'        => false,
            'outcome'            => 'completed',
        ]);

        $riga->update(['outcome' => 'declined']);

        $this->assertDatabaseHas('vehicle_recommendations', [
            'vehicle_id'          => $this->veicolo->id,
            'source'              => 'declined',
            'title'               => 'Sostituzione pastiglie freno',
            'status'              => 'pending',
            'origin_work_order_id' => $this->commessa->id,
        ]);
    }

    public function test_ripristino_completed_rimuove_recommendation_pending(): void
    {
        $riga = CommessaRiga::create([
            'commessa_id'        => $this->commessa->id,
            'tipo'               => 'manodopera',
            'descrizione'        => 'Tagliando olio',
            'quantita'           => 1,
            'prezzo_unitario'    => 50,
            'sconto_percentuale' => 0,
            'iva_percentuale'    => 22,
            'ordinamento'        => 1,
            'in_garanzia'        => false,
            'outcome'            => 'completed',
        ]);

        $riga->update(['outcome' => 'declined']);
        $this->assertEquals(1, VehicleRecommendation::pending()->count());

        $riga->update(['outcome' => 'completed']);
        $this->assertEquals(0, VehicleRecommendation::pending()->count());
    }

    public function test_mileage_crea_recommendation_quando_km_superati(): void
    {
        MaintenanceRule::create([
            'name'      => 'Tagliando olio',
            'every_km'  => 15000,
            'is_active' => true,
        ]);

        // Veicolo a 50000 km, nessuna esecuzione precedente → ogni_km senza storia → trigger
        $this->engine->refreshFor($this->veicolo);

        $this->assertDatabaseHas('vehicle_recommendations', [
            'vehicle_id' => $this->veicolo->id,
            'source'     => 'mileage',
            'title'      => 'Tagliando olio',
            'status'     => 'pending',
        ]);
    }

    public function test_engine_idempotente_nessun_duplicato_pending(): void
    {
        MaintenanceRule::create([
            'name'      => 'Tagliando olio',
            'every_km'  => 15000,
            'is_active' => true,
        ]);

        $this->engine->refreshFor($this->veicolo);
        $this->engine->refreshFor($this->veicolo);
        $this->engine->refreshFor($this->veicolo);

        $this->assertEquals(1, VehicleRecommendation::where('status', 'pending')->count());
    }

    public function test_regola_mesi_crea_recommendation(): void
    {
        MaintenanceRule::create([
            'name'          => 'Liquido freni',
            'every_months'  => 24,
            'is_active'     => true,
        ]);

        $this->engine->refreshFor($this->veicolo);

        $this->assertDatabaseHas('vehicle_recommendations', [
            'vehicle_id' => $this->veicolo->id,
            'source'     => 'mileage',
            'title'      => 'Liquido freni',
        ]);
    }

    public function test_righe_declined_escluse_da_totale_commessa(): void
    {
        CommessaRiga::create([
            'commessa_id'        => $this->commessa->id,
            'tipo'               => 'manodopera',
            'descrizione'        => 'Completato',
            'quantita'           => 1,
            'prezzo_unitario'    => 100,
            'sconto_percentuale' => 0,
            'iva_percentuale'    => 22,
            'ordinamento'        => 1,
            'in_garanzia'        => false,
            'outcome'            => 'completed',
        ]);

        CommessaRiga::create([
            'commessa_id'        => $this->commessa->id,
            'tipo'               => 'manodopera',
            'descrizione'        => 'Declinato',
            'quantita'           => 1,
            'prezzo_unitario'    => 200,
            'sconto_percentuale' => 0,
            'iva_percentuale'    => 22,
            'ordinamento'        => 2,
            'in_garanzia'        => false,
            'outcome'            => 'declined',
        ]);

        $commessa = $this->commessa->fresh(['righe']);
        $this->assertEquals(100.0, $commessa->totale_imponibile);
    }
}
