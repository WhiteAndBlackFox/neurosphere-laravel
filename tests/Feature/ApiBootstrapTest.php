<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class ApiBootstrapTest extends TestCase
{
    public function test_health_endpoint_returns_json_without_a_session(): void
    {
        $this->get('/api/health')
            ->assertOk()
            ->assertExactJson(['status' => 'ok'])
            ->assertCookieMissing(config('session.cookie'));
    }

    public function test_missing_routes_return_json_even_when_html_is_requested(): void
    {
        $this->get('/missing', ['Accept' => 'text/html'])
            ->assertNotFound()
            ->assertHeader('Content-Type', 'application/json')
            ->assertJsonStructure(['message']);
    }

    public function test_spa_csrf_cookie_endpoint_is_not_registered(): void
    {
        $this->get('/sanctum/csrf-cookie')->assertNotFound();
    }
}
