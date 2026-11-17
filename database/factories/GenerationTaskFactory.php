<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\GenerationStatus;
use App\Models\GenerationTask;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GenerationTask>
 */
class GenerationTaskFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => 'image',
            'prompt' => fake()->sentence(),
            'variations' => fake()->numberBetween(1, 4),
            'status' => GenerationStatus::Pending,
            'external_request_id' => null,
            'error_message' => null,
            'quota_refunded_at' => null,
        ];
    }
}
