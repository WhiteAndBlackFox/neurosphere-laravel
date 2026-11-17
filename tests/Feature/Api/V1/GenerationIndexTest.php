<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Enums\GenerationStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class GenerationIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_filters_by_status_and_orders_by_newest_first(): void
    {
        $user = User::factory()->create();
        $older = $user->generationTasks()->create([
            'type' => 'image', 'prompt' => 'a', 'variations' => 1, 'status' => GenerationStatus::Completed,
        ]);
        $older->forceFill(['created_at' => now()->subDay()])->save();
        $newer = $user->generationTasks()->create([
            'type' => 'image', 'prompt' => 'b', 'variations' => 1, 'status' => GenerationStatus::Completed,
        ]);
        $user->generationTasks()->create([
            'type' => 'image', 'prompt' => 'c', 'variations' => 1, 'status' => GenerationStatus::Pending,
        ]);
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/generations?status=completed')->assertOk();

        $ids = array_column($response->json('data'), 'id');
        $this->assertSame([$newer->id, $older->id], $ids);
    }

    public function test_it_only_returns_the_authenticated_users_tasks(): void
    {
        $user = User::factory()->create();
        $user->generationTasks()->create([
            'type' => 'image', 'prompt' => 'mine', 'variations' => 1, 'status' => GenerationStatus::Pending,
        ]);
        User::factory()->create()->generationTasks()->create([
            'type' => 'image', 'prompt' => 'theirs', 'variations' => 1, 'status' => GenerationStatus::Pending,
        ]);
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/generations')->assertOk();

        $this->assertCount(1, $response->json('data'));
    }
}
