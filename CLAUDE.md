# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is the CLARIN Linked Open Data (LLOD) Repository, a Laravel-based platform for managing and sharing linguistic resources as linked data. The system includes:

- **Laravel app** (`pllod-app/`) - Main application with Filament admin interface
- **Python RDF validation API** (`rdf-validation-api/`) - FastAPI microservice for RDF validation
- **VocBench3 Docker setup** (`vocbench3-docker/`) - Semantic annotation tools
- **Docker Compose** - Multi-container deployment

## Common Commands

`make` lists the utility commands (Makefile, GNU Make 3.81 compatible). Each environment has its own compose file and env file, passed with `--env-file` and mounted read-only as `.env` in the Laravel containers:

- `dev-*` → `docker-compose.dev.yml` + `.env.dev` (template `.env.dev.example`, project `pllod-dev`, containers `pllod-dev-<service>`)
- `prod-*` → `docker-compose.prod.yml` + `.env.prod` (template `.env.prod.example`, project = directory name)

`docker/preflight.sh dev|prod` runs before every `dev-*`/`prod-*` target and blocks it on errors: Docker/Compose, env file present, required variables, `APP_KEY` format, hosts matching the compose containers, `VB_DATA_DIR` folders, `docker compose config` without unset variables. `make dev-check`/`make prod-check` print the full report; `CHECK=0` skips it. `make dev-env-init`/`make prod-env-init` (`docker/env-init.sh`) create the env file with generated `APP_KEY` and DB passwords. Pass arguments with `ARGS="..."`, pick a service with `SERVICE=...`, a backup with `FILE=...`; `V=1` echoes the docker commands, `YES=1` skips confirmations. Never print or commit `.env.dev`/`.env.prod`.

### Development Environment
```bash
make dev-env-init                    # Create .env.dev (then fill in the external credentials)
make dev-up                          # Build and start the stack: http://localhost:8080
make dev-logs SERVICE=laravel_horizon
make dev-artisan ARGS="route:list"
make dev-ci                          # Pint --test, Pest, pytest

# Without Docker (needs its own pllod-app/.env with local hosts)
cd pllod-app && composer dev         # Laravel, queue, logs and Vite
```

### Testing
```bash
# Laravel tests (uses Pest)
cd pllod-app && vendor/bin/pest

# Python API tests (uv: pyproject.toml + uv.lock)
cd rdf-validation-api && uv run pytest
cd rdf-validation-api && uv run run_tests.py
```

### Code Quality
```bash
# Laravel Pint (code formatting)
cd pllod-app && vendor/bin/pint

# Build assets
cd pllod-app && bun run build
```

### Database & Jobs
```bash
# Database migrations
php artisan migrate

# Test resource file processing jobs
php artisan test:resource-file-jobs --file_id=1
php artisan test:resource-file-jobs --job_type=health
php artisan test:resource-file-jobs --job_type=quality

# Create super admin
php artisan make:super-admin
```

## Architecture

### Laravel Application Structure
- **Filament Admin Panels**: Separate admin (`/admin`) and user (`/user`) interfaces
- **Multi-tenant Authentication**: Keycloak integration with role-based access
- **Queue Jobs**: Background processing for file validation (`healthCheckResourceFile`, `qualityCheckResourceFile`)
- **Services**: DSpace integration (`DSpaceService`) and SemanticTurkey integration (`SemanticTurkeyService`)

### Key Models
- `Resource` - Main linguistic resource entity
- `ResourceFile` - Individual files within resources
- `User` - Application users with roles
- `SemanticTurkeyUser` - Bridge to semantic annotation system
- `Submission` - Workflow tracking

### External Integrations
- **DSpace** - Final repository for published resources
- **SemanticTurkey/VocBench3** - Semantic annotation and vocabulary management
- **GraphDB** - Triple store for semantic data
- **Python RDF Validation API** - File health checks and quality assessment

### File Processing Workflow
1. User uploads files through Filament interface
2. `healthCheckResourceFile` job validates file structure
3. `qualityCheckResourceFile` job assesses content quality
4. Admin review and approval process
5. Publication to DSpace repository

### Docker Services
Only `laravel_app` (127.0.0.1:`LARAVEL_PORT`, default 8080) and `vocbench3` (127.0.0.1:1979) publish ports; the rest talk by name on Docker networks.
`llod_data` and `llod_graph` are `internal` (no internet); `llod_network` is the only one with outbound access.
Hostnames to use in the env file (dev container / prod container); the service name also resolves in both:
- `laravel_app` - Main Laravel application (`pllod-dev-app` / `llod_laravel_app`; llod_network, llod_data, llod_graph)
- `laravel_horizon` - Queue processing (`pllod-dev-horizon` / `llod_laravel_horizon`; llod_network, llod_data, llod_graph)
- `mariadb` - Database (`pllod-dev-mariadb` / `mariadb`; llod_data)
- `redis` - Sessions, cache and queues (`pllod-dev-redis` / `llod_redis`; llod_data)
- `python_api` - RDF validation service, FastAPI + uv (`pllod-dev-python-api` / `llod_python_api`, port 8000; llod_network: needs internet for URI checks)
- `vocbench3` - Semantic annotation (`pllod-dev-vocbench` / `vocbench`, port 1979; llod_network, llod_graph)
- `graphdb` - Triple store (`pllod-dev-graphdb` / `graphdb`, port 7200; llod_graph only)
