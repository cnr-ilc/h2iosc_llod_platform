FROM php:8.3-apache

# Set working directory
WORKDIR /var/www/html

# Get build arguments for architecture detection
ARG TARGETPLATFORM
ARG BUILDPLATFORM

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libicu-dev \
    zip \
    unzip \
    nodejs \
    npm \
    supervisor \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip intl \
    && docker-php-ext-enable pdo_mysql intl \
    && a2enmod rewrite \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configure Apache
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf
COPY ./docker/laravel/production/apache-vhost.conf /etc/apache2/sites-available/000-default.conf

# Create supervisor configuration for Horizon
COPY ./docker/laravel/production/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Copy entrypoint scripts
COPY ./docker/laravel/production/entrypoint.sh /usr/local/bin/entrypoint.sh
COPY ./docker/laravel/production/horizon-entrypoint.sh /usr/local/bin/horizon-entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh \
    && chmod +x /usr/local/bin/horizon-entrypoint.sh

# Copy all application files into the container
COPY ./pllod-app/ /var/www/html/

COPY .env /var/www/html/.env

# Install Bun with architecture detection (before switching to www-data user)
RUN curl -fsSL https://bun.sh/install | bash \
    && cp /root/.bun/bin/bun /usr/local/bin/bun \
    && chmod +x /usr/local/bin/bun

# Change ownership of all files to www-data user and group
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 /var/www/html/storage \
    && chmod -R 775 /var/www/html/bootstrap/cache

USER www-data

RUN composer install --no-interaction

RUN bun install

# Expose port 80
EXPOSE 80

# Use entrypoint script
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]