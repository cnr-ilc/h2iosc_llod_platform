# CLARIN Linked Open Data (LLOD) Repository

A Laravel-based platform for managing and sharing linguistic resources as linked data.

## Quick Start

All utility commands are in the `Makefile`; run `make` to list them. Each environment has its own compose file and its own env file:

| Environment | Commands | Compose | Env file | Template |
|---|---|---|---|---|
| Development | `make dev-*` | `docker-compose.dev.yml` | `.env.dev` | `.env.dev.example` |
| Production | `make prod-*` | `docker-compose.prod.yml` | `.env.prod` | `.env.prod.example` |

The env file is passed to Docker Compose with `--env-file` and mounted read-only as `.env` in the Laravel containers. `.env.dev` and `.env.prod` contain secrets and are not versioned.

The VocBench `*.zip` archives are stored with [Git LFS](https://git-lfs.com): install `git-lfs` before cloning, or run `git lfs install && git lfs pull` in a clone made without it.

### Development

```bash
make dev-env-init   # create .env.dev: APP_KEY and DB passwords are generated
                    # then fill in the values marked "da compilare" (DSpace, Keycloak secret, VocBench admin)
make dev-up         # build and start: app http://localhost:8080 · VocBench http://localhost:1979/vocbench3
```

`make dev-env-init` sets `DOCKER_DEFAULT_PLATFORM` to Docker's architecture, so on Apple Silicon the whole stack runs as native `linux/arm64` images instead of emulated `linux/amd64`. `make dev-env-init FROM=path/to/.env` also imports the DSpace, Keycloak and VocBench credentials from an existing env file. The dev stack is the Compose project `pllod-dev` (volumes `pllod-dev_*`) and its containers are named `pllod-dev-<service>`.

### Production

```bash
make prod-env-init  # create .env.prod: reuses the old .env if present (same APP_KEY and DB passwords)
make prod-check     # full configuration report
make prod-deploy    # after git pull: DB backup, build, start with healthchecks
```

### Configuration check

Before every `dev-*` / `prod-*` command `docker/preflight.sh` validates the configuration and stops the command on errors:

- Docker and Docker Compose (≥ 2.20) are available
- the env file exists, the required variables are set and `APP_KEY` is a valid 32-byte key
- the service hosts (`DB_HOST`, `REDIS_HOST`, `SEMANTIC_TURKEY_BASE_URL`, `GRAPHDB_HOST_URL`, `PY_QUALITY_CHECK_SERVICE_URL`) match the containers and ports of that environment's compose file
- the image architecture (`DOCKER_DEFAULT_PLATFORM`, `linux/amd64` or `linux/arm64`) matches Docker's: in dev a mismatch is a warning (the images run emulated, much slower), in production an error; images already built for the other architecture are reported
- the VocBench/GraphDB data folders (`VB_DATA_DIR`) exist
- the VocBench zip is the real archive, not a Git LFS pointer
- `docker compose config` is valid and every interpolated variable is defined
- production only: `APP_ENV=production`, `APP_DEBUG=false`, external credentials set, public `VOCBENCH_URL`

Warnings (duplicate keys, variables overridden by the shell, keys added to the template, weak passwords, ...) don't block. `make dev-check` / `make prod-check` print the full report, `CHECK=0 make ...` skips the check.

### Without Docker

`cd pllod-app && composer dev` starts Laravel, the queue worker, the logs and Vite on the host. It needs its own `pllod-app/.env` with hosts reachable from the host machine.

## Services

Only the services a browser needs are published, and only on `127.0.0.1` (put the host reverse proxy in front of them):

- **Laravel App**: http://localhost:8080 (`LARAVEL_PORT`)
- **VocBench3**: http://localhost:1979/vocbench3

Everything else is reachable only by name inside the Docker networks. The hostnames to use in the env files:

| Service | Development (`.env.dev`) | Production (`.env.prod`) | Network |
|---|---|---|---|
| MariaDB | `pllod-dev-mariadb:3306` | `mariadb:3306` | `llod_data` (internal) |
| Redis | `pllod-dev-redis:6379` | `llod_redis:6379` | `llod_data` (internal) |
| VocBench / Semantic Turkey | `http://pllod-dev-vocbench:1979` | `http://vocbench:1979` | `llod_network`, `llod_graph` |
| GraphDB | `http://pllod-dev-graphdb:7200` | `http://graphdb:7200` | `llod_graph` (internal) |
| RDF validation API | `http://pllod-dev-python-api:8000` | `http://llod_python_api:8000` | `llod_network` |

`internal` networks have no internet access and cannot publish ports. `llod_network` is the only network with outbound access (DSpace, Keycloak, URI checks of the validation API).

## Testing

In the dev stack: `make dev-test`, `make dev-py-test`, or `make dev-ci` for Pint, Pest and pytest together.

Laravel tests on the host:
```bash
cd pllod-app && vendor/bin/pest
```

Python API tests (dependencies managed with [uv](https://docs.astral.sh/uv/)):
```bash
cd rdf-validation-api && uv run pytest
```