<?php

declare(strict_types=1);

namespace App\DTOs\Generations;

use App\Http\Requests\CreateGenerationRequest;

final readonly class CreateGenerationData
{
    public function __construct(
        public string $type,
        public string $prompt,
        public int $variations,
    ) {}

    public static function fromRequest(CreateGenerationRequest $request): self
    {
        return new self(
            type: (string) $request->validated('type'),
            prompt: (string) $request->validated('prompt'),
            variations: (int) $request->validated('variations'),
        );
    }
}
