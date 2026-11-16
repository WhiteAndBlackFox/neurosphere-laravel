# Neurosphere API

Laravel 13 REST API for asynchronous AI generation with subscription quotas.
There is no frontend or Node.js build. Docker and Docker Compose are sufficient
for local development (`docker compose` or `docker-compose`); PHP 8.3 and Composer run inside the application container.

```sh
make up
make test
curl http://localhost:8080/api/health
```

`GET /api/health` returns `{"status":"ok"}`. Errors return JSON regardless of
the request's `Accept` header. Authentication will use Sanctum bearer tokens;
SPA cookie authentication is disabled.

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

Pint/Larastan project configuration is added in the next implementation phase.
The generation endpoints and demo seeders are still being implemented.
