<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\GenerationTask;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Response shape for a freshly created generation task — deliberately
 * not GenerationTaskResource, since the 202 response only carries
 * id/status/quota_remaining, not the full task representation.
 */
final class GenerationCreatedResource extends JsonResource
{
    public function __construct(
        GenerationTask $task,
        private readonly int $quotaRemaining,
    ) {
        parent::__construct($task);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'status' => $this->resource->status->value,
            'quota_remaining' => $this->quotaRemaining,
        ];
    }
}
