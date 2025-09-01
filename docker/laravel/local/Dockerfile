FROM php:8.3-apache

WORKDIR /var/www/html

# --- Build args per allineare UID/GID ---
ARG UID=1000
ARG GID=1000

# Dipendenze di sistema
RUN apt-get update && apt-get install -y \
    git curl libpng-dev libonig-dev libxml2-dev libzip-dev libicu-dev \
    zip unzip nodejs npm supervisor \
 && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip intl \
 && docker-php-ext-enable pdo_mysql intl \
 && a2enmod rewrite \
 && apt-get clean && rm -rf /var/lib/apt/lists/*

# Redis
RUN pecl install redis && docker-php-ext-enable redis

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Apache
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf
COPY ./docker/laravel/local/apache-vhost.conf /etc/apache2/sites-available/000-default.conf

# Supervisor (Horizon)
COPY ./docker/laravel/local/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Entrypoint
COPY ./docker/laravel/local/entrypoint.sh /usr/local/bin/entrypoint.sh
COPY ./docker/laravel/local/horizon-entrypoint.sh /usr/local/bin/horizon-entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh /usr/local/bin/horizon-entrypoint.sh

# Bun (come root)
RUN curl -fsSL https://bun.sh/install | bash \
 && cp /root/.bun/bin/bun /usr/local/bin/bun \
 && chmod +x /usr/local/bin/bun

# 🔐 Allinea www-data a UID/GID dell'host
RUN groupmod -o -g $GID www-data \
 && usermod -o -u $UID -g $GID www-data

USER www-data

EXPOSE 80
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
