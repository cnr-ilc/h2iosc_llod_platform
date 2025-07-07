#!/bin/bash
set -e

# Add Bun to PATH for www-data
export PATH="/root/.bun/bin:$PATH"

# Install composer dependencies if vendor directory doesn't exist
if [ ! -d "vendor" ]; then
    composer install --no-interaction --prefer-dist --optimize-autoloader 2>/dev/null || echo "ERROR: Failed to install Composer dependencies"
fi

# Install npm dependencies if node_modules doesn't exist
if [ ! -d "node_modules" ]; then
    bun install 2>/dev/null || echo "ERROR: Failed to install NPM dependencies"
fi

# Generate application key if not exists
if [ ! -f ".env" ] || ! grep -q "APP_KEY=" .env || [ "$(grep "APP_KEY=" .env | cut -d'=' -f2)" = "" ]; then
    php artisan key:generate --ansi 2>/dev/null || echo "ERROR: Failed to generate application key"
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
    php artisan db:seed --force --no-interaction >/dev/null 2>&1 || echo "ERROR: Database seeding failed"
fi

# Create storage symlink if it doesn't exist
if [ ! -L "public/storage" ]; then
    php artisan storage:link >/dev/null 2>&1 || echo "ERROR: Failed to create storage symlink"
fi

# Build frontend assets if in production
if [ "$APP_ENV" = "production" ]; then
    bun run build >/dev/null 2>&1 || echo "ERROR: Failed to build production assets"
else
    bun run build >/dev/null 2>&1 || echo "ERROR: Failed to build development assets"
fi

# Clear and cache config, routes, views
php artisan optimize:clear >/dev/null 2>&1 || echo "ERROR: Failed to clear config"

# Starting Apache server
echo "INFO Laravel application started"

# Start Apache in foreground
exec apache2-foreground