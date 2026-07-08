<?php

namespace Tests\Unit;

use App\Models\MatricePrezzo;
use App\Models\MatricePrezzoScaglione;
use App\Services\Pricing\MatricePrezzoService;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Bordi: costo_da inclusivo (>=), costo_a esclusivo (<), ultimo scaglione aperto (costo_a null).
 */
class MatricePrezzoServiceTest extends TestCase
{
    private MatricePrezzoService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new MatricePrezzoService();
    }

    private function makeMatrix(array $scaglioni): MatricePrezzo
    {
        $m = new MatricePrezzo(['nome' => 'test', 'is_default' => true, 'is_attiva' => true]);
        $m->setRelation('scaglioni', collect($scaglioni)->map(fn($s) => new MatricePrezzoScaglione($s)));
        return $m;
    }

    public function test_suggestPrice_returns_null_for_zero_cost(): void
    {
        $m = $this->makeMatrix([
            ['costo_da' => 0, 'costo_a' => null, 'markup_percent' => 50, 'arrotondamento' => 'none'],
        ]);
        $this->assertNull($this->service->suggestPrice(0, $m));
    }

    public function test_suggestPrice_returns_null_for_null_cost(): void
    {
        $m = $this->makeMatrix([
            ['costo_da' => 0, 'costo_a' => null, 'markup_percent' => 50, 'arrotondamento' => 'none'],
        ]);
        $this->assertNull($this->service->suggestPrice(null, $m));
    }

    public function test_costo_da_inclusivo(): void
    {
        // costo_da=10 inclusivo: cost=10 deve matchare scaglione 10-50
        $m = $this->makeMatrix([
            ['costo_da' =>  0, 'costo_a' => 10, 'markup_percent' => 100, 'arrotondamento' => 'none'],
            ['costo_da' => 10, 'costo_a' => null, 'markup_percent' => 50, 'arrotondamento' => 'none'],
        ]);
        // cost=10 → scaglione 10-null → 10 * 1.50 = 15.00
        $this->assertEquals('15.00', $this->service->suggestPrice(10, $m));
    }

    public function test_costo_a_esclusivo(): void
    {
        // costo_a=10 esclusivo: cost=9.99 matchá scaglione 0-10, cost=10 matchá 10-null
        $m = $this->makeMatrix([
            ['costo_da' =>  0, 'costo_a' => 10, 'markup_percent' => 100, 'arrotondamento' => 'none'],
            ['costo_da' => 10, 'costo_a' => null, 'markup_percent' => 10, 'arrotondamento' => 'none'],
        ]);
        // cost=9.99 → +100% = 19.98
        $this->assertEquals('19.98', $this->service->suggestPrice(9.99, $m));
        // cost=10.00 → +10% = 11.00
        $this->assertEquals('11.00', $this->service->suggestPrice(10, $m));
    }

    public function test_ultimo_scaglione_aperto(): void
    {
        $m = $this->makeMatrix([
            ['costo_da' =>   0, 'costo_a' =>  50, 'markup_percent' => 70, 'arrotondamento' => 'none'],
            ['costo_da' =>  50, 'costo_a' => null, 'markup_percent' => 30, 'arrotondamento' => 'none'],
        ]);
        // cost=999 → +30% = 1298.70
        $this->assertEquals('1298.70', $this->service->suggestPrice(999, $m));
    }

    public function test_arrotondamento_0_10(): void
    {
        $m = $this->makeMatrix([
            ['costo_da' => 0, 'costo_a' => null, 'markup_percent' => 0, 'arrotondamento' => '0.10'],
        ]);
        // 7.11 → ceil al 0.10 = 7.20
        $this->assertEquals('7.20', $this->service->suggestPrice(7.11, $m));
        // 7.10 → già multiplo = 7.10
        $this->assertEquals('7.10', $this->service->suggestPrice(7.10, $m));
    }

    public function test_arrotondamento_0_50(): void
    {
        $m = $this->makeMatrix([
            ['costo_da' => 0, 'costo_a' => null, 'markup_percent' => 50, 'arrotondamento' => '0.50'],
        ]);
        // 10 * 1.5 = 15.00 → già multiplo di 0.50
        $this->assertEquals('15.00', $this->service->suggestPrice(10, $m));
        // 7 * 1.5 = 10.50 → già multiplo
        $this->assertEquals('10.50', $this->service->suggestPrice(7, $m));
        // 7.01 * 1.5 = 10.515 → ceil 0.50 = 11.00
        $this->assertEquals('11.00', $this->service->suggestPrice(7.01, $m));
    }

    public function test_arrotondamento_1_00(): void
    {
        $m = $this->makeMatrix([
            ['costo_da' => 0, 'costo_a' => null, 'markup_percent' => 0, 'arrotondamento' => '1.00'],
        ]);
        // 7.01 → ceil 1 = 8
        $this->assertEquals('8.00', $this->service->suggestPrice(7.01, $m));
        // 8.00 → 8
        $this->assertEquals('8.00', $this->service->suggestPrice(8.00, $m));
    }

    public function test_validateScaglioni_ok(): void
    {
        $this->expectNotToPerformAssertions();
        $this->service->validateScaglioni([
            ['costo_da' =>  0, 'costo_a' =>  50, 'markup_percent' => 70, 'arrotondamento' => 'none'],
            ['costo_da' => 50, 'costo_a' => null, 'markup_percent' => 30, 'arrotondamento' => '0.50'],
        ]);
    }

    public function test_validateScaglioni_buco(): void
    {
        $this->expectException(ValidationException::class);
        $this->service->validateScaglioni([
            ['costo_da' =>  0, 'costo_a' =>  50, 'markup_percent' => 70, 'arrotondamento' => 'none'],
            ['costo_da' => 60, 'costo_a' => null, 'markup_percent' => 30, 'arrotondamento' => 'none'],
        ]);
    }

    public function test_validateScaglioni_ultimo_non_aperto(): void
    {
        $this->expectException(ValidationException::class);
        $this->service->validateScaglioni([
            ['costo_da' =>  0, 'costo_a' =>  50, 'markup_percent' => 70, 'arrotondamento' => 'none'],
            ['costo_da' => 50, 'costo_a' => 100, 'markup_percent' => 30, 'arrotondamento' => 'none'],
        ]);
    }

    public function test_validateScaglioni_null_non_ultimo(): void
    {
        $this->expectException(ValidationException::class);
        $this->service->validateScaglioni([
            ['costo_da' =>  0, 'costo_a' => null, 'markup_percent' => 70, 'arrotondamento' => 'none'],
            ['costo_da' => 50, 'costo_a' => null, 'markup_percent' => 30, 'arrotondamento' => 'none'],
        ]);
    }

    public function test_validateScaglioni_empty(): void
    {
        $this->expectException(ValidationException::class);
        $this->service->validateScaglioni([]);
    }
}
