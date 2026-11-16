<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_clinic_data_endpoints_reject_unauthenticated_requests(): void
    {
        $this->getJson('/api/pasiens')->assertUnauthorized();
        $this->getJson('/api/dashboard')->assertUnauthorized();
    }

    public function test_staff_can_sign_in_use_the_api_and_revoke_their_token(): void
    {
        $user = User::factory()->create([
            'email' => 'staff@example.test',
            'password' => bcrypt('test-password'),
        ]);

        $login = $this->postJson('/api/auth/login', [
            'email' => 'staff@example.test',
            'password' => 'test-password',
        ])->assertOk()
          ->assertJsonPath('token_type', 'Bearer')
          ->assertJsonPath('user.email', 'staff@example.test');

        $token = $login->json('token');
        $this->withToken($token)->getJson('/api/pasiens')->assertOk();

        $this->withToken($token)->postJson('/api/auth/logout')->assertOk();
        $this->withToken($token)->getJson('/api/pasiens')->assertUnauthorized();
    }

    public function test_invalid_credentials_do_not_issue_a_token(): void
    {
        User::factory()->create([
            'email' => 'staff@example.test',
            'password' => bcrypt('correct-password'),
        ]);

        $this->postJson('/api/auth/login', [
            'email' => 'staff@example.test',
            'password' => 'wrong-password',
        ])->assertUnauthorized()->assertJsonMissingPath('token');
    }
}
