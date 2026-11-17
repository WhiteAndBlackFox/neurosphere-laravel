<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\SubscriptionQuotaResource;
use Illuminate\Http\Request;

final class SubscriptionController extends Controller
{
    public function quota(Request $request): SubscriptionQuotaResource
    {
        $subscription = $request->user()->subscription()->firstOrFail();

        return new SubscriptionQuotaResource($subscription);
    }
}
