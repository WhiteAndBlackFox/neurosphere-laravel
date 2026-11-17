<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Enums\GenerationStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class GenerationShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_view_their_own_task(): void
    {
        $user = User::factory()->create();
        $task = $user->generationTasks()->create([
            'type' => 'image',
            'prompt' => 'neon city at night, cinematic',
            'variations' => 2,
            'status' => GenerationStatus::Completed,
        ]);
        Sanctum::actingAs($user);

        $this->getJson("/api/v1/generations/{$task->id}")
            ->assertOk()
            ->assertJson(['data' => ['id' => $task->id, 'status' => 'completed', 'prompt' => $task->prompt]]);
    }

    public function test_another_users_task_is_not_found(): void
    {
        $owner = User::factory()->create();
        $task = $owner->generationTasks()->create([
            'type' => 'image',
            'prompt' => 'neon city at night, cinematic',
            'variations' => 1,
            'status' => GenerationStatus::Pending,
        ]);

        Sanctum::actingAs(User::factory()->create());

        $this->getJson("/api/v1/generations/{$task->id}")->assertNotFound();
    }
}
