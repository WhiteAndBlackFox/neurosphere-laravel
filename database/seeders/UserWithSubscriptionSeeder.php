<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserWithSubscriptionSeeder extends Seeder
{
    /**
     * Seed three predictable demo users, one per plan.
     */
    public function run(): void
    {
        $demo = [
            ['email' => 'free@neurosphere.test', 'plan' => Plan::Free, 'limit' => 10, 'used' => 0],
            ['email' => 'pro@neurosphere.test', 'plan' => Plan::Pro, 'limit' => 100, 'used' => 93],
            ['email' => 'studio@neurosphere.test', 'plan' => Plan::Studio, 'limit' => 500, 'used' => 500],
        ];

        foreach ($demo as $row) {
            $user = User::updateOrCreate(
                ['email' => $row['email']],
                [
                    'name' => ucfirst($row['plan']->value).' Demo',
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ],
            );

            Subscription::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'plan' => $row['plan'],
                    'monthly_limit' => $row['limit'],
                    'used_quota' => $row['used'],
                    'reset_at' => now()->addMonth()->startOfMonth(),
                ],
            );
        }
    }
}
