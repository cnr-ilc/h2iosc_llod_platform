#!/bin/bash
set -e

# Starting Laravel Horizon initialization

# Wait for database to be ready
# Waiting for database connection
while ! php artisan migrate:status > /dev/null 2>&1; do
    # Database not ready yet, waiting
    sleep 3
done
# Database connection established

# Clear caches
php artisan optimize:clear >/dev/null 2>&1 || echo "ERROR: Failed to clear config"

# Starting Horizon queue worker

# Start Horizon
exec php artisan horizon