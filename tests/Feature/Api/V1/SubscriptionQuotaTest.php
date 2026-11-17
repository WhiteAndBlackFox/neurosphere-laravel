<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Enums\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class SubscriptionQuotaTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_the_authenticated_users_quota(): void
    {
        $user = User::factory()->create();
        $resetAt = now()->addMonth()->startOfMonth();
        $user->subscription()->create([
            'plan' => Plan::Pro,
            'monthly_limit' => 100,
            'used_quota' => 93,
            'reset_at' => $resetAt,
        ]);
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/subscription/quota')->assertOk()->assertJson([
            'data' => [
                'plan' => 'pro',
                'monthly_limit' => 100,
                'used' => 93,
                'remaining' => 7,
                'reset_at' => $resetAt->toISOString(),
            ],
        ]);
    }
}
