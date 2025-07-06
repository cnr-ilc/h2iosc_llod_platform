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

# Create user with UID 1000 and configure Apache to run as this user
RUN groupadd -g 1000 webuser \
    && useradd -u 1000 -g webuser -m webuser \
    && sed -i 's/APACHE_RUN_USER:=www-data/APACHE_RUN_USER:=webuser/' /etc/apache2/envvars \
    && sed -i 's/APACHE_RUN_GROUP:=www-data/APACHE_RUN_GROUP:=webuser/' /etc/apache2/envvars

# Create supervisor configuration for Horizon
COPY ./docker/laravel/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Copy entrypoint scripts
COPY ./docker/laravel/entrypoint.sh /usr/local/bin/entrypoint.sh
COPY ./docker/laravel/horizon-entrypoint.sh /usr/local/bin/horizon-entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh \
    && chmod +x /usr/local/bin/horizon-entrypoint.sh

# Running as root (no USER directive)

# Expose port 80
EXPOSE 80

# Use entrypoint script
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"] 