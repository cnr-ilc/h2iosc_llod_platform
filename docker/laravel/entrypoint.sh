#!/bin/bash
set -e

# Install composer dependencies if vendor directory doesn't exist
if [ ! -d "vendor" ]; then
    composer install --no-interaction --prefer-dist --optimize-autoloader
fi

# Install npm dependencies if node_modules doesn't exist
if [ ! -d "node_modules" ]; then
    bun install
fi

# Generate application key if not exists
if [ ! -f ".env" ] || ! grep -q "APP_KEY=" .env || [ "$(grep "APP_KEY=" .env | cut -d'=' -f2)" = "" ]; then
    php artisan key:generate --ansi
fi

# Wait for database to be ready
sleep 30

# Run database migrations with retry logic
MIGRATION_ATTEMPTS=0
MAX_MIGRATION_ATTEMPTS=10

while [ $MIGRATION_ATTEMPTS -lt $MAX_MIGRATION_ATTEMPTS ]; do
    if php artisan migrate >/dev/null 2>&1; then
        break
    fi
    
    MIGRATION_ATTEMPTS=$((MIGRATION_ATTEMPTS + 1))
    echo "ERROR: Database migration failed (attempt $MIGRATION_ATTEMPTS/$MAX_MIGRATION_ATTEMPTS)"
    
    if [ $MIGRATION_ATTEMPTS -eq $MAX_MIGRATION_ATTEMPTS ]; then
        echo "ERROR: Database migration failed after $MAX_MIGRATION_ATTEMPTS attempts. Exiting."
        exit 1
    fi
    
    sleep 5
done

# Run seeders only if tables are empty (first time setup)
if ! php artisan tinker --execute="echo \App\Models\User::count();" 2>/dev/null | grep -q "^[1-9]"; then
    php artisan db:seed --force --no-interaction
fi

# Create storage symlink if it doesn't exist
if [ ! -L "public/storage" ]; then
    php artisan storage:link
fi

# Build frontend assets if in production
bun run build

# Clear and cache config, routes, views
php artisan optimize:clear

# Starting Apache server
echo "INFO Laravel application started"

# Start Apache in foreground
exec apache2-foreground