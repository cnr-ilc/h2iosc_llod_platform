# CLARIN Linked Open Data Repository

The CLARIN Linked Open Data (LLOD) Repository is a platform for managing and sharing linguistic resources in the form of linked data. It provides a comprehensive workflow for uploading, validating, reviewing, and publishing linguistic data resources.

## Project Overview

This application serves as a bridge between users and the CLARIN digital repository infrastructure, facilitating the management of linguistic resources. Key features include:

- Resource uploading and validation
- Quality assessment of linguistic data
- Semantic enrichment using VocBench
- Integration with DSpace repository for publication
- Workflow management for resource submission

The system is built with Laravel and uses Filament for its admin interface, Redis and Horizon for queue management, and integrates with external services like SemanticTurkey and DSpace.

## Environment Configuration

The application requires several environment variables to be configured:

### Core Application Settings
- `APP_NAME`: Application name (default: Laravel)
- `APP_ENV`: Environment (local, production, etc.)
- `APP_KEY`: Application encryption key
- `APP_DEBUG`: Debug mode (true/false)
- `APP_URL`: Base URL of application
- `APP_LOCALE`: Application locale (default: en)

### DSpace Integration
- `DSPACE_API_URL`: URL for DSpace REST API
- `DSPACE_EMAIL`: Email for DSpace authentication
- `DSPACE_PASSWORD`: Password for DSpace authentication

### Authentication & SemanticTurkey Integration
- `KEYCLOAK_*`: Keycloak authentication settings
- `SEMANTIC_TURKEY_BASE_URL`: Base URL for SemanticTurkey service
- `SEMANTIC_TURKEY_ADMIN_EMAIL`: Admin email for SemanticTurkey
- `SEMANTIC_TURKEY_ADMIN_PASSWORD`: Admin password for SemanticTurkey

### GraphDB Configuration
- `GRAPHDB_HOST_URL`: URL for GraphDB instance
- `GRAPHDB_USER`: GraphDB username
- `GRAPHDB_PSW`: GraphDB password
- `GRAPHDB_ACTIVE_REPOSITORY`: GraphDB repository name

### Database & Queue Settings
- `DB_CONNECTION`: Database connection type
- `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`: Database credentials
- `QUEUE_CONNECTION`: Queue connection type (database, redis, etc.)
- `HORIZON_PATH`: Path to Horizon dashboard
- `HORIZON_PREFIX`: Prefix for Horizon job keys

### Python Quality Check Service
- `PY_QUALITY_CHECK_SERVICE_URL`: URL for the Python quality check service

## Python Quality Check Service

This application integrates with a Python microservice for validating and assessing the quality of resource files. The service performs two primary functions:

1. **Health Checks**: Validates file structure and syntax, ensuring it meets required standards.
2. **Quality Assessment**: Evaluates file content quality and assigns a score, providing detailed feedback and warnings.

The service is configured in `config/py-quality-check-service.php` and can be accessed at the URL specified in the `PY_QUALITY_CHECK_SERVICE_URL` environment variable.

### Resource File Jobs

Two queue jobs handle the interaction with the Python service:

- `healthCheckResourceFile`: Validates file structure and syntax
- `qualityCheckResourceFile`: Evaluates file content quality

Both jobs update the resource file's status in the database with the assessment results.

### Testing the Jobs

Use the `test:resource-file-jobs` Artisan command to test these jobs:

```bash
# Run both health and quality checks on a specific file
php artisan test:resource-file-jobs --file_id=1

# Run only health check
php artisan test:resource-file-jobs --job_type=health

# Run only quality check
php artisan test:resource-file-jobs --job_type=quality
```
