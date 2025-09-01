#!/bin/bash
set -e

# Clear caches
php artisan optimize:clear

# Start Horizon
exec php artisan horizon