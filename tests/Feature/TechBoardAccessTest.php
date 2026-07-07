<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TechBoardAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        config(['techboard.token' => 'secret-test-token-1234567890abcdefghijklmnopqrstuvwxyz1234567890abcdef']);
    }

    public function test_correct_token_returns_200(): void
    {
        $response = $this->get('/board/secret-test-token-1234567890abcdefghijklmnopqrstuvwxyz1234567890abcdef');
        $response->assertStatus(200);
    }

    public function test_wrong_token_returns_404(): void
    {
        $response = $this->get('/board/wrong-token');
        $response->assertStatus(404);
    }

    public function test_empty_token_in_config_returns_404(): void
    {
        config(['techboard.token' => '']);
        $response = $this->get('/board/anything');
        $response->assertStatus(404);
    }
}
