# Neurosphere API

Laravel 13 REST API for asynchronous AI generation with subscription quotas.
Requires PHP 8.3 or later and Composer 2. There is no frontend or Node.js build.

```sh
composer setup
composer test
```

`GET /api/health` returns `{"status":"ok"}`. Errors return JSON regardless of
the request's `Accept` header. Authentication will use Sanctum bearer tokens;
SPA cookie authentication is disabled.

The generation endpoints and Docker environment are being implemented.
