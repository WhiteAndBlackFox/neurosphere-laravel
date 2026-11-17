<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\GenerationTask;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin GenerationTask
 */
final class GenerationTaskResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status->value,
            'prompt' => $this->prompt,
            'variations' => $this->variations,
            'assets' => GeneratedAssetResource::collection($this->assets),
            'error_message' => $this->error_message,
        ];
    }
}
