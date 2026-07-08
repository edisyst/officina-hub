<?php

namespace Tests\Feature;

use App\Enums\StatoCommessa;
use App\Enums\TipoCliente;
use App\Enums\TipoCommessa;
use App\Enums\TipoRiga;
use App\Models\Articolo;
use App\Models\Cliente;
use App\Models\Commessa;
use App\Models\MatricePrezzo;
use App\Models\User;
use App\Models\Veicolo;
use Database\Seeders\RuoliSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommessaRigaPricingTest extends TestCase
{
    use RefreshDatabase;

    private function creaCommessa(): Commessa
    {
        $this->seed(RuoliSeeder::class);
        $cliente = Cliente::create(['tipo' => TipoCliente::Fisica, 'nome' => 'Test', 'cognome' => 'Pricing']);
        $veicolo = Veicolo::create(['marca' => 'Fiat', 'modello' => '500']);
        $user    = User::factory()->create();

        return Commessa::create([
            'numero'              => 'PRC-001',
            'stato'               => StatoCommessa::Bozza,
            'tipo'                => TipoCommessa::Meccanica,
            'data_ingresso'       => now(),
            'descrizione_cliente' => '',
            'cliente_id'          => $cliente->id,
            'veicolo_id'          => $veicolo->id,
            'user_id'             => $user->id,
        ]);
    }

    private function makeMatriceDefault(): MatricePrezzo
    {
        $m = MatricePrezzo::create(['nome' => 'Default', 'is_default' => true, 'is_attiva' => true]);
        $m->scaglioni()->createMany([
            ['costo_da' =>  0, 'costo_a' =>  50, 'markup_percent' => 100, 'arrotondamento' => 'none'],
            ['costo_da' => 50, 'costo_a' => null, 'markup_percent' =>  50, 'arrotondamento' => 'none'],
        ]);
        return $m;
    }

    private function creaArticolo(string $prezzoAcquisto = '20.00'): Articolo
    {
        return Articolo::create([
            'codice'           => 'ART-' . uniqid(),
            'descrizione'      => 'Test articolo',
            'prezzo_acquisto'  => $prezzoAcquisto,
            'prezzo_vendita'   => '1.00',
            'iva_percentuale'  => 22,
            'giacenza_attuale' => 10,
        ]);
    }

    public function test_prezzo_suggerito_auto_su_nuova_riga(): void
    {
        $this->makeMatriceDefault();
        $commessa = $this->creaCommessa();
        $articolo = $this->creaArticolo('20.00');

        $riga = $commessa->righe()->create([
            'tipo'            => TipoRiga::Articolo,
            'articolo_id'     => $articolo->id,
            'descrizione'     => 'Test',
            'quantita'        => 1,
            'prezzo_unitario' => 0,
            'iva_percentuale' => 22,
            'ordinamento'     => 1,
        ]);

        // 20.00 * (1 + 100/100) = 40.00 (scaglione 0-50 +100%)
        $this->assertEquals('40.00', $riga->fresh()->prezzo_unitario);
    }

    public function test_prezzo_non_sovrascritto_se_specificato(): void
    {
        $this->makeMatriceDefault();
        $commessa = $this->creaCommessa();
        $articolo = $this->creaArticolo('20.00');

        $riga = $commessa->righe()->create([
            'tipo'            => TipoRiga::Articolo,
            'articolo_id'     => $articolo->id,
            'descrizione'     => 'Test',
            'quantita'        => 1,
            'prezzo_unitario' => 99.99,
            'iva_percentuale' => 22,
            'ordinamento'     => 1,
        ]);

        $this->assertEquals('99.99', $riga->fresh()->prezzo_unitario);
    }

    public function test_senza_matrice_attiva_prezzo_rimane_zero(): void
    {
        // Nessuna matrice nel DB
        $commessa = $this->creaCommessa();
        $articolo = $this->creaArticolo('20.00');

        $riga = $commessa->righe()->create([
            'tipo'            => TipoRiga::Articolo,
            'articolo_id'     => $articolo->id,
            'descrizione'     => 'Test',
            'quantita'        => 1,
            'prezzo_unitario' => 0,
            'iva_percentuale' => 22,
            'ordinamento'     => 1,
        ]);

        $this->assertEquals('0.00', $riga->fresh()->prezzo_unitario);
    }
}
