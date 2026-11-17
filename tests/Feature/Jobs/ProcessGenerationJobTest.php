<?php

declare(strict_types=1);

namespace Tests\Feature\Jobs;

use App\Enums\GenerationStatus;
use App\Enums\Plan;
use App\Jobs\ProcessGenerationJob;
use App\Models\GenerationTask;
use App\Models\Subscription;
use App\Models\User;
use App\Services\ExternalAi\ExternalAiClientInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

final class ProcessGenerationJobTest extends TestCase
{
    use RefreshDatabase;

    private function createSubscription(int $usedQuota = 1): Subscription
    {
        $user = User::factory()->create();

        return $user->subscription()->create([
            'plan' => Plan::Pro,
            'monthly_limit' => 10,
            'used_quota' => $usedQuota,
            'reset_at' => now()->addMonth(),
        ]);
    }

    private function createTask(User $user, string $prompt, int $variations = 2): GenerationTask
    {
        return $user->generationTasks()->create([
            'type' => 'image',
            'prompt' => $prompt,
            'variations' => $variations,
            'status' => GenerationStatus::Pending,
        ]);
    }

    public function test_handle_completes_the_task_and_creates_its_assets(): void
    {
        $subscription = $this->createSubscription();
        $task = $this->createTask($subscription->user, 'neon city at night, cinematic', variations: 3);

        (new ProcessGenerationJob($task->id))->handle(app(ExternalAiClientInterface::class));

        $task->refresh();
        $this->assertSame(GenerationStatus::Completed, $task->status);
        $this->assertCount(3, $task->assets);
    }

    public function test_handle_is_a_no_op_when_the_task_is_not_pending(): void
    {
        $subscription = $this->createSubscription();
        $task = $this->createTask($subscription->user, 'neon city at night, cinematic');
        $task->update(['status' => GenerationStatus::Completed]);

        (new ProcessGenerationJob($task->id))->handle(app(ExternalAiClientInterface::class));

        $this->assertSame(GenerationStatus::Completed, $task->refresh()->status);
        $this->assertCount(0, $task->assets);
    }

    public function test_handle_does_not_duplicate_assets_on_a_crashed_retry(): void
    {
        $subscription = $this->createSubscription();
        $task = $this->createTask($subscription->user, 'neon city at night, cinematic', variations: 2);
        $task->update(['status' => GenerationStatus::Processing]);
        $task->assets()->create([
            'external_id' => 'ext_already_there',
            'url' => 'https://mock.ai/assets/0.png',
            'mime_type' => 'image/png',
            'size_bytes' => 1,
        ]);

        (new ProcessGenerationJob($task->id))->handle(app(ExternalAiClientInterface::class));

        $this->assertSame(GenerationStatus::Completed, $task->refresh()->status);
        $this->assertCount(1, $task->assets);
    }

    public function test_failed_marks_the_task_failed_with_the_error_message_and_refunds_quota(): void
    {
        $subscription = $this->createSubscription(usedQuota: 1);
        $task = $this->createTask($subscription->user, 'fail: broken prompt');

        (new ProcessGenerationJob($task->id))->failed(new RuntimeException('External AI timeout'));

        $task->refresh();
        $this->assertSame(GenerationStatus::Failed, $task->status);
        $this->assertSame('External AI timeout', $task->error_message);
        $this->assertNotNull($task->quota_refunded_at);
        $this->assertSame(0, $subscription->refresh()->used_quota);
    }
}
