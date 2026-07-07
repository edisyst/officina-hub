<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Acceptance\CheckIn;
use App\Models\Cliente;
use App\Models\Commessa;
use App\Models\User;
use App\Models\Veicolo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AcceptanceCheckInTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        foreach (['admin', 'accettatore', 'meccanico', 'cassa'] as $r) {
            Role::firstOrCreate(['name' => $r, 'guard_name' => 'web']);
        }
        $this->user = User::factory()->create();
        $this->user->assignRole('admin');
    }

    public function test_pagina_accettazione_accessibile(): void
    {
        $this->actingAs($this->user)
            ->get(route('acceptance'))
            ->assertOk();
    }

    public function test_targa_esistente_porta_a_stadio_2a(): void
    {
        $cliente = Cliente::create(['tipo' => 'fisica', 'nome' => 'Test', 'cognome' => 'User']);
        $veicolo = Veicolo::create(['tipo' => 'auto', 'targa' => 'AB123CD', 'marca' => 'Fiat', 'modello' => 'Punto', 'alimentazione' => 'benzina', 'km_attuali' => 30000, 'cliente_id' => $cliente->id]);

        Livewire::actingAs($this->user)
            ->test(CheckIn::class)
            ->set('targaInput', 'AB123CD')
            ->assertSet('stadio', 2)
            ->assertSet('veicoloId', $veicolo->id)
            ->assertSet('modoNuovoVeicolo', false);
    }

    public function test_targa_nuova_attiva_modo_nuovo_veicolo(): void
    {
        Livewire::actingAs($this->user)
            ->test(CheckIn::class)
            ->set('targaInput', 'ZZ999XX')
            ->call('proseguiNuovoVeicolo')
            ->assertSet('stadio', 2)
            ->assertSet('modoNuovoVeicolo', true);
    }

    public function test_avanza_a_stadio_3_con_veicolo_esistente(): void
    {
        $cliente = Cliente::create(['tipo' => 'fisica', 'nome' => 'Test', 'cognome' => 'User']);
        Veicolo::create(['tipo' => 'auto', 'targa' => 'AB123CD', 'marca' => 'Fiat', 'modello' => 'Punto', 'alimentazione' => 'benzina', 'km_attuali' => 30000, 'cliente_id' => $cliente->id]);

        Livewire::actingAs($this->user)
            ->test(CheckIn::class)
            ->set('targaInput', 'AB123CD')
            ->set('km_attuali', 35000)
            ->call('avanzaAlloStadio3')
            ->assertSet('stadio', 3);
    }

    public function test_km_inferiore_blocca_avanzamento(): void
    {
        $cliente = Cliente::create(['tipo' => 'fisica', 'nome' => 'Test', 'cognome' => 'User']);
        Veicolo::create(['tipo' => 'auto', 'targa' => 'AB123CD', 'marca' => 'Fiat', 'modello' => 'Punto', 'alimentazione' => 'benzina', 'km_attuali' => 80000, 'cliente_id' => $cliente->id]);

        Livewire::actingAs($this->user)
            ->test(CheckIn::class)
            ->set('targaInput', 'AB123CD')
            ->set('km_attuali', 50000)
            ->call('avanzaAlloStadio3')
            ->assertSet('stadio', 2)
            ->assertHasErrors('km_attuali');
    }

    public function test_apertura_odl_crea_commessa_e_redirige(): void
    {
        $cliente = Cliente::create(['tipo' => 'fisica', 'nome' => 'Test', 'cognome' => 'User']);
        Veicolo::create(['tipo' => 'auto', 'targa' => 'AB123CD', 'marca' => 'Fiat', 'modello' => 'Punto', 'alimentazione' => 'benzina', 'km_attuali' => 30000, 'cliente_id' => $cliente->id]);

        Livewire::actingAs($this->user)
            ->test(CheckIn::class)
            ->set('targaInput', 'AB123CD')
            ->set('km_attuali', 35000)
            ->call('avanzaAlloStadio3')
            ->set('tipo', 'meccanica')
            ->set('descrizione_cliente', 'Diagnosi freni')
            ->call('apriOdl', false)
            ->assertRedirect();

        $this->assertDatabaseHas('commesse', ['descrizione_cliente' => 'Diagnosi freni']);
    }

    public function test_flusso_nuovo_cliente_e_veicolo_crea_odl(): void
    {
        Livewire::actingAs($this->user)
            ->test(CheckIn::class)
            ->set('targaInput', 'NEW001XX')
            ->call('proseguiNuovoVeicolo')
            ->set('nv_tipo', 'auto')
            ->set('nv_marca', 'Renault')
            ->set('nv_modello', 'Clio')
            ->set('nv_alimentazione', 'benzina')
            ->set('nv_km', 10000)
            ->set('modoNuovoCliente', true)
            ->set('nc_tipo', 'fisica')
            ->set('nc_nome', 'Giovanna')
            ->set('nc_cognome', 'Neri')
            ->call('avanzaAlloStadio3')
            ->set('tipo', 'meccanica')
            ->set('descrizione_cliente', 'Prima revisione')
            ->call('apriOdl', false)
            ->assertRedirect();

        $this->assertDatabaseHas('clienti', ['nome' => 'Giovanna', 'cognome' => 'Neri']);
        $this->assertDatabaseHas('veicoli', ['targa' => 'NEW001XX', 'marca' => 'Renault']);
        $this->assertDatabaseHas('commesse', ['descrizione_cliente' => 'Prima revisione']);
    }

    public function test_flusso_funziona_senza_pacchetti_e_senza_lookup(): void
    {
        $cliente = Cliente::create(['tipo' => 'fisica', 'nome' => 'Test', 'cognome' => 'NoPlugin']);
        Veicolo::create(['tipo' => 'auto', 'targa' => 'NN000NN', 'marca' => 'Opel', 'modello' => 'Corsa', 'alimentazione' => 'diesel', 'km_attuali' => 0, 'cliente_id' => $cliente->id]);

        Livewire::actingAs($this->user)
            ->test(CheckIn::class)
            ->set('targaInput', 'NN000NN')
            ->call('avanzaAlloStadio3')
            ->set('descrizione_cliente', 'Tagliando base')
            ->call('apriOdl', false)
            ->assertRedirect();

        $this->assertDatabaseHas('commesse', ['descrizione_cliente' => 'Tagliando base']);
    }
}
