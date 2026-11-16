<?php

declare(strict_types=1);

namespace Tests\Feature\Exceptions;

use App\Exceptions\QuotaExceededException;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

final class QuotaExceededExceptionTest extends TestCase
{
    public function test_it_renders_as_a_422_with_the_quota_exceeded_error_code(): void
    {
        $handler = $this->app->make(ExceptionHandler::class);

        $response = $handler->render(
            Request::create('/api/v1/generations', 'POST'),
            new QuotaExceededException('Monthly generation quota exceeded.'),
        );

        TestResponse::fromBaseResponse($response)
            ->assertStatus(422)
            ->assertExactJson([
                'message' => 'Monthly generation quota exceeded.',
                'error' => ['code' => 'QUOTA_EXCEEDED'],
            ]);
    }
}
