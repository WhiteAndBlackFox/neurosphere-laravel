<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Enums\GenerationStatus;
use App\Enums\Plan;
use App\Exceptions\QuotaExceededException;
use App\Models\GenerationTask;
use App\Models\Subscription;
use App\Models\User;
use App\Services\GenerationQuotaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class GenerationQuotaServiceTest extends TestCase
{
    use RefreshDatabase;

    private GenerationQuotaService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new GenerationQuotaService;
    }

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

    public function test_reserve_increments_used_quota_when_under_limit(): void
    {
        $subscription = $this->createSubscription(monthlyLimit: 10, usedQuota: 3);

        $this->service->reserve($subscription->user);

        $this->assertSame(4, $subscription->refresh()->used_quota);
    }

    public function test_reserve_throws_and_leaves_quota_unchanged_when_exhausted(): void
    {
        $subscription = $this->createSubscription(monthlyLimit: 10, usedQuota: 10);

        try {
            $this->service->reserve($subscription->user);
            $this->fail('Expected QuotaExceededException was not thrown.');
        } catch (QuotaExceededException) {
            // expected
        }

        $this->assertSame(10, $subscription->refresh()->used_quota);
    }

    public function test_refund_decrements_used_quota_and_stamps_quota_refunded_at(): void
    {
        $subscription = $this->createSubscription(monthlyLimit: 10, usedQuota: 5);
        $task = $this->createTask($subscription->user);

        $this->service->refund($task);

        $this->assertSame(4, $subscription->refresh()->used_quota);
        $this->assertNotNull($task->refresh()->quota_refunded_at);
    }

    public function test_refund_is_idempotent_when_called_twice_for_the_same_task(): void
    {
        $subscription = $this->createSubscription(monthlyLimit: 10, usedQuota: 5);
        $task = $this->createTask($subscription->user);

        $this->service->refund($task);
        $firstRefundedAt = $task->refresh()->quota_refunded_at;

        $this->service->refund($task);

        $this->assertSame(4, $subscription->refresh()->used_quota);
        $this->assertTrue($firstRefundedAt->equalTo($task->refresh()->quota_refunded_at));
    }

    public function test_refund_never_decrements_used_quota_below_zero(): void
    {
        $subscription = $this->createSubscription(monthlyLimit: 10, usedQuota: 0);
        $task = $this->createTask($subscription->user);

        $this->service->refund($task);

        $this->assertSame(0, $subscription->refresh()->used_quota);
    }

    private function createTask(User $user): GenerationTask
    {
        return $user->generationTasks()->create([
            'type' => 'image',
            'prompt' => 'fail: broken prompt',
            'variations' => 1,
            'status' => GenerationStatus::Failed,
        ]);
    }
}
