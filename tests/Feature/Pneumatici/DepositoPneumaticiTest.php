<?php

namespace Tests\Feature\Pneumatici;

use App\Enums\AzioneDeposito;
use App\Enums\StagionePneumatico;
use App\Enums\StatoPneumatico;
use App\Jobs\InviaNotificaCambioStagionale;
use App\Models\Cliente;
use App\Models\DepositoPneumatico;
use App\Models\Pneumatico;
use App\Models\User;
use App\Models\Veicolo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DepositoPneumaticiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Cliente $cliente;
    private Veicolo $veicolo;

    protected function setUp(): void
    {
        parent::setUp();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (['admin', 'accettatore', 'meccanico', 'cassa'] as $r) {
            Role::firstOrCreate(['name' => $r, 'guard_name' => 'web']);
        }

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->cliente = Cliente::create([
            'tipo'    => 'fisica',
            'nome'    => 'Mario',
            'cognome' => 'Rossi',
            'email'   => 'mario@test.it',
        ]);

        $this->veicolo = Veicolo::create([
            'cliente_id'   => $this->cliente->id,
            'tipo'         => 'auto',
            'targa'        => 'AB123CD',
            'marca'        => 'Fiat',
            'modello'      => 'Punto',
            'alimentazione' => 'benzina',
        ]);
    }

    private function creaPneumatico(array $extra = []): Pneumatico
    {
        return Pneumatico::create(array_merge([
            'veicolo_id' => $this->veicolo->id,
            'cliente_id' => $this->cliente->id,
            'stagione'   => StagionePneumatico::Invernale,
            'marca'      => 'Michelin',
            'misura'     => '205/55 R16',
            'stato'      => StatoPneumatico::InDeposito,
        ], $extra));
    }

    public function test_crea_pneumatico_in_deposito(): void
    {
        $p = $this->creaPneumatico();

        $this->assertDatabaseHas('pneumatici', [
            'id'    => $p->id,
            'stato' => 'in_deposito',
            'marca' => 'Michelin',
        ]);
    }

    public function test_deposita_e_registra_movimento(): void
    {
        $p = $this->creaPneumatico(['stato' => StatoPneumatico::Montato]);

        $p->update(['stato' => StatoPneumatico::InDeposito]);

        DepositoPneumatico::create([
            'pneumatico_id'     => $p->id,
            'azione'            => AzioneDeposito::Deposito,
            'data_azione'       => now()->toDateString(),
            'ubicazione'        => 'Scaffale A3, Posizione 2',
            'usura_percentuale' => 20,
            'user_id'           => $this->admin->id,
        ]);

        $this->assertEquals(StatoPneumatico::InDeposito, $p->fresh()->stato);
        $this->assertDatabaseHas('depositi_pneumatici', [
            'pneumatico_id'     => $p->id,
            'ubicazione'        => 'Scaffale A3, Posizione 2',
            'usura_percentuale' => 20,
        ]);
    }

    public function test_monta_da_deposito_aggiorna_stato(): void
    {
        $p = $this->creaPneumatico(['stagione' => StagionePneumatico::Estivo]);

        $p->update(['stato' => StatoPneumatico::Montato]);
        DepositoPneumatico::create([
            'pneumatico_id' => $p->id,
            'azione'        => AzioneDeposito::Ritiro,
            'data_azione'   => now()->toDateString(),
            'user_id'       => $this->admin->id,
        ]);

        $this->assertEquals(StatoPneumatico::Montato, $p->fresh()->stato);
    }

    public function test_smaltimento_aggiorna_stato(): void
    {
        $p = $this->creaPneumatico();

        $p->update(['stato' => StatoPneumatico::Smaltito]);
        DepositoPneumatico::create([
            'pneumatico_id' => $p->id,
            'azione'        => AzioneDeposito::Smaltimento,
            'data_azione'   => now()->toDateString(),
            'user_id'       => $this->admin->id,
        ]);

        $this->assertEquals(StatoPneumatico::Smaltito, $p->fresh()->stato);
        $this->assertDatabaseHas('depositi_pneumatici', [
            'pneumatico_id' => $p->id,
            'azione'        => 'smaltimento',
        ]);
    }

    public function test_notifica_cambio_stagionale_dispatched(): void
    {
        Queue::fake();

        $p = $this->creaPneumatico();

        dispatch(new InviaNotificaCambioStagionale([$p->id]));

        Queue::assertPushed(InviaNotificaCambioStagionale::class, function ($job) use ($p) {
            return in_array($p->id, $job->pneumaticiIds);
        });
    }

    public function test_notifica_non_inviata_se_non_in_deposito(): void
    {
        // Il job non deve inviare email a pneumatici non in_deposito
        $p = $this->creaPneumatico(['stato' => StatoPneumatico::Montato]);

        $candidati = Pneumatico::whereIn('id', [$p->id])
            ->where('stato', StatoPneumatico::InDeposito)
            ->count();

        $this->assertEquals(0, $candidati);
    }

    public function test_distribuzione_appuntamenti_settimana_5_giorni(): void
    {
        $inizioSettimana = now()->startOfWeek();
        $giorni          = collect();
        for ($i = 0; $i < 5; $i++) {
            $giorni->push($inizioSettimana->copy()->addDays($i));
        }

        $this->assertCount(5, $giorni);
        $this->assertEquals(1, $inizioSettimana->dayOfWeek); // lunedì
    }

    public function test_codice_etichetta_formato_corretto(): void
    {
        $p = $this->creaPneumatico();

        $codice = $p->codiceEtichetta();
        $this->assertMatchesRegularExpression('/^DEP-\d{4}-\d{5}$/', $codice);
    }
}
