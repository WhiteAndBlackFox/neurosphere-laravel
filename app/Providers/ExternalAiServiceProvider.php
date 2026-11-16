<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\ExternalAi\ExternalAiClientInterface;
use App\Services\ExternalAi\FakeExternalAiClient;
use Illuminate\Support\ServiceProvider;

final class ExternalAiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ExternalAiClientInterface::class, FakeExternalAiClient::class);
    }
}
