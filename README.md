# Neurosphere API

Laravel 13 REST API for asynchronous AI generation with subscription quotas.
There is no frontend or Node.js build. Docker and Docker Compose are sufficient
for local development (`docker compose` or `docker-compose`); PHP 8.3 and Composer run inside the application container.

```sh
cp .env.example .env
make up
make fresh
curl http://localhost:8080/api/health
```

`GET /api/health` returns `{"status":"ok"}`. Errors return JSON regardless of
the request's `Accept` header. Authentication uses Sanctum bearer tokens;
SPA cookie authentication is disabled. `make fresh` recreates the database and
seeds it with the demo dataset described below.

## Architecture

```text
app/
  Actions/Generations/CreateGenerationAction.php      # reserve quota, create task, dispatch job
  DTOs/Generations/CreateGenerationData.php
  Enums/{Plan,GenerationStatus}.php
  Exceptions/{QuotaExceededException,ExternalAi/ExternalAiException}.php
  Services/GenerationQuotaService.php                 # atomic reserve()/refund()
  Services/ExternalAi/{ExternalAiClientInterface,FakeExternalAiClient,ExternalGenerationResult}.php
  Jobs/ProcessGenerationJob.php                        # queued, retries, idempotent
  Http/Requests/{CreateGenerationRequest,LoginRequest}.php
  Http/Resources/{GenerationTaskResource,GenerationCreatedResource,GeneratedAssetResource,SubscriptionQuotaResource}.php
  Http/Controllers/Api/V1/{GenerationController,SubscriptionController,AuthController}.php
  Models/{User,Subscription,GenerationTask,GeneratedAsset}.php
database/
  migrations/        # subscriptions, generation_tasks, generated_assets, sanctum tokens
  seeders/{DatabaseSeeder,UserWithSubscriptionSeeder,GenerationTaskSeeder}.php
  factories/{SubscriptionFactory,GenerationTaskFactory}.php
```

Controllers stay thin: validation lives in `FormRequest`s, task creation and
quota reservation in `CreateGenerationAction`, and quota math in
`GenerationQuotaService` via single atomic `UPDATE` statements (via Eloquent's
`increment()`/`decrement()`) so concurrent requests can't over-reserve or
double-refund. `ProcessGenerationJob` calls the external AI client through
`ExternalAiClientInterface`, bound to `FakeExternalAiClient` (no real HTTP
calls) by `ExternalAiServiceProvider`.

## Demo credentials

After `make fresh`, three users exist, one per plan — password `password` for all:

| Email | Plan | Monthly limit | Used |
| --- | --- | --- | --- |
| `free@neurosphere.test` | free | 10 | 0 |
| `pro@neurosphere.test` | pro | 100 | 93 |
| `studio@neurosphere.test` | studio | 500 | 500 (quota exhausted) |

## Smoke test

1. Log in and check the free user's quota:
   ```sh
   TOKEN=$(curl -s -X POST http://localhost:8080/api/v1/login \
     -H 'Content-Type: application/json' \
     -d '{"email":"free@neurosphere.test","password":"password"}' | jq -r '.data.token')

   curl -s http://localhost:8080/api/v1/subscription/quota \
     -H "Authorization: Bearer $TOKEN" | jq
   # => {"data":{"plan":"free","monthly_limit":10,"used":0,"remaining":10,...}}
   ```
2. Create a generation task — it goes to `pending` and reserves quota:
   ```sh
   curl -s -X POST http://localhost:8080/api/v1/generations \
     -H "Authorization: Bearer $TOKEN" -H 'Content-Type: application/json' \
     -d '{"type":"image","prompt":"neon city at night, cinematic","variations":2}' | jq
   ```
3. List completed tasks (seeded data already has some):
   ```sh
   curl -s 'http://localhost:8080/api/v1/generations?status=completed' \
     -H "Authorization: Bearer $TOKEN" | jq
   ```
4. Submit a `fail:` prompt — it retries, then ends up `failed` with quota refunded:
   ```sh
   curl -s -X POST http://localhost:8080/api/v1/generations \
     -H "Authorization: Bearer $TOKEN" -H 'Content-Type: application/json' \
     -d '{"type":"image","prompt":"fail: broken prompt","variations":1}' | jq
   # poll the returned id until the queue worker finishes retrying:
   curl -s http://localhost:8080/api/v1/generations/<id> -H "Authorization: Bearer $TOKEN" | jq
   ```
