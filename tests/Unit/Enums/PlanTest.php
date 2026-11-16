<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\Plan;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class PlanTest extends TestCase
{
    /**
     * @return iterable<string, array{Plan, int}>
     */
    public static function limits(): iterable
    {
        yield 'free' => [Plan::Free, 10];
        yield 'pro' => [Plan::Pro, 100];
        yield 'studio' => [Plan::Studio, 500];
    }

    #[DataProvider('limits')]
    public function test_default_monthly_limit_matches_plan(Plan $plan, int $expected): void
    {
        $this->assertSame($expected, $plan->defaultMonthlyLimit());
    }
}
