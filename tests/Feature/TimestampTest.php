<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class Timestamp extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testTimestamp()
    {
        $response = $this->json('GET', '/Timestamp');
        $response->assertStatus(200)
                ->assertJson([
                        'Timestamp' => time(),
                ])
                ;
    }
}
