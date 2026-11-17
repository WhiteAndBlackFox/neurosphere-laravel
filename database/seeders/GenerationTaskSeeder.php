<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\GenerationStatus;
use App\Models\GenerationTask;
use App\Models\User;
use Illuminate\Database\Seeder;

class GenerationTaskSeeder extends Seeder
{
    /**
     * Seed tasks in every status, plus fail/slow prompts, for every demo user.
     */
    public function run(): void
    {
        $scenarios = [
            // [prompt, status, variations, error_message]
            ['neon city at night, cinematic', GenerationStatus::Completed, 2, null],
            ['forest spirit, watercolor', GenerationStatus::Completed, 1, null],
            ['portrait, studio light', GenerationStatus::Pending, 1, null],
            ['abstract shapes', GenerationStatus::Processing, 2, null],
            ['fail: broken prompt', GenerationStatus::Failed, 1, 'External AI timeout'],
            ['slow: cinematic dolly shot', GenerationStatus::Completed, 2, null],
        ];

        User::query()->each(function (User $user) use ($scenarios): void {
            foreach ($scenarios as [$prompt, $status, $variations, $error]) {
                GenerationTask::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'prompt' => $prompt,
                    ],
                    [
                        'type' => 'image',
                        'variations' => $variations,
                        'status' => $status,
                        'error_message' => $error,
                        'external_request_id' => $status === GenerationStatus::Completed
                            ? 'ext_'.md5($user->id.$prompt)
                            : null,
                        'quota_refunded_at' => $status === GenerationStatus::Failed ? now() : null,
                    ],
                );
            }
        });
    }
}
