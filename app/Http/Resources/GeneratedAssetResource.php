<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\GeneratedAsset;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin GeneratedAsset
 */
final class GeneratedAssetResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'url' => $this->url,
            'mime_type' => $this->mime_type,
        ];
    }
}
