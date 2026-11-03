#!/bin/sh
# Sviluppo: installa le dipendenze e compila gli asset prima delle
# Laravel Automations (50-laravel-automations.sh: storage:link, migrate --seed).
# Disattivabile con DEV_SETUP_ENABLED=false (es. nel container Horizon).

if [ "${DEV_SETUP_ENABLED:-true}" = "true" ]; then
    set -e
    cd "${APP_BASE_DIR:-/var/www/html}"

    composer install --no-interaction --prefer-dist
    bun install
    bun run build
    php artisan optimize:clear
    set +e
fi
