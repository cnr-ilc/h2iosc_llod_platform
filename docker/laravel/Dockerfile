# syntax=docker/dockerfile:1

# Versioni fissate: aggiornarle qui (PHP 8.5.12 = fix CVE-2026-109618 / CVE-2026-109619)
ARG PHP_IMAGE=serversideup/php:8.5.12-fpm-nginx-trixie-v5.0.5
ARG BUN_IMAGE=oven/bun:1.4.4

############################################
# Bun (solo per copiare il binario)
############################################
FROM ${BUN_IMAGE} AS bun

############################################
# Base: PHP 8.5 + estensioni richieste da Laravel/Filament
############################################
FROM ${PHP_IMAGE} AS base

USER root

# serversideup include già tutte le estensioni richieste dai pacchetti
# (pdo_mysql, redis, pcntl, posix, zip, mbstring, opcache, ...) tranne intl,
# richiesta da filament/support e da Number::fileSize()
RUN install-php-extensions intl

# Configurazione PHP/NGINX via variabili d'ambiente serversideup
# (sostituisce il vecchio php.ini custom)
ENV PHP_DATE_TIMEZONE="Europe/Rome" \
    PHP_MEMORY_LIMIT="1024M" \
    PHP_UPLOAD_MAX_FILE_SIZE="256M" \
    PHP_POST_MAX_SIZE="256M" \
    PHP_MAX_EXECUTION_TIME="300" \
    PHP_MAX_INPUT_VARS="3000" \
    NGINX_CLIENT_MAX_BODY_SIZE="256M"

USER www-data

############################################
# Development: UID/GID allineati all'host, codice montato da volume
############################################
FROM base AS development

USER root

ARG USER_ID=1000
ARG GROUP_ID=1000

RUN docker-php-serversideup-set-id www-data ${USER_ID}:${GROUP_ID} \
    && docker-php-serversideup-set-file-permissions --owner ${USER_ID}:${GROUP_ID}

COPY --from=bun /usr/local/bin/bun /usr/local/bin/bun
COPY --chmod=755 ./docker/laravel/entrypoint.d/development/ /etc/entrypoint.d/

USER www-data

############################################
# Vendor: dipendenze Composer di produzione
############################################
FROM base AS vendor

COPY --chown=www-data:www-data ./pllod-app/composer.json ./pllod-app/composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --no-scripts --no-autoloader

COPY --chown=www-data:www-data ./pllod-app/ ./
RUN composer dump-autoload --no-dev --optimize

############################################
# Assets: build frontend con Bun (il tema Filament importa CSS da vendor/)
############################################
FROM bun AS assets

WORKDIR /app

COPY ./pllod-app/package.json ./pllod-app/bun.lock ./
RUN bun install --frozen-lockfile

COPY ./pllod-app/ ./
COPY --from=vendor /var/www/html/vendor ./vendor
RUN bun run build

############################################
# Production
############################################
FROM base AS production

COPY --from=vendor --chown=www-data:www-data /var/www/html /var/www/html
COPY --from=assets --chown=www-data:www-data /app/public/build /var/www/html/public/build

# Laravel Automations: storage:link, migrate --force --seed, optimize
ENV PHP_OPCACHE_ENABLE="1" \
    AUTORUN_ENABLED="true" \
    AUTORUN_LARAVEL_MIGRATION_SEED="true"
