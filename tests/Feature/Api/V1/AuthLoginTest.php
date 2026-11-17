<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class AuthLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_credentials_return_a_token(): void
    {
        $user = User::factory()->create(['password' => Hash::make('password')]);

        $response = $this->postJson('/api/v1/login', ['email' => $user->email, 'password' => 'password']);

        $response->assertOk()->assertJsonStructure(['data' => ['token']]);
    }

    public function test_invalid_credentials_are_rejected(): void
    {
        $user = User::factory()->create(['password' => Hash::make('password')]);

        $this->postJson('/api/v1/login', ['email' => $user->email, 'password' => 'wrong'])
            ->assertStatus(401);
    }
}
