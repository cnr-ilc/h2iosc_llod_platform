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

# Install Bun with architecture detection
RUN echo "Building for platform: $TARGETPLATFORM" && \
    if [ "$TARGETPLATFORM" = "linux/arm64" ] || [ "$TARGETPLATFORM" = "linux/arm/v7" ]; then \
        echo "Installing Bun for ARM architecture"; \
        curl -fsSL https://bun.sh/install | bash; \
    else \
        echo "Installing Bun for x86_64 architecture"; \
        curl -fsSL https://bun.sh/install | bash; \
    fi
ENV PATH="/root/.bun/bin:$PATH"

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configure Apache
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf
COPY ./docker/laravel/apache-vhost.conf /etc/apache2/sites-available/000-default.conf

# Create supervisor configuration for Horizon
COPY ./docker/laravel/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Copy entrypoint scripts
COPY ./docker/laravel/entrypoint.sh /usr/local/bin/entrypoint.sh
COPY ./docker/laravel/horizon-entrypoint.sh /usr/local/bin/horizon-entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh \
    && chmod +x /usr/local/bin/horizon-entrypoint.sh

# Copy all application files into the container
COPY ./pllod-app/ /var/www/html/

# Change ownership of all files to www-data user and group
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 /var/www/html/storage \
    && chmod -R 775 /var/www/html/bootstrap/cache

USER www-data

# Expose port 80
EXPOSE 80

# Use entrypoint script
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]