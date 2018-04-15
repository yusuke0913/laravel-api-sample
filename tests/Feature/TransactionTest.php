<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class Transaction extends TestCase
{

    public function testVerifier()
    {
        $verifier = "test";
        $response = $this->json('POST', '/Transaction', [
                'TransactionId' => 1,
                'UserId' => 1,
                'CurrencyAmount' => 1,
                'Verifier' => $verifier,
        ]);

        $response->assertStatus(200)
                ->assertJson(['Error' => true, 'ErrorMessage' => 'INVALID_PARAMETER'])
        ;
    }

    public function testStatus()
    {
        $userId = 1;
        $response = $this->json('POST', '/TransactionStats', [
                'UserId' => $userId,
        ]);

        $response->assertStatus(200)
                ->assertJsonMissing(['Error' => true, 'ErrorMessage' => 'ErrorMessage'])
                ->assertJsonStructure(['UserId','TransactionCount', 'CurrencySum'])
        ;
    }

    public function testDuplicate()
    {
        $verifier = "f20c59de4a0ee239248e76e28b030c27211ef1e4";
        $response = $this->json('POST', '/Transaction', [
                'TransactionId' => 1,
                'UserId' => 1,
                'CurrencyAmount' => 1,
                'Verifier' => $verifier,
        ]);
        $response->assertStatus(200)
                ->assertJson(['Error' => true, 'ErrorMessage' => 'DPULICATE_TRANSACTION_ID'])
        ;
    }

}
