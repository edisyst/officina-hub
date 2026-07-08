<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Impostazioni\MatriciPrezzo;
use App\Models\MatricePrezzo;
use App\Models\User;
use Database\Seeders\RuoliSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MatriciPrezzoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        $this->seed(RuoliSeeder::class);
    }

    private function admin(): User
    {
        return User::factory()->create()->assignRole('admin');
    }

    public function test_admin_can_view_component(): void
    {
        $this->actingAs($this->admin());
        Livewire::test(MatriciPrezzo::class)->assertOk();
    }

    public function test_crea_matrice(): void
    {
        $this->actingAs($this->admin());

        Livewire::test(MatriciPrezzo::class)
            ->call('apriNuovo')
            ->set('nome', 'Test Matrix')
            ->set('scaglioni', [
                ['costo_da' =>  '0.00', 'costo_a' => '50.00', 'markup_percent' => '70.00', 'arrotondamento' => 'none'],
                ['costo_da' => '50.00', 'costo_a' => '',       'markup_percent' => '40.00', 'arrotondamento' => '0.50'],
            ])
            ->call('salva')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('matrici_prezzo', ['nome' => 'Test Matrix']);
        $this->assertEquals(2, MatricePrezzo::where('nome', 'Test Matrix')->first()->scaglioni()->count());
    }

    public function test_scaglioni_con_buco_non_salva(): void
    {
        $this->actingAs($this->admin());

        Livewire::test(MatriciPrezzo::class)
            ->call('apriNuovo')
            ->set('nome', 'Bad Matrix')
            ->set('scaglioni', [
                ['costo_da' =>  '0.00', 'costo_a' => '30.00', 'markup_percent' => '70.00', 'arrotondamento' => 'none'],
                ['costo_da' => '50.00', 'costo_a' => '',       'markup_percent' => '40.00', 'arrotondamento' => 'none'],
            ])
            ->call('salva');

        $this->assertDatabaseMissing('matrici_prezzo', ['nome' => 'Bad Matrix']);
    }

    public function test_imposta_default_unico(): void
    {
        $this->actingAs($this->admin());

        $m1 = MatricePrezzo::factory()->withScaglioni()->create(['is_default' => true]);
        $m2 = MatricePrezzo::factory()->withScaglioni()->create(['is_default' => false]);

        Livewire::test(MatriciPrezzo::class)->call('impostaDefault', $m2->id);

        $this->assertFalse($m1->fresh()->is_default);
        $this->assertTrue($m2->fresh()->is_default);
    }
}
