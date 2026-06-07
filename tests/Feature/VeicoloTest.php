<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\User;
use App\Models\Veicolo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class VeicoloTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        foreach (['admin', 'accettatore', 'meccanico', 'cassa'] as $r) {
            Role::create(['name' => $r, 'guard_name' => 'web']);
        }
    }

    public function test_crea_veicolo(): void
    {
        $veicolo = Veicolo::create([
            'tipo' => 'auto',
            'targa' => 'AB123CD',
            'marca' => 'Fiat',
            'modello' => 'Panda',
            'alimentazione' => 'benzina',
        ]);

        $this->assertDatabaseHas('veicoli', ['targa' => 'AB123CD', 'marca' => 'Fiat']);
    }

    public function test_associa_cliente_a_veicolo(): void
    {
        $cliente = Cliente::create(['tipo' => 'fisica', 'nome' => 'Mario', 'cognome' => 'Rossi']);
        $veicolo = Veicolo::create([
            'tipo' => 'auto',
            'marca' => 'Fiat',
            'modello' => 'Punto',
            'alimentazione' => 'diesel',
        ]);

        $veicolo->clienti()->attach($cliente->id, [
            'proprietario_attuale' => true,
            'data_inizio' => now()->toDateString(),
        ]);

        $this->assertDatabaseHas('cliente_veicolo', [
            'cliente_id' => $cliente->id,
            'veicolo_id' => $veicolo->id,
            'proprietario_attuale' => 1,
        ]);

        $this->assertCount(1, $veicolo->clienti);
    }

    public function test_soft_delete_veicolo(): void
    {
        $veicolo = Veicolo::create([
            'tipo' => 'auto',
            'targa' => 'XY999ZZ',
            'marca' => 'Test',
            'modello' => 'Delete',
            'alimentazione' => 'benzina',
        ]);

        $veicolo->delete();

        $this->assertSoftDeleted('veicoli', ['id' => $veicolo->id]);
        $this->assertNull(Veicolo::find($veicolo->id));
    }

    public function test_ricerca_veicolo(): void
    {
        Veicolo::create(['tipo' => 'auto', 'targa' => 'FZ123AB', 'marca' => 'Volkswagen', 'modello' => 'Golf', 'alimentazione' => 'diesel']);
        Veicolo::create(['tipo' => 'moto', 'targa' => 'AA999BB', 'marca' => 'Honda', 'modello' => 'CBR', 'alimentazione' => 'benzina']);

        $risultati = Veicolo::search('Volkswagen')->get();
        $this->assertCount(1, $risultati);
        $this->assertEquals('FZ123AB', $risultati->first()->targa);
    }

    public function test_admin_puo_accedere_lista_veicoli(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin)->get(route('veicoli.index'))->assertOk();
    }
}
