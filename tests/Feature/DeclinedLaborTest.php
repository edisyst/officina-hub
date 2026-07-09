<?php

namespace Tests\Feature;

use App\Enums\StatoCommessa;
use App\Enums\TipoCliente;
use App\Enums\TipoCommessa;
use App\Models\Cliente;
use App\Models\Commessa;
use App\Models\CommessaRiga;
use App\Models\User;
use App\Models\VehicleRecommendation;
use App\Models\Veicolo;
use Database\Seeders\RuoliSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DeclinedLaborTest extends TestCase
{
    use RefreshDatabase;

    private Commessa $commessa;
    private Veicolo $veicolo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RuoliSeeder::class);
        foreach (['admin', 'accettatore', 'meccanico', 'cassa'] as $r) {
            Role::firstOrCreate(['name' => $r, 'guard_name' => 'web']);
        }

        $user          = User::factory()->create();
        $cliente       = Cliente::create(['tipo' => TipoCliente::Fisica, 'nome' => 'Declined', 'cognome' => 'Test']);
        $this->veicolo = Veicolo::create(['marca' => 'BMW', 'modello' => 'X3', 'km_attuali' => 80000, 'cliente_id' => $cliente->id]);
        $this->commessa = Commessa::create([
            'numero'              => 'DEC-001',
            'tipo'                => TipoCommessa::Meccanica->value,
            'stato'               => StatoCommessa::Bozza->value,
            'cliente_id'          => $cliente->id,
            'veicolo_id'          => $this->veicolo->id,
            'user_id'             => $user->id,
            'data_ingresso'       => now(),
            'km_ingresso'         => 80000,
            'descrizione_cliente' => '',
        ]);
    }

    private function creaRiga(string $descrizione, string $outcome = 'completed', float $prezzo = 100): CommessaRiga
    {
        return CommessaRiga::create([
            'commessa_id'        => $this->commessa->id,
            'tipo'               => 'manodopera',
            'descrizione'        => $descrizione,
            'quantita'           => 1,
            'prezzo_unitario'    => $prezzo,
            'sconto_percentuale' => 0,
            'iva_percentuale'    => 22,
            'ordinamento'        => 1,
            'in_garanzia'        => false,
            'outcome'            => $outcome,
        ]);
    }

    public function test_riga_declined_esclusa_da_totale_imponibile(): void
    {
        $this->creaRiga('Lavoro A', 'completed', 100);
        $this->creaRiga('Lavoro B', 'declined', 200);

        $commessa = $this->commessa->fresh(['righe']);
        $this->assertEquals(100.0, $commessa->totale_imponibile);
    }

    public function test_riga_declined_esclusa_da_totale_iva(): void
    {
        $this->creaRiga('Lavoro A', 'completed', 100);
        $this->creaRiga('Lavoro B', 'declined', 100);

        $commessa = $this->commessa->fresh(['righe']);
        $this->assertEquals(22.0, $commessa->totale_iva);
    }

    public function test_riga_declined_crea_recommendation(): void
    {
        $riga = $this->creaRiga('Sostituzione ammortizzatori');
        $riga->update(['outcome' => 'declined']);

        $this->assertDatabaseHas('vehicle_recommendations', [
            'vehicle_id' => $this->veicolo->id,
            'source'     => 'declined',
            'title'      => 'Sostituzione ammortizzatori',
            'status'     => 'pending',
        ]);
    }

    public function test_scope_completed_filtra_declined(): void
    {
        $this->creaRiga('A', 'completed');
        $this->creaRiga('B', 'declined');

        $this->assertEquals(1, CommessaRiga::completed()->count());
    }
}
