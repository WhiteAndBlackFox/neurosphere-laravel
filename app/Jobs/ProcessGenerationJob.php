<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\GenerationStatus;
use App\Models\GenerationTask;
use App\Services\ExternalAi\ExternalAiClientInterface;
use App\Services\GenerationQuotaService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Processes a single generation task against the external AI client.
 */
final class ProcessGenerationJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    /**
     * @var array<int, int>
     */
    public array $backoff = [10, 60, 300];

    public function __construct(
        public int $taskId,
    ) {}

    public function handle(ExternalAiClientInterface $client): void
    {
        $task = GenerationTask::findOrFail($this->taskId);

        // Pending or Processing may retry; Completed/Failed are terminal.
        if (in_array($task->status, [GenerationStatus::Completed, GenerationStatus::Failed], true)) {
            Log::info('Skipping generation task already finished.', ['task_id' => $task->id, 'status' => $task->status->value]);

            return;
        }

        $task->update(['status' => GenerationStatus::Processing]);

        if ($task->assets()->exists()) {
            $task->update(['status' => GenerationStatus::Completed]);

            return;
        }

        $result = $client->generate($task);

        $task->assets()->createMany($result->assets);
        $task->update(['status' => GenerationStatus::Completed]);

        Log::info('Generation task completed.', ['task_id' => $task->id]);
    }

    public function failed(Throwable $e): void
    {
        $task = GenerationTask::find($this->taskId);

        if (! $task) {
            return;
        }

        Log::error('Generation task failed permanently.', ['task_id' => $task->id, 'error' => $e->getMessage()]);

        $task->update([
            'status' => GenerationStatus::Failed,
            'error_message' => $e->getMessage(),
        ]);

        app(GenerationQuotaService::class)->refund($task);
    }
}
