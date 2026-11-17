<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Enums\Plan;
use App\Jobs\ProcessGenerationJob;
use App\Models\GenerationTask;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class GenerationCreateTest extends TestCase
{
    use RefreshDatabase;

    private function createSubscription(int $monthlyLimit, int $usedQuota): Subscription
    {
        $user = User::factory()->create();

        return $user->subscription()->create([
            'plan' => Plan::Pro,
            'monthly_limit' => $monthlyLimit,
            'used_quota' => $usedQuota,
            'reset_at' => now()->addMonth(),
        ]);
    }

    public function test_it_creates_a_pending_task_and_dispatches_the_job(): void
    {
        Queue::fake();
        $subscription = $this->createSubscription(monthlyLimit: 10, usedQuota: 3);
        Sanctum::actingAs($subscription->user);

        $response = $this->postJson('/api/v1/generations', [
            'type' => 'image',
            'prompt' => 'neon city at night, cinematic',
            'variations' => 2,
        ]);

        $response->assertStatus(202)->assertJson([
            'data' => ['status' => 'pending', 'quota_remaining' => 6],
        ]);
        Queue::assertPushed(ProcessGenerationJob::class);
    }

    public function test_it_returns_422_for_invalid_payloads(): void
    {
        $subscription = $this->createSubscription(monthlyLimit: 10, usedQuota: 0);
        Sanctum::actingAs($subscription->user);

        $this->postJson('/api/v1/generations', ['type' => 'video', 'prompt' => '', 'variations' => 9])
            ->assertStatus(422);
    }

    public function test_it_returns_quota_exceeded_when_exhausted(): void
    {
        Queue::fake();
        $subscription = $this->createSubscription(monthlyLimit: 10, usedQuota: 10);
        Sanctum::actingAs($subscription->user);

        $response = $this->postJson('/api/v1/generations', [
            'type' => 'image',
            'prompt' => 'neon city at night, cinematic',
            'variations' => 1,
        ]);

        $response->assertStatus(422)->assertJson(['error' => ['code' => 'QUOTA_EXCEEDED']]);
        $this->assertSame(0, GenerationTask::count());
        Queue::assertNotPushed(ProcessGenerationJob::class);
    }

    public function test_it_requires_authentication(): void
    {
        $this->postJson('/api/v1/generations', [
            'type' => 'image',
            'prompt' => 'neon city at night, cinematic',
            'variations' => 1,
        ])->assertStatus(401);
    }

    public function test_it_returns_401_json_even_without_an_accept_header(): void
    {
        // postJson() always sends Accept: application/json, which masks a
        // real bug: Laravel's default guest-redirect tries route('login'),
        // which doesn't exist on this API-only app, and 500s instead of
        // 401ing for any client (e.g. plain curl) that omits the header.
        $this->post('/api/v1/generations', [
            'type' => 'image',
            'prompt' => 'neon city at night, cinematic',
            'variations' => 1,
        ])->assertStatus(401)->assertHeader('Content-Type', 'application/json');
    }
}
