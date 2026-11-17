<?php

declare(strict_types=1);

namespace Tests\Feature\Actions\Generations;

use App\Actions\Generations\CreateGenerationAction;
use App\DTOs\Generations\CreateGenerationData;
use App\Enums\GenerationStatus;
use App\Enums\Plan;
use App\Exceptions\QuotaExceededException;
use App\Jobs\ProcessGenerationJob;
use App\Models\GenerationTask;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

final class CreateGenerationActionTest extends TestCase
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

    public function test_it_reserves_quota_creates_a_pending_task_and_dispatches_the_job(): void
    {
        Queue::fake();

        $subscription = $this->createSubscription(monthlyLimit: 10, usedQuota: 3);
        $data = new CreateGenerationData(type: 'image', prompt: 'neon city at night, cinematic', variations: 2);

        $task = $this->app->make(CreateGenerationAction::class)->execute($subscription->user, $data);

        $this->assertSame(GenerationStatus::Pending, $task->status);
        $this->assertSame('image', $task->type);
        $this->assertSame('neon city at night, cinematic', $task->prompt);
        $this->assertSame(2, $task->variations);
        $this->assertSame(4, $subscription->refresh()->used_quota);

        Queue::assertPushed(ProcessGenerationJob::class, fn (ProcessGenerationJob $job): bool => $job->taskId === $task->id);
    }

    public function test_it_throws_and_rolls_back_when_quota_is_exhausted(): void
    {
        Queue::fake();

        $subscription = $this->createSubscription(monthlyLimit: 10, usedQuota: 10);
        $data = new CreateGenerationData(type: 'image', prompt: 'neon city at night, cinematic', variations: 1);

        $this->expectException(QuotaExceededException::class);

        try {
            $this->app->make(CreateGenerationAction::class)->execute($subscription->user, $data);
        } finally {
            $this->assertSame(10, $subscription->refresh()->used_quota);
            $this->assertSame(0, GenerationTask::count());
            Queue::assertNotPushed(ProcessGenerationJob::class);
        }
    }
}
