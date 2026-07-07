<?php

namespace Tests\Unit;

use App\Actions\Acceptance\CheckInVehicleAction;
use App\Models\Cliente;
use App\Models\Commessa;
use App\Models\User;
use App\Models\Veicolo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CheckInVehicleActionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private CheckInVehicleAction $action;

    protected function setUp(): void
    {
        parent::setUp();
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        foreach (['admin', 'accettatore', 'meccanico', 'cassa'] as $r) {
            Role::firstOrCreate(['name' => $r, 'guard_name' => 'web']);
        }
        $this->user   = User::factory()->create();
        $this->user->assignRole('admin');
        $this->action = app(CheckInVehicleAction::class);
    }

    private function baseDati(array $overrides = []): array
    {
        return array_merge([
            'veicolo_id'          => null,
            'modoNuovoVeicolo'    => true,
            'targa'               => 'AA000BB',
            'km'                  => 50000,
            'nv_tipo'             => 'auto',
            'nv_marca'            => 'Fiat',
            'nv_modello'          => 'Panda',
            'nv_anno'             => 2020,
            'nv_vin'              => null,
            'nv_alimentazione'    => 'benzina',
            'cliente_id'          => null,
            'modoNuovoCliente'    => true,
            'nc_tipo'             => 'fisica',
            'nc_nome'             => 'Mario',
            'nc_cognome'          => 'Rossi',
            'nc_ragione_sociale'  => null,
            'nc_telefono'         => '3331234567',
            'nc_email'            => null,
            'nc_codice_fiscale'   => null,
            'nc_partita_iva'      => null,
            'tipo'                => 'meccanica',
            'descrizione_cliente' => 'Cambio olio',
            'data_uscita_prevista'=> null,
            'pacchetto_id'        => null,
            'righe_preventivo'    => [],
        ], $overrides);
    }

    public function test_crea_nuovo_cliente_veicolo_e_commessa_in_transazione(): void
    {
        $commessa = $this->action->execute($this->baseDati(), $this->user);

        $this->assertInstanceOf(Commessa::class, $commessa);
        $this->assertDatabaseHas('commesse', ['id' => $commessa->id, 'descrizione_cliente' => 'Cambio olio']);
        $this->assertDatabaseHas('clienti',  ['nome' => 'Mario', 'cognome' => 'Rossi']);
        $this->assertDatabaseHas('veicoli',  ['targa' => 'AA000BB', 'marca' => 'Fiat']);
        $this->assertDatabaseHas('cliente_veicolo', ['cliente_id' => $commessa->cliente_id, 'veicolo_id' => $commessa->veicolo_id]);
    }

    public function test_aggancia_cliente_esistente(): void
    {
        $cliente = Cliente::create(['tipo' => 'fisica', 'nome' => 'Anna', 'cognome' => 'Verdi']);

        $commessa = $this->action->execute($this->baseDati([
            'modoNuovoCliente' => false,
            'cliente_id'       => $cliente->id,
        ]), $this->user);

        $this->assertEquals($cliente->id, $commessa->cliente_id);
        $this->assertDatabaseCount('clienti', 1);
    }

    public function test_aggancia_veicolo_esistente_e_aggiorna_km(): void
    {
        $cliente = Cliente::create(['tipo' => 'fisica', 'nome' => 'Luca', 'cognome' => 'Bianchi']);
        $veicolo = Veicolo::create(['tipo' => 'auto', 'targa' => 'ZZ999YY', 'marca' => 'BMW', 'modello' => 'X3', 'alimentazione' => 'diesel', 'km_attuali' => 40000, 'cliente_id' => $cliente->id]);

        $commessa = $this->action->execute($this->baseDati([
            'modoNuovoVeicolo' => false,
            'veicolo_id'       => $veicolo->id,
            'modoNuovoCliente' => false,
            'cliente_id'       => $cliente->id,
            'km'               => 50000,
        ]), $this->user);

        $this->assertEquals($veicolo->id, $commessa->veicolo_id);
        $this->assertEquals(50000, $veicolo->fresh()->km_attuali);
    }

    public function test_km_inferiore_non_aggiorna_veicolo(): void
    {
        $cliente = Cliente::create(['tipo' => 'fisica', 'nome' => 'Luca', 'cognome' => 'Bianchi']);
        $veicolo = Veicolo::create(['tipo' => 'auto', 'targa' => 'ZZ999YY', 'marca' => 'BMW', 'modello' => 'X3', 'alimentazione' => 'diesel', 'km_attuali' => 80000, 'cliente_id' => $cliente->id]);

        $this->action->execute($this->baseDati([
            'modoNuovoVeicolo' => false,
            'veicolo_id'       => $veicolo->id,
            'modoNuovoCliente' => false,
            'cliente_id'       => $cliente->id,
            'km'               => 50000,
        ]), $this->user);

        $this->assertEquals(80000, $veicolo->fresh()->km_attuali);
    }

    public function test_rollback_su_cliente_inesistente(): void
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $this->action->execute($this->baseDati([
            'modoNuovoCliente' => false,
            'cliente_id'       => 99999,
        ]), $this->user);

        $this->assertDatabaseCount('commesse', 0);
    }
}
