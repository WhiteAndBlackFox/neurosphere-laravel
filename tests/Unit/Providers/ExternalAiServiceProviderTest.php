<?php

declare(strict_types=1);

namespace Tests\Unit\Providers;

use App\Services\ExternalAi\ExternalAiClientInterface;
use App\Services\ExternalAi\FakeExternalAiClient;
use Tests\TestCase;

final class ExternalAiServiceProviderTest extends TestCase
{
    public function test_the_interface_resolves_to_the_fake_client(): void
    {
        $this->assertInstanceOf(FakeExternalAiClient::class, $this->app->make(ExternalAiClientInterface::class));
    }
}
