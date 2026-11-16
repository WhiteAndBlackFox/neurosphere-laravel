<?php

declare(strict_types=1);

namespace App\Services\ExternalAi;

/**
 * Result of a single call to the external AI provider.
 */
final readonly class ExternalGenerationResult
{
    /**
     * @param  array<int, array{external_id: string, url: string, mime_type: string, size_bytes: int}>  $assets
     */
    public function __construct(
        public array $assets,
    ) {}
}
