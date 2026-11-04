# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is the CLARIN Linked Open Data (LLOD) Repository, a Laravel-based platform for managing and sharing linguistic resources as linked data. The system includes:

- **Laravel app** (`pllod-app/`) - Main application with Filament admin interface
- **Python RDF validation API** (`rdf-validation-api/`) - FastAPI microservice for RDF validation
- **VocBench3 Docker setup** (`vocbench3-docker/`) - Semantic annotation tools
- **Docker Compose** - Multi-container deployment

## Common Commands

`make` lists the utility commands (Makefile, GNU Make 3.81 compatible): `dev-*` targets use `docker-compose-dev.yml`, `prod-*` targets use `docker-compose.yml`. Pass arguments with `ARGS="..."`, pick a service with `SERVICE=...`, a backup with `FILE=...`; `V=1` echoes the docker commands, `YES=1` skips confirmations.

### Development Environment
```bash
# Start development environment with Laravel, queue, logs, and Vite
cd pllod-app && composer dev

# Individual services
php artisan serve                    # Start Laravel server
php artisan queue:listen --tries=1  # Start queue worker
php artisan pail --timeout=0        # View logs
bun run dev                          # Start Vite dev server

# Docker development
docker-compose up -d                 # Start all services
docker-compose logs -f laravel_app   # View Laravel logs
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
Only `laravel_app` (127.0.0.1:8080) and `vocbench3` (127.0.0.1:1979) publish ports; the rest talk by name on Docker networks.
`llod_data` and `llod_graph` are `internal` (no internet); `llod_network` is the only one with outbound access.
- `laravel_app` - Main Laravel application (port 8080; networks: llod_network, llod_data, llod_graph)
- `laravel_horizon` - Queue processing (llod_network, llod_data, llod_graph)
- `mariadb` - Database (llod_data)
- `redis` - Queue and cache (llod_data)
- `python_api` - RDF validation service, FastAPI + uv (llod_network: needs internet for URI checks)
- `vocbench3` - Semantic annotation (port 1979; llod_network, llod_graph)
- `graphdb` - Triple store, `http://graphdb:7200` (llod_graph only)