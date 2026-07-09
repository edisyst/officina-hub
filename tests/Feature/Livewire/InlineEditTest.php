<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Magazzino\ListaArticoli;
use App\Models\Articolo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InlineEditTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        foreach (['admin', 'accettatore', 'meccanico', 'cassa'] as $r) {
            Role::firstOrCreate(['name' => $r, 'guard_name' => 'web']);
        }
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
    }

    private function makeArticolo(array $attrs = []): Articolo
    {
        static $n = 0;
        $n++;
        return Articolo::create(array_merge([
            'codice'           => 'IE-' . $n,
            'descrizione'      => 'Articolo IE ' . $n,
            'unita_misura'     => 'pz',
            'prezzo_acquisto'  => 5.00,
            'prezzo_vendita'   => 12.00,
            'iva_percentuale'  => 22,
            'scorta_minima'    => 2,
            'giacenza_attuale' => 8,
            'attivo'           => true,
        ], $attrs));
    }

    public function test_inline_edit_prezzo_vendita(): void
    {
        $art = $this->makeArticolo(['prezzo_vendita' => 12.00]);

        Livewire::actingAs($this->admin)
            ->test(ListaArticoli::class)
            ->call('salvaInlineEdit', $art->id, 'prezzo_vendita', 15.50);

        $this->assertEquals(15.50, (float) $art->fresh()->prezzo_vendita);
    }

    public function test_inline_edit_scorta_minima(): void
    {
        $art = $this->makeArticolo(['scorta_minima' => 2]);

        Livewire::actingAs($this->admin)
            ->test(ListaArticoli::class)
            ->call('salvaInlineEdit', $art->id, 'scorta_minima', 10);

        $this->assertEquals(10, $art->fresh()->scorta_minima);
    }

    public function test_inline_edit_invalid_value_does_not_save(): void
    {
        $art = $this->makeArticolo(['prezzo_vendita' => 10.00]);

        Livewire::actingAs($this->admin)
            ->test(ListaArticoli::class)
            ->call('salvaInlineEdit', $art->id, 'prezzo_vendita', -5);

        // negative price rejected
        $this->assertEquals(10.00, (float) $art->fresh()->prezzo_vendita);
    }

    public function test_inline_edit_unauthorized_user(): void
    {
        $meccanico = User::factory()->create();
        $meccanico->assignRole('meccanico');

        $art = $this->makeArticolo(['ubicazione' => 'OLD']);

        Livewire::actingAs($meccanico)
            ->test(ListaArticoli::class)
            ->call('salvaInlineEdit', $art->id, 'ubicazione', 'NEW')
            ->assertStatus(403);

        $this->assertEquals('OLD', $art->fresh()->ubicazione);
    }
}
