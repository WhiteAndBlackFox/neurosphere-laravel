<?php

declare(strict_types=1);

namespace App\Actions\Generations;

use App\DTOs\Generations\CreateGenerationData;
use App\Enums\GenerationStatus;
use App\Exceptions\QuotaExceededException;
use App\Jobs\ProcessGenerationJob;
use App\Models\GenerationTask;
use App\Models\User;
use App\Services\GenerationQuotaService;
use Illuminate\Support\Facades\DB;

final class CreateGenerationAction
{
    public function __construct(
        private readonly GenerationQuotaService $quota,
    ) {}

    /**
     * Reserve quota, create the task, and dispatch its processing job.
     *
     * @throws QuotaExceededException
     */
    public function execute(User $user, CreateGenerationData $data): GenerationTask
    {
        return DB::transaction(function () use ($user, $data): GenerationTask {
            $this->quota->reserve($user);

            $task = $user->generationTasks()->create([
                'type' => $data->type,
                'prompt' => $data->prompt,
                'variations' => $data->variations,
                'status' => GenerationStatus::Pending,
            ]);

            ProcessGenerationJob::dispatch($task->id)->afterCommit();

            return $task;
        });
    }
}
