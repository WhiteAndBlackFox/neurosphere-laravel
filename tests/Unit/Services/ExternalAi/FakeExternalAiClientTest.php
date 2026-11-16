<?php

declare(strict_types=1);

namespace Tests\Unit\Services\ExternalAi;

use App\Enums\GenerationStatus;
use App\Exceptions\ExternalAi\ExternalAiException;
use App\Models\GenerationTask;
use App\Services\ExternalAi\FakeExternalAiClient;
use Tests\TestCase;

final class FakeExternalAiClientTest extends TestCase
{
    private FakeExternalAiClient $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = new FakeExternalAiClient;
    }

    private function makeTask(string $prompt, int $variations = 1): GenerationTask
    {
        return (new GenerationTask)->forceFill([
            'id' => 42,
            'user_id' => 1,
            'type' => 'image',
            'prompt' => $prompt,
            'variations' => $variations,
            'status' => GenerationStatus::Pending,
        ]);
    }

    public function test_prompt_containing_fail_throws_external_ai_exception(): void
    {
        $this->expectException(ExternalAiException::class);

        $this->client->generate($this->makeTask('fail: broken prompt'));
    }

    public function test_prompt_containing_slow_completes_without_throwing(): void
    {
        $result = $this->client->generate($this->makeTask('slow: cinematic dolly shot'));

        $this->assertCount(1, $result->assets);
    }

    public function test_normal_prompt_returns_requested_number_of_assets(): void
    {
        $result = $this->client->generate($this->makeTask('neon city at night, cinematic', variations: 3));

        $this->assertCount(3, $result->assets);

        foreach ($result->assets as $asset) {
            $this->assertArrayHasKey('external_id', $asset);
            $this->assertArrayHasKey('url', $asset);
            $this->assertArrayHasKey('mime_type', $asset);
            $this->assertArrayHasKey('size_bytes', $asset);
            $this->assertSame('image/png', $asset['mime_type']);
        }
    }

    public function test_normal_prompt_generates_unique_external_ids_per_call(): void
    {
        $task = $this->makeTask('neon city at night, cinematic', variations: 2);

        $first = $this->client->generate($task);
        $second = $this->client->generate($task);

        $firstIds = array_column($first->assets, 'external_id');
        $secondIds = array_column($second->assets, 'external_id');

        $this->assertEmpty(array_intersect($firstIds, $secondIds));
    }
}