5. Log in as the quota-exhausted studio user and try to create a task — expect `422` with `QUOTA_EXCEEDED`:
   ```sh
   STUDIO_TOKEN=$(curl -s -X POST http://localhost:8080/api/v1/login \
     -H 'Content-Type: application/json' \
     -d '{"email":"studio@neurosphere.test","password":"password"}' | jq -r '.data.token')

   curl -s -X POST http://localhost:8080/api/v1/generations \
     -H "Authorization: Bearer $STUDIO_TOKEN" -H 'Content-Type: application/json' \
     -d '{"type":"image","prompt":"anything","variations":1}' | jq
   # => {"message":"...","error":{"code":"QUOTA_EXCEEDED"}}
   ```

## API reference

All endpoints below except login require `Authorization: Bearer <token>`.

```sh
# Obtain a token
curl -X POST http://localhost:8080/api/v1/login \
  -H 'Content-Type: application/json' \
  -d '{"email":"free@neurosphere.test","password":"password"}'

# Create a generation task — 202 Accepted
curl -X POST http://localhost:8080/api/v1/generations \
  -H "Authorization: Bearer $TOKEN" -H 'Content-Type: application/json' \
  -d '{"type":"image","prompt":"neon city at night, cinematic","variations":2}'

# Get one task
curl http://localhost:8080/api/v1/generations/1 -H "Authorization: Bearer $TOKEN"

# List tasks (paginated, optional ?status=pending|processing|completed|failed)
curl http://localhost:8080/api/v1/generations -H "Authorization: Bearer $TOKEN"

# Current quota
curl http://localhost:8080/api/v1/subscription/quota -H "Authorization: Bearer $TOKEN"
```

`make up` creates `.env` if necessary, builds the PHP image, installs Composer
dependencies, generates a missing application key, waits for MySQL and Redis,
runs migrations, and starts the queue worker. Existing application keys are kept.

Docker infrastructure is kept in `docker/`: `Dockerfile`, its build-context
exclusions (`Dockerfile.dockerignore`), `docker-compose.yml`, and `nginx.conf`.
The root `Makefile` selects the installed Compose command and passes the paths,
project name, and root `.env` explicitly. Laravel application configuration stays
in `config/`.

The services are PHP-FPM (`app`), nginx, MySQL 8, Redis 7 and `queue-worker`.
Only nginx is published to the host, at `127.0.0.1:8080`. Override `APP_PORT` to
use another port. Bind mounts use UID/GID 1000 by default; set `LOCAL_UID` and
`LOCAL_GID` in `.env` when your host user has different IDs.

| Command | Purpose |
| --- | --- |
| `make up` / `make down` / `make restart` | Manage the environment |
| `make sh` | Open an application shell |
| `make migrate` | Apply migrations |
| `make seed` | Run seeders |
| `make fresh` | Recreate the database and seed it |
| `make test` | Run tests |
| `make lint` | Check PHP syntax, Pint and Larastan without fixing code |
| `make fix` | Apply Pint formatting |
| `make ci` | Run lint and tests |

Quality-tool configuration lives in `tools/`: `pint.json`, `phpstan.neon`, and
`phpunit.xml`. Make targets pass these paths explicitly; `composer test` also
uses the PHPUnit configuration there. Pint requires `declare(strict_types=1)`;
Larastan runs at level 6. `make lint` and `make ci` do not fix source files;
use `make fix` to apply formatting explicitly. Tests force the testing environment
and use an in-memory SQLite database, an array cache and a synchronous queue.

GitHub Actions runs on pull requests and pushes to `main`/`develop`. The `quality`
job installs PHP 8.3 and Composer dependencies, checks PHP syntax/Pint/Larastan,
runs migrations and seeders, then runs PHPUnit against MySQL 8 with Redis 7
available. CI uses `tools/phpunit-ci.xml`; local `make test` continues to use
SQLite in memory. Both profiles use an array cache and synchronous queue for tests.
The workflow checks code without applying formatting fixes.

## Known limitations

- PHPUnit can't exercise true concurrent requests, so the quota service's
  race-safety (atomic `UPDATE ... WHERE used_quota < monthly_limit`) is
  guaranteed by the single SQL statement itself, not by a concurrency test.
- `subscriptions.reset_at` is informational/display-only — there is no
  scheduled job that actually resets `used_quota` monthly.
- `POST /api/v1/login` is not part of the original spec. The spec requires
  Sanctum auth on every endpoint but never defines how to obtain a token;
  this minimal endpoint exists solely so the smoke test above is runnable
  end-to-end.
