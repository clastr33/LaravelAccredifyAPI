<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class AccredifyControllerTest extends TestCase
{
   // use RefreshDatabase;

    public array $payload;

    protected function setUp(): void
    {
        parent::setUp();
        // Create a user and authenticate
        $this->user = User::factory()->create();
        $this->actingAs($this->user, 'sanctum');

        $this->payload = [
            'data' => [
                'id' => '63c79bd9303530645d1cca00',
                'name' => 'Certificate of Completion',
                'recipient' => [
                    'name' => 'Marty McFly',
                    'email' => 'marty.mcfly@gmail.com'
                ],
                'issuer' => [
                    'name' => 'Accredify',
                    'identityProof' => [
                        'type' => 'DNS-DID',
                        'key' => 'did:ethr:0x05b642ff12a4ae545357d82ba4f786f3aed84214#controller',
                        'location' => 'ropstore.accredify.io'
                    ]
                ],
                'issued' => '2022-12-23T00:00:00+08:00'
            ],
            'signature' => [
                'type' => 'SHA3MerkleProof',
                'targetHash' => '288f94aadadf486cfdad84b9f4305f7d51eac62db18376d48180cc1dd2047a0e'
            ]
        ];
    }

    //API responds when the JSON data matches the expected recipient, issuer and signature.
    public function test_verification_success()
    {
        $response = $this->postJson('/api/v1/verify', $this->payload);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'issuer' => 'Accredify',
                    'result' => 'verified'
                ]
            ]);
    }

    //API responds with invalid_recipient when doesn't match the expected name.
    public function test_verification_invalid_recipient()
    {
        $payload = $this->payload;
        // Invalid recipient
        $payload['data']['recipient']['name'] = '';

        $response = $this->postJson('/api/v1/verify', $payload);

        $response->assertStatus(200)
            ->assertJson([
                'error' => 'invalid_recipient'
            ]);
    }


    //API responds with invalid_issuer when doesn't match the expected name.
    public function test_verification_invalid_issuer()
    {
        $payload = $this->payload;
        // Invalid issuer
        $payload['data']['issuer']['name'] = '';

        $response = $this->postJson('/api/v1/verify', $payload);

        $response->assertStatus(200)
            ->assertJson([
                'error' => 'invalid_issuer'
            ]);
    }

    //API responds with invalid_signature when doesn't match the expected hash.
    public function test_verification_invalid_signature()
    {
        $payload = $this->payload;
        // Invalid signature
        $payload['signature']['targetHash'] = '';

        $response = $this->postJson('/api/v1/verify', $payload);

        $response->assertStatus(200)
            ->assertJson([
                'error' => 'invalid_signature'
            ]);
    }
}
