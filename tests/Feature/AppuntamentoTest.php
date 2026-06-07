<?php

namespace Tests\Feature;

use App\Enums\StatoCommessa;
use App\Livewire\Agenda\CalendarioAppuntamenti;
use App\Models\Appuntamento;
use App\Models\Commessa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AppuntamentoTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RuoliSeeder::class);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
    }

    public function test_calendario_appuntamenti_si_carica(): void
    {
        Livewire::actingAs($this->admin)
            ->test(CalendarioAppuntamenti::class)
            ->assertOk();
    }

    public function test_creazione_appuntamento_via_livewire(): void
    {
        Livewire::actingAs($this->admin)
            ->test(CalendarioAppuntamenti::class)
            ->call('apriModalNuovo', now()->toIso8601String(), now()->addHour()->toIso8601String())
            ->set('titolo', 'Tagliando Test')
            ->set('stato', 'pianificato')
            ->call('salva')
            ->assertDispatched('calendar-refresh');

        $this->assertDatabaseHas('appuntamenti', ['titolo' => 'Tagliando Test']);
    }

    public function test_spostamento_appuntamento_persiste_nel_db(): void
    {
        $app = Appuntamento::create([
            'titolo'          => 'Test Appuntamento',
            'data_ora_inizio' => now()->startOfDay()->addHours(9),
            'data_ora_fine'   => now()->startOfDay()->addHours(10),
            'stato'           => 'pianificato',
        ]);

        $nuovoInizio = now()->startOfDay()->addDay()->addHours(9)->format('Y-m-d\TH:i:s');
        $nuovaFine   = now()->startOfDay()->addDay()->addHours(10)->format('Y-m-d\TH:i:s');

        Livewire::actingAs($this->admin)
            ->test(CalendarioAppuntamenti::class)
            ->call('sposta', $app->id, $nuovoInizio, $nuovaFine);

        $app->refresh();
        $this->assertEquals(
            now()->startOfDay()->addDay()->addHours(9)->format('Y-m-d H:i'),
            $app->data_ora_inizio->format('Y-m-d H:i')
        );
    }
}
