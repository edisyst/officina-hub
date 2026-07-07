<?php

namespace Tests\Unit;

use App\Enums\StatoAppuntamento;
use App\Enums\StatoCommessa;
use App\Enums\TipoCliente;
use App\Enums\TipoCommessa;
use App\Models\Appuntamento;
use App\Models\Commessa;
use App\Models\Lavorazione;
use App\Models\User;
use App\Models\Veicolo;
use App\Models\Cliente;
use App\Services\TechBoardService;
use Database\Seeders\RuoliSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TechBoardServiceTest extends TestCase
{
    use RefreshDatabase;

    private TechBoardService $service;
    private Cliente $cliente;
    private Veicolo $veicolo;
    private Commessa $commessa;
    private User $meccanico;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RuoliSeeder::class);
        $this->service = new TechBoardService();

        $this->meccanico = User::factory()->create(['name' => 'Mario Rossi']);
        $this->meccanico->assignRole('meccanico');

        $this->cliente = Cliente::create(['tipo' => TipoCliente::Fisica, 'nome' => 'Mario', 'cognome' => 'Bianchi']);
        $this->veicolo = Veicolo::create(['targa' => 'AB123CD', 'marca' => 'Fiat', 'modello' => 'Panda']);
        $this->commessa = Commessa::create([
            'numero'             => 'C-2026-001',
            'stato'              => StatoCommessa::InLavorazione,
            'tipo'               => TipoCommessa::Tagliando,
            'cliente_id'         => $this->cliente->id,
            'veicolo_id'         => $this->veicolo->id,
            'user_id'            => $this->meccanico->id,
            'data_ingresso'      => now(),
            'descrizione_cliente' => 'Test',
        ]);
    }

    public function test_has_estimated_minutes_returns_true(): void
    {
        $this->assertTrue($this->service->hasEstimatedMinutes());
    }

    public function test_lavorazioni_attive_returns_active_timers(): void
    {
        Lavorazione::create([
            'commessa_id'         => $this->commessa->id,
            'user_id'             => $this->meccanico->id,
            'started_at'          => now()->subMinutes(30),
            'stopped_at'          => null,
            'descrizione'         => 'Tagliando',
            'minuti_preventivati' => 60,
        ]);

        // Stopped — should not appear
        Lavorazione::create([
            'commessa_id' => $this->commessa->id,
            'user_id'     => $this->meccanico->id,
            'started_at'  => now()->subHour(),
            'stopped_at'  => now(),
            'descrizione' => 'Finita',
        ]);

        $result = $this->service->lavorazioniAttive();

        $this->assertCount(1, $result);
        $this->assertEquals('AB123CD', $result->first()['targa']);
        $this->assertEquals('Mario Rossi', $result->first()['meccanico_nome']);
        $this->assertEquals(60, $result->first()['minuti_preventivati']);
    }

    public function test_commesse_sospese_returns_sospesa_commesse(): void
    {
        Commessa::create([
            'numero'             => 'C-2026-002',
            'stato'              => StatoCommessa::Sospesa,
            'tipo'               => TipoCommessa::Tagliando,
            'cliente_id'         => $this->cliente->id,
            'veicolo_id'         => $this->veicolo->id,
            'user_id'            => $this->meccanico->id,
            'data_ingresso'      => now(),
            'descrizione_cliente' => 'Test',
        ]);

        $result = $this->service->commesseSospese();

        $this->assertCount(1, $result);
        $this->assertEquals('AB123CD', $result->first()['targa']);
        $this->assertEquals('Bianchi', $result->first()['cognome']);
    }

    public function test_prossimi_appuntamenti_returns_today_and_tomorrow(): void
    {
        $admin = User::factory()->create();
        $this->seed(RuoliSeeder::class);
        $admin->assignRole('admin');

        Appuntamento::create([
            'data_ora_inizio' => now()->setHour(9)->setMinute(0),
            'data_ora_fine'   => now()->setHour(10)->setMinute(0),
            'cliente_id'      => $this->cliente->id,
            'veicolo_id'      => $this->veicolo->id,
            'titolo'          => 'Tagliando',
            'stato'           => StatoAppuntamento::Confermato,
        ]);

        Appuntamento::create([
            'data_ora_inizio' => now()->addDay()->setHour(14)->setMinute(0),
            'data_ora_fine'   => now()->addDay()->setHour(15)->setMinute(0),
            'cliente_id'      => $this->cliente->id,
            'veicolo_id'      => $this->veicolo->id,
            'titolo'          => 'Revisione',
            'stato'           => StatoAppuntamento::Confermato,
        ]);

        // Day after tomorrow — should not appear
        Appuntamento::create([
            'data_ora_inizio' => now()->addDays(2)->setHour(10)->setMinute(0),
            'data_ora_fine'   => now()->addDays(2)->setHour(11)->setMinute(0),
            'cliente_id'      => $this->cliente->id,
            'veicolo_id'      => $this->veicolo->id,
            'titolo'          => 'Futuro',
            'stato'           => StatoAppuntamento::Confermato,
        ]);

        $result = $this->service->prossimiAppuntamenti();

        $this->assertCount(2, $result);
        $this->assertEquals('Oggi', $result->first()['giorno']);
        $this->assertEquals('Domani', $result->last()['giorno']);
    }
}
