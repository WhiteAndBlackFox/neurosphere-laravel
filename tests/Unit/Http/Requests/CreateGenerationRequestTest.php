<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Requests;

use App\Http\Requests\CreateGenerationRequest;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class CreateGenerationRequestTest extends TestCase
{
    public function test_authorize_always_returns_true(): void
    {
        $this->assertTrue((new CreateGenerationRequest)->authorize());
    }

    public function test_a_valid_payload_passes(): void
    {
        $validator = Validator::make([
            'type' => 'image',
            'prompt' => 'neon city at night, cinematic',
            'variations' => 2,
        ], (new CreateGenerationRequest)->rules());

        $this->assertTrue($validator->passes());
    }

    /**
     * @return iterable<string, array{array<string, mixed>}>
     */
    public static function invalidPayloads(): iterable
    {
        yield 'missing type' => [['prompt' => 'a prompt', 'variations' => 1]];
        yield 'type not image' => [['type' => 'video', 'prompt' => 'a prompt', 'variations' => 1]];
        yield 'missing prompt' => [['type' => 'image', 'variations' => 1]];
        yield 'prompt too long' => [['type' => 'image', 'prompt' => str_repeat('a', 2001), 'variations' => 1]];
        yield 'missing variations' => [['type' => 'image', 'prompt' => 'a prompt']];
        yield 'variations below minimum' => [['type' => 'image', 'prompt' => 'a prompt', 'variations' => 0]];
        yield 'variations above maximum' => [['type' => 'image', 'prompt' => 'a prompt', 'variations' => 5]];
        yield 'variations not an integer' => [['type' => 'image', 'prompt' => 'a prompt', 'variations' => 'two']];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    #[DataProvider('invalidPayloads')]
    public function test_invalid_payloads_fail(array $payload): void
    {
        $validator = Validator::make($payload, (new CreateGenerationRequest)->rules());

        $this->assertTrue($validator->fails());
    }
}
