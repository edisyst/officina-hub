<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ClienteTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $meccanico;

    protected function setUp(): void
    {
        parent::setUp();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        Role::create(['name' => 'admin', 'guard_name' => 'web']);
        Role::create(['name' => 'accettatore', 'guard_name' => 'web']);
        Role::create(['name' => 'meccanico', 'guard_name' => 'web']);
        Role::create(['name' => 'cassa', 'guard_name' => 'web']);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->meccanico = User::factory()->create();
        $this->meccanico->assignRole('meccanico');
    }

    public function test_admin_puo_vedere_lista_clienti(): void
    {
        $response = $this->actingAs($this->admin)->get(route('clienti.index'));
        $response->assertOk();
    }

    public function test_meccanico_non_puo_vedere_lista_clienti(): void
    {
        $response = $this->actingAs($this->meccanico)->get(route('clienti.index'));
        $response->assertForbidden();
    }

    public function test_crea_cliente_persona_fisica(): void
    {
        $cliente = Cliente::create([
            'tipo' => 'fisica',
            'nome' => 'Mario',
            'cognome' => 'Rossi',
            'telefono' => '333 1234567',
        ]);

        $this->assertDatabaseHas('clienti', ['nome' => 'Mario', 'cognome' => 'Rossi']);
        $this->assertEquals('Mario Rossi', $cliente->nome_completo);
    }

    public function test_crea_cliente_persona_giuridica(): void
    {
        $cliente = Cliente::create([
            'tipo' => 'giuridica',
            'ragione_sociale' => 'Acme S.r.l.',
            'partita_iva' => '01234567890',
        ]);

        $this->assertDatabaseHas('clienti', ['ragione_sociale' => 'Acme S.r.l.']);
        $this->assertEquals('Acme S.r.l.', $cliente->nome_completo);
    }

    public function test_soft_delete_cliente(): void
    {
        $cliente = Cliente::create(['tipo' => 'fisica', 'nome' => 'Test', 'cognome' => 'Elimina']);

        $cliente->delete();

        $this->assertSoftDeleted('clienti', ['id' => $cliente->id]);
        $this->assertNull(Cliente::find($cliente->id));
        $this->assertNotNull(Cliente::withTrashed()->find($cliente->id));
    }

    public function test_ripristina_cliente_eliminato(): void
    {
        $cliente = Cliente::create(['tipo' => 'fisica', 'nome' => 'Test', 'cognome' => 'Ripristina']);
        $cliente->delete();
        $cliente->restore();
        $this->assertNotNull(Cliente::find($cliente->id));
    }

    public function test_ricerca_cliente(): void
    {
        Cliente::create(['tipo' => 'fisica', 'nome' => 'Giovanni', 'cognome' => 'Verdi']);
        Cliente::create(['tipo' => 'fisica', 'nome' => 'Luigi', 'cognome' => 'Bianchi']);

        $risultati = Cliente::search('Giovanni')->get();
        $this->assertCount(1, $risultati);
        $this->assertEquals('Giovanni', $risultati->first()->nome);
    }
}
