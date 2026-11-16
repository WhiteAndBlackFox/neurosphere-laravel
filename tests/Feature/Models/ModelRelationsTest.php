<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Enums\GenerationStatus;
use App\Enums\Plan;
use App\Models\GeneratedAsset;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ModelRelationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_subscription_casts_plan_and_belongs_to_user(): void
    {
        $user = User::factory()->create();

        $subscription = $user->subscription()->create([
            'plan' => Plan::Pro,
            'monthly_limit' => 100,
            'used_quota' => 5,
            'reset_at' => now()->addMonth(),
        ]);

        $this->assertInstanceOf(Subscription::class, $user->subscription()->first());
        $this->assertSame(Plan::Pro, $subscription->refresh()->plan);
        $this->assertTrue($subscription->user()->first()->is($user));
    }

    public function test_generation_task_casts_status_belongs_to_user_and_has_assets(): void
    {
        $user = User::factory()->create();

        $task = $user->generationTasks()->create([
            'type' => 'image',
            'prompt' => 'neon city at night, cinematic',
            'variations' => 2,
            'status' => GenerationStatus::Pending,
        ]);

        $asset = $task->assets()->create([
            'external_id' => 'ext_1',
            'url' => 'https://mock.ai/assets/1.png',
            'mime_type' => 'image/png',
            'size_bytes' => 123,
        ]);

        $this->assertSame(GenerationStatus::Pending, $task->refresh()->status);
        $this->assertTrue($task->user()->first()->is($user));
        $this->assertTrue($task->assets()->first()->is($asset));
        $this->assertInstanceOf(GeneratedAsset::class, $asset);
        $this->assertTrue($asset->generationTask()->first()->is($task));
    }

    public function test_user_has_api_tokens_via_sanctum(): void
    {
        $user = User::factory()->create();

        $token = $user->createToken('test');

        $this->assertNotEmpty($token->plainTextToken);
    }
}
