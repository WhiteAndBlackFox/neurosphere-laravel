<?php

declare(strict_types=1);

namespace App\Services\ExternalAi;

use App\Exceptions\ExternalAi\ExternalAiException;
use App\Models\GenerationTask;
use Illuminate\Support\Str;

/**
 * Mock external AI provider: makes no real HTTP calls.
 *
 * Prompts containing "fail" simulate a provider failure, and prompts
 * containing "slow" simulate a slow response. This client is NOT itself
 * idempotent across retries — a repeated call returns fresh asset ids
 * every time, since `Str::random()` makes each `external_id` unique.
 * Protecting against duplicate assets on a retried job is the Job's
 * responsibility (it must not call this client again once a task
 * already has assets), not this client's.
 */
final class FakeExternalAiClient implements ExternalAiClientInterface
{
    public function generate(GenerationTask $task): ExternalGenerationResult
    {
        if (str_contains($task->prompt, 'fail')) {
            throw new ExternalAiException("Simulated external AI failure for task {$task->id}.");
        }

        if (str_contains($task->prompt, 'slow')) {
            usleep((int) config('services.external_ai.slow_delay_ms') * 1000);
        }

        $assets = [];

        for ($i = 0; $i < $task->variations; $i++) {
            $assets[] = [
                'external_id' => sprintf('ext_%d_%d_%s', $task->id, $i, Str::random(8)),
                'url' => "https://mock.ai/assets/{$task->id}_{$i}.png",
                'mime_type' => 'image/png',
                'size_bytes' => random_int(100_000, 2_000_000),
            ];
        }

        return new ExternalGenerationResult($assets);
    }
}
