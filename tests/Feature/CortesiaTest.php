<?php

namespace Tests\Feature;

use App\Enums\StatoPrestito;
use App\Models\Cliente;
use App\Models\Commessa;
use App\Models\PrestitoCortesia;
use App\Models\User;
use App\Models\VeicoloCortesia;
use App\Models\Veicolo;
use App\Services\LookupTarga\LookupTargaService;
use App\Services\LookupTarga\MockProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CortesiaTest extends TestCase
{
    use RefreshDatabase;

    private User    $admin;
    private User    $accettatore;
    private Cliente $cliente;

    protected function setUp(): void
    {
        parent::setUp();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (['admin', 'accettatore', 'meccanico', 'cassa'] as $r) {
            Role::firstOrCreate(['name' => $r, 'guard_name' => 'web']);
        }

        $this->admin      = User::factory()->create();
        $this->accettatore = User::factory()->create();
        $this->admin->assignRole('admin');
        $this->accettatore->assignRole('accettatore');

        $this->cliente = Cliente::create([
            'tipo'    => 'fisica',
            'nome'    => 'Mario',
            'cognome' => 'Rossi',
        ]);
    }

    private function creaVeicolo(array $attrs = []): VeicoloCortesia
    {
        return VeicoloCortesia::create(array_merge([
            'targa'                     => 'AB123CD',
            'marca'                     => 'Fiat',
            'modello'                   => 'Panda',
            'tipo'                      => 'auto',
            'km_attuali'                => 1000,
            'carburante_tipo'           => 'benzina',
            'livello_carburante_inizio' => 100,
            'attivo'                    => true,
        ], $attrs));
    }

    /** Il veicolo è disponibile quando non ha prestiti attivi nel periodo */
    public function test_veicolo_disponibile_senza_prestiti(): void
    {
        $veicolo = $this->creaVeicolo();

        $this->assertTrue(
            $veicolo->isDisponibile(new \DateTime('2026-07-01'), new \DateTime('2026-07-05'))
        );
    }

    /** Il veicolo NON è disponibile se c'è sovrapposizione con un prestito in corso */
    public function test_veicolo_non_disponibile_con_sovrapposizione(): void
    {
        $veicolo = $this->creaVeicolo();

        PrestitoCortesia::create([
            'veicolo_cortesia_id'   => $veicolo->id,
            'cliente_id'            => $this->cliente->id,
            'user_id_consegna'      => $this->admin->id,
            'data_consegna'         => '2026-07-02 09:00',
            'data_rientro_prevista' => '2026-07-06',
            'km_consegna'           => 1000,
            'carburante_consegna'   => 100,
            'stato'                 => StatoPrestito::InCorso,
        ]);

        $this->assertFalse(
            $veicolo->isDisponibile(new \DateTime('2026-07-01'), new \DateTime('2026-07-05'))
        );
    }

    /** Il veicolo è disponibile per un periodo non sovrapposto */
    public function test_veicolo_disponibile_periodo_diverso(): void
    {
        $veicolo = $this->creaVeicolo();

        PrestitoCortesia::create([
            'veicolo_cortesia_id'   => $veicolo->id,
            'cliente_id'            => $this->cliente->id,
            'user_id_consegna'      => $this->admin->id,
            'data_consegna'         => '2026-07-01 09:00',
            'data_rientro_prevista' => '2026-07-05',
            'km_consegna'           => 1000,
            'carburante_consegna'   => 100,
            'stato'                 => StatoPrestito::Rientrato,
        ]);

        // Dopo il rientro, deve essere disponibile
        $this->assertTrue(
            $veicolo->isDisponibile(new \DateTime('2026-07-06'), new \DateTime('2026-07-10'))
        );
    }

    /** Il flusso consegna crea il prestito con stato in_corso */
    public function test_crea_prestito_con_stato_in_corso(): void
    {
        $veicolo = $this->creaVeicolo();

        $prestito = PrestitoCortesia::create([
            'veicolo_cortesia_id'   => $veicolo->id,
            'cliente_id'            => $this->cliente->id,
            'user_id_consegna'      => $this->accettatore->id,
            'data_consegna'         => now(),
            'data_rientro_prevista' => now()->addDays(3)->toDateString(),
            'km_consegna'           => 1000,
            'carburante_consegna'   => 80,
            'firma_consegna_svg'    => '<svg><path/></svg>',
            'stato'                 => StatoPrestito::InCorso,
        ]);

        $this->assertDatabaseHas('prestiti_cortesia', [
            'id'    => $prestito->id,
            'stato' => StatoPrestito::InCorso->value,
        ]);
        $this->assertEquals(StatoPrestito::InCorso, $prestito->stato);
    }

    /** Il rientro aggiorna stato, km e carburante */
    public function test_rientro_aggiorna_dati_veicolo(): void
    {
        $veicolo = $this->creaVeicolo(['km_attuali' => 1000]);

        $prestito = PrestitoCortesia::create([
            'veicolo_cortesia_id'   => $veicolo->id,
            'cliente_id'            => $this->cliente->id,
            'user_id_consegna'      => $this->admin->id,
            'data_consegna'         => now()->subDays(2),
            'data_rientro_prevista' => now()->toDateString(),
            'km_consegna'           => 1000,
            'carburante_consegna'   => 80,
            'stato'                 => StatoPrestito::InCorso,
        ]);

        $prestito->update([
            'km_rientro'             => 1250,
            'carburante_rientro'     => 60,
            'data_rientro_effettiva' => now(),
            'user_id_rientro'        => $this->admin->id,
            'stato'                  => StatoPrestito::Rientrato,
        ]);

        $veicolo->update(['km_attuali' => 1250]);

        $this->assertEquals(StatoPrestito::Rientrato, $prestito->fresh()->stato);
        $this->assertEquals(250, $prestito->fresh()->km_percorsi);
        $this->assertEquals(-20, $prestito->fresh()->delta_carburante);
        $this->assertEquals(1250, $veicolo->fresh()->km_attuali);
    }

    /** Prestito in ritardo viene rilevato correttamente */
    public function test_prestito_in_ritardo(): void
    {
        $veicolo = $this->creaVeicolo();

        $prestito = PrestitoCortesia::create([
            'veicolo_cortesia_id'   => $veicolo->id,
            'cliente_id'            => $this->cliente->id,
            'user_id_consegna'      => $this->admin->id,
            'data_consegna'         => now()->subDays(5),
            'data_rientro_prevista' => now()->subDays(2)->toDateString(),
            'km_consegna'           => 500,
            'carburante_consegna'   => 100,
            'stato'                 => StatoPrestito::InCorso,
        ]);

        $this->assertTrue($prestito->isInRitardo());

        $scope = PrestitoCortesia::inRitardo()->count();
        $this->assertEquals(1, $scope);
    }

    /** MockProvider pre-compila i dati dalla targa */
    public function test_mock_provider_cerca_targa(): void
    {
        $provider = new MockProvider();
        $risultato = $provider->cerca('AB123CD');

        $this->assertNotNull($risultato);
        $this->assertArrayHasKey('marca', $risultato);
        $this->assertArrayHasKey('modello', $risultato);
        $this->assertArrayHasKey('alimentazione', $risultato);
        $this->assertIsString($risultato['marca']);
    }

    /** MockProvider restituisce null per targa troppo corta */
    public function test_mock_provider_null_targa_corta(): void
    {
        $provider = new MockProvider();
        $this->assertNull($provider->cerca('AB'));
    }

    /** LookupTargaService restituisce null se disabilitato */
    public function test_service_restituisce_null_se_disabilitato(): void
    {
        // Non impostare lookup_targa_abilitato → default false
        $service = new LookupTargaService();
        $this->assertNull($service->cerca('AB123CD'));
        $this->assertFalse($service->isAbilitato());
    }
}
