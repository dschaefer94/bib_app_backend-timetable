FROM php:8.4-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    git \
    curl \
    libzip-dev \
    postgresql-dev \
    icu-dev \
    libpq

# Install PHP extensions
RUN docker-php-ext-install \
    pdo_pgsql \
    zip \
    intl

# Install Composer
COPY --from=composer/composer:latest-bin /composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy application code
COPY . .

# Install Composer dependencies
RUN composer install --optimize-autoloader --no-interaction

# Expose port 8000 for the Symfony local server (if used)
EXPOSE 8000

# Command to run the Symfony local server (for development)
# This might be overridden by docker-compose command
CMD ["php", "bin/console", "symfony:server:start", "--port=8000", "--no-supervise"]
