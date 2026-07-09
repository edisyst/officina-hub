<?php

namespace Tests\Unit;

use App\DataTransferObjects\VehicleStatusCard;
use App\Enums\StatoCommessa;
use App\Enums\TipoCliente;
use App\Enums\TipoMovimento;
use App\Enums\TipoRiga;
use App\Models\Articolo;
use App\Models\Cliente;
use App\Models\Commessa;
use App\Models\CommessaRiga;
use App\Models\MovimentoMagazzino;
use App\Models\User;
use App\Models\Veicolo;
use App\Services\VehicleStatus\VehicleStatusService;
use Database\Seeders\RuoliSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehicleStatusServiceTest extends TestCase
{
    use RefreshDatabase;

    private VehicleStatusService $service;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RuoliSeeder::class);
        $this->user = User::factory()->create();
        $this->service = app(VehicleStatusService::class);
    }

    private function makeVeicoloConCliente(array $veicoloData = [], array $clienteData = []): array
    {
        $cliente = Cliente::factory()->create(array_merge([
            'tipo'    => TipoCliente::Fisica,
            'nome'    => 'Mario',
            'cognome' => 'Rossi',
        ], $clienteData));

        $veicolo = Veicolo::factory()->create(array_merge([
            'targa'      => 'AB123CD',
            'marca'      => 'Fiat',
            'modello'    => 'Panda',
            'cliente_id' => $cliente->id,
        ], $veicoloData));

        $veicolo->clienti()->attach($cliente->id);

        return [$veicolo, $cliente];
    }

    private function makeCommessa(Veicolo $veicolo, Cliente $cliente, array $data = []): Commessa
    {
        return Commessa::factory()->create(array_merge([
            'veicolo_id' => $veicolo->id,
            'cliente_id' => $cliente->id,
            'user_id'    => $this->user->id,
            'stato'      => StatoCommessa::InLavorazione,
            'numero'     => 'OdL-001',
        ], $data));
    }

    // --- Match tests ---

    public function test_trova_per_targa_prefix(): void
    {
        [$veicolo] = $this->makeVeicoloConCliente(['targa' => 'AB123CD']);

        $results = $this->service->lookup('AB1');

        $this->assertCount(1, $results);
        $this->assertInstanceOf(VehicleStatusCard::class, $results->first());
        $this->assertSame('AB123CD', $results->first()->targa);
    }

    public function test_trova_per_cognome(): void
    {
        [$veicolo] = $this->makeVeicoloConCliente([], ['cognome' => 'Bianchi']);

        $results = $this->service->lookup('Bian');

        $this->assertCount(1, $results);
    }

    public function test_trova_per_telefono(): void
    {
        [$veicolo] = $this->makeVeicoloConCliente([], ['telefono' => '3331234567']);

        $results = $this->service->lookup('3331');

        $this->assertCount(1, $results);
    }

    public function test_nessun_risultato_con_meno_di_due_caratteri(): void
    {
        $this->makeVeicoloConCliente(['targa' => 'ZZ999ZZ']);

        $this->assertEmpty($this->service->lookup('Z'));
        $this->assertEmpty($this->service->lookup(''));
    }

    public function test_max_cinque_risultati(): void
    {
        for ($i = 1; $i <= 7; $i++) {
            [$v] = $this->makeVeicoloConCliente(
                ['targa' => "AA{$i}00BB"],
                ['cognome' => "Verdi{$i}", 'email' => "verdi{$i}@test.it"],
            );
        }

        $results = $this->service->lookup('AA');
        $this->assertCount(5, $results);
    }

    // --- Semaforo tests ---

    public function test_semaforo_grigio_senza_ricambi(): void
    {
        [$v, $c] = $this->makeVeicoloConCliente();
        $this->makeCommessa($v, $c); // nessuna riga articolo

        $card = $this->service->lookup('AB1')->first();

        $this->assertSame('grigio', $card->semaforoRicambi);
        $this->assertEmpty($card->ricambiMancanti);
    }

    public function test_semaforo_verde_giacenza_sufficiente(): void
    {
        [$v, $c] = $this->makeVeicoloConCliente();
        $commessa = $this->makeCommessa($v, $c);

        $articolo = Articolo::factory()->create(['giacenza_attuale' => 10]);
        CommessaRiga::factory()->create([
            'commessa_id' => $commessa->id,
            'tipo'        => TipoRiga::Articolo,
            'articolo_id' => $articolo->id,
            'quantita'    => 2,
        ]);

        $card = $this->service->lookup('AB1')->first();

        $this->assertSame('verde', $card->semaforoRicambi);
    }

    public function test_semaforo_verde_se_gia_scaricato(): void
    {
        [$v, $c] = $this->makeVeicoloConCliente();
        $commessa = $this->makeCommessa($v, $c);

        $articolo = Articolo::factory()->create(['giacenza_attuale' => 0]);
        CommessaRiga::factory()->create([
            'commessa_id' => $commessa->id,
            'tipo'        => TipoRiga::Articolo,
            'articolo_id' => $articolo->id,
            'quantita'    => 2,
        ]);

        // Movimento scarico registrato per questa commessa
        MovimentoMagazzino::factory()->create([
            'articolo_id' => $articolo->id,
            'tipo'        => TipoMovimento::Scarico,
            'quantita'    => 2,
            'commessa_id' => $commessa->id,
        ]);

        $card = $this->service->lookup('AB1')->first();

        $this->assertSame('verde', $card->semaforoRicambi);
    }

    public function test_semaforo_giallo_giacenza_insufficiente(): void
    {
        [$v, $c] = $this->makeVeicoloConCliente();
        $commessa = $this->makeCommessa($v, $c);

        $articolo = Articolo::factory()->create([
            'descrizione'      => 'Filtro olio',
            'giacenza_attuale' => 0,
        ]);
        CommessaRiga::factory()->create([
            'commessa_id' => $commessa->id,
            'tipo'        => TipoRiga::Articolo,
            'articolo_id' => $articolo->id,
            'quantita'    => 1,
            'descrizione' => 'Filtro olio',
        ]);

        $card = $this->service->lookup('AB1')->first();

        $this->assertSame('giallo', $card->semaforoRicambi);
        $this->assertCount(1, $card->ricambiMancanti);
        $this->assertSame('Filtro olio', $card->ricambiMancanti[0]['descrizione']);
    }

    // --- OdL attivo vs storico ---

    public function test_odl_attivo_presente(): void
    {
        [$v, $c] = $this->makeVeicoloConCliente();
        $commessa = $this->makeCommessa($v, $c, [
            'stato'  => StatoCommessa::InLavorazione,
            'numero' => 'OdL-099',
        ]);

        $card = $this->service->lookup('AB1')->first();

        $this->assertSame($commessa->id, $card->commessaId);
        $this->assertSame('OdL-099', $card->commessaNumero);
        $this->assertSame(StatoCommessa::InLavorazione, $card->commessaStato);
        $this->assertNull($card->ultimaConsegnaLabel);
    }

    public function test_fallback_storico_se_nessun_odl_attivo(): void
    {
        [$v, $c] = $this->makeVeicoloConCliente();
        $this->makeCommessa($v, $c, [
            'stato'         => StatoCommessa::Consegnata,
            'data_consegna' => now()->subDays(5),
        ]);

        $card = $this->service->lookup('AB1')->first();

        $this->assertNull($card->commessaId);
        $this->assertNotNull($card->ultimaConsegnaLabel);
        $this->assertStringContainsString('Consegnata il', $card->ultimaConsegnaLabel);
    }

    // --- Feature check degrado: Step 28 (data_ora_consegna_prevista) ---

    public function test_consegna_prevista_null_se_non_impostata(): void
    {
        [$v, $c] = $this->makeVeicoloConCliente();
        $this->makeCommessa($v, $c, ['data_ora_consegna_prevista' => null]);

        $card = $this->service->lookup('AB1')->first();

        $this->assertNull($card->consegnaPrevista);
    }

    public function test_consegna_prevista_popolata_se_presente(): void
    {
        [$v, $c] = $this->makeVeicoloConCliente();
        $attesa = now()->addDays(3);
        $this->makeCommessa($v, $c, ['data_ora_consegna_prevista' => $attesa]);

        $card = $this->service->lookup('AB1')->first();

        $this->assertNotNull($card->consegnaPrevista);
    }

    // --- Query count (N+1 guard) ---

    public function test_no_n_plus_1_con_piu_veicoli(): void
    {
        for ($i = 1; $i <= 3; $i++) {
            [$v, $c] = $this->makeVeicoloConCliente(
                ['targa' => "TS{$i}00TS"],
                ['cognome' => "Test{$i}", 'email' => "t{$i}@t.it"],
            );
            $cm = $this->makeCommessa($v, $c, ['numero' => "OdL-T{$i}"]);
            $art = Articolo::factory()->create(['giacenza_attuale' => 0]);
            CommessaRiga::factory()->create([
                'commessa_id' => $cm->id,
                'tipo'        => TipoRiga::Articolo,
                'articolo_id' => $art->id,
                'quantita'    => 1,
            ]);
        }

        $queryCount = 0;
        \Illuminate\Support\Facades\DB::listen(function () use (&$queryCount) {
            $queryCount++;
        });

        $this->service->lookup('TS');

        // Nessun N+1: poche query batch indipendentemente dal numero di risultati
        $this->assertLessThanOrEqual(12, $queryCount);
    }
}
