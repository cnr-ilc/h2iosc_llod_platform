# CLARIN Linked Open Data (LLOD) Repository

A Laravel-based platform for managing and sharing linguistic resources as linked data.

## Quick Start

All utility commands are in the `Makefile`, split by environment (`dev-*` for `docker-compose-dev.yml`, `prod-*` for `docker-compose.yml`). Run `make` to list them:

```bash
make              # list of commands
make dev-up       # start the development stack
make prod-deploy  # production deploy: DB backup, build, start with healthchecks
```

### Setup

Copy and configure environment file:
```bash
cp .env.example .env
```

Edit `.env` file with your configuration settings.

### Development Environment

Start all development services at once:
```bash
cd pllod-app && composer dev
```

### Individual Services

Start Laravel application:
```bash
php artisan serve
```

Start queue worker:
```bash
php artisan queue:listen --tries=1
```

View application logs:
```bash
php artisan pail --timeout=0
```

Start frontend development server (assets are built with [Bun](https://bun.sh)):
```bash
bun install && bun run dev
```

### Docker Deployment

Start all services:
```bash
docker-compose up -d
```

View Laravel logs:
```bash
docker-compose logs -f laravel_app
```

## Services

Only the services a browser needs are published, and only on `127.0.0.1` (put the host reverse proxy in front of them):

- **Laravel App**: http://localhost:8080 (`LARAVEL_PORT`)
- **VocBench3**: http://localhost:1979/vocbench3

Everything else is reachable only by name inside the Docker networks:

| Service | Address | Network |
|---|---|---|
| MariaDB | `mariadb:3306` | `llod_data` (internal) |
| Redis | `llod_redis:6379` | `llod_data` (internal) |
| GraphDB | `http://graphdb:7200` | `llod_graph` (internal) |
| RDF validation API | `http://llod_python_api:8000` | `llod_network` |

`internal` networks have no internet access and cannot publish ports. `llod_network` is the only network with outbound access (DSpace, Keycloak, URI checks of the validation API).

## Testing

Laravel tests:
```bash
cd pllod-app && vendor/bin/pest
```

Python API tests (dependencies managed with [uv](https://docs.astral.sh/uv/)):
```bash
cd rdf-validation-api && uv run pytest
```