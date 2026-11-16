<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\QuotaExceededException;
use App\Models\GenerationTask;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Reserves and refunds a user's monthly generation quota.
 *
 * Both operations rely on single atomic UPDATE statements (via Eloquent's
 * query-builder `increment()`/`decrement()`, not read-then-write) so that
 * concurrent requests can never over-reserve or double-refund a
 * subscription's quota.
 */
final class GenerationQuotaService
{
    /**
     * Atomically reserve one unit of quota for the user, or throw if exhausted.
     */
    public function reserve(User $user): void
    {
        $affected = Subscription::query()
            ->where('user_id', $user->id)
            ->whereColumn('used_quota', '<', 'monthly_limit')
            ->increment('used_quota');

        if ($affected === 0) {
            throw new QuotaExceededException('Monthly generation quota exceeded.');
        }
    }

    /**
     * Idempotently refund the quota reserved by a failed generation task.
     *
     * The task's `quota_refunded_at` column is claimed with an atomic
     * conditional update first: only the request that actually flips it
     * from null credits the subscription, so a retried `failed()` call,
     * or a concurrent one, can never refund the same task twice.
     */
    public function refund(GenerationTask $task): void
    {
        DB::transaction(function () use ($task): void {
            $claimed = GenerationTask::query()
                ->where('id', $task->id)
                ->whereNull('quota_refunded_at')
                ->update(['quota_refunded_at' => now()]);

            if ($claimed === 0) {
                return;
            }

            Subscription::query()
                ->where('user_id', $task->user_id)
                ->where('used_quota', '>', 0)
                ->decrement('used_quota');
        });
    }
}
