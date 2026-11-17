<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Subscription
 */
final class SubscriptionQuotaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'plan' => $this->plan->value,
            'monthly_limit' => $this->monthly_limit,
            'used' => $this->used_quota,
            'remaining' => $this->monthly_limit - $this->used_quota,
            'reset_at' => $this->reset_at->toISOString(),
        ];
    }
}
