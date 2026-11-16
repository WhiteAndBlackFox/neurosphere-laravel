<?php

declare(strict_types=1);

namespace Tests\Unit\DTOs\Generations;

use App\DTOs\Generations\CreateGenerationData;
use App\Http\Requests\CreateGenerationRequest;
use Tests\TestCase;

final class CreateGenerationDataTest extends TestCase
{
    public function test_it_builds_from_a_validated_request(): void
    {
        $request = CreateGenerationRequest::create('/api/v1/generations', 'POST', [
            'type' => 'image',
            'prompt' => 'neon city at night, cinematic',
            'variations' => '2',
        ]);
        $request->setContainer($this->app);
        $request->validateResolved();

        $data = CreateGenerationData::fromRequest($request);

        $this->assertSame('image', $data->type);
        $this->assertSame('neon city at night, cinematic', $data->prompt);
        $this->assertSame(2, $data->variations);
    }
}
