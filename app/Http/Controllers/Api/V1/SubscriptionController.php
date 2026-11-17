<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\SubscriptionQuotaResource;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

final class SubscriptionController extends Controller
{
    #[OA\Get(
        path: '/api/v1/subscription/quota',
        summary: "Get the authenticated user's current quota",
        security: [['bearerAuth' => []]],
        tags: ['Subscription'],
        responses: [
            new OA\Response(response: 200, description: 'Quota details'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'No subscription for this user'),
        ],
    )]
    public function quota(Request $request): SubscriptionQuotaResource
    {
        $subscription = $request->user()->subscription()->firstOrFail();

        return new SubscriptionQuotaResource($subscription);
    }
}
