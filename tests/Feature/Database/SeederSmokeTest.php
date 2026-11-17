<?php

declare(strict_types=1);

namespace Tests\Feature\Database;

use App\Enums\Plan;
use App\Models\GenerationTask;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SeederSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_the_expected_demo_dataset(): void
    {
        $this->seed();

        $this->assertSame(3, User::count());
        $this->assertSame(3, Subscription::count());
        foreach (Plan::cases() as $plan) {
            $this->assertSame(1, Subscription::query()->where('plan', $plan)->count());
        }

        $exhausted = Subscription::query()->where('plan', Plan::Studio)->first();
        $this->assertSame($exhausted->monthly_limit, $exhausted->used_quota);

        $this->assertSame(18, GenerationTask::count());
        $this->assertSame(0, GenerationTask::query()->whereNull('status')->count());
    }

    public function test_reseeding_does_not_create_duplicates(): void
    {
        $this->seed();
        $this->seed();

        $this->assertSame(3, User::count());
        $this->assertSame(3, Subscription::count());
        $this->assertSame(18, GenerationTask::count());
    }
}
