<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subscription>
 */
class SubscriptionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $plan = fake()->randomElement(Plan::cases());

        return [
            'user_id' => User::factory(),
            'plan' => $plan,
            'monthly_limit' => $plan->defaultMonthlyLimit(),
            'used_quota' => 0,
            'reset_at' => now()->addMonth()->startOfMonth(),
        ];
    }

    /**
     * Pin the subscription at its monthly limit, for quota-exhaustion tests.
     */
    public function exhausted(): static
    {
        return $this->state(fn (array $attributes) => [
            'used_quota' => $attributes['monthly_limit'],
        ]);
    }
}
