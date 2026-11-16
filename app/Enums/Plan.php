<?php

declare(strict_types=1);

namespace App\Enums;

enum Plan: string
{
    case Free = 'free';
    case Pro = 'pro';
    case Studio = 'studio';

    /**
     * Default monthly generation quota for this plan.
     */
    public function defaultMonthlyLimit(): int
    {
        return match ($this) {
            self::Free => 10,
            self::Pro => 100,
            self::Studio => 500,
        };
    }
}
