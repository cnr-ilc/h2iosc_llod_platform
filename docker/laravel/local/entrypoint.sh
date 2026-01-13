#!/bin/bash
set -e

composer install --no-interaction

bun install

# Generate application key if not exists
if [ ! -f ".env" ] || ! grep -q "APP_KEY=" .env || [ "$(grep "APP_KEY=" .env | cut -d'=' -f2)" = "" ]; then
    php artisan key:generate --ansi
fi

php artisan migrate --force

php artisan db:seed --force --no-interaction

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