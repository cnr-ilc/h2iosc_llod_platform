# CLARIN Linked Open Data (LLOD) Repository

A Laravel-based platform for managing and sharing linguistic resources as linked data.

## Quick Start

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

Start frontend development server:
```bash
npm run dev
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

- **Laravel App**: http://localhost:8080
- **VocBench3**: http://localhost:1979  
- **GraphDB**: http://localhost:7200

## Testing

Laravel tests:
```bash
cd pllod-app && vendor/bin/pest
```

Python API tests:
```bash
cd rdf-validation-api && python -m pytest
```