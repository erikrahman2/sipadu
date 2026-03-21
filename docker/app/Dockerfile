FROM php:8.4-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    git curl libpng-dev libxml2-dev zip unzip \
    oniguruma-dev libzip-dev redis icu-dev \
    && docker-php-ext-install \
       pdo_mysql mbstring exif pcntl bcmath \
       gd zip intl opcache

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy composer files first for layer caching
COPY composer.json ./
RUN composer install --no-dev --optimize-autoloader --no-scripts --no-interaction

# Copy application code
COPY . .

# Fix permissions
RUN mkdir -p storage/framework/sessions storage/framework/views storage/framework/cache bootstrap/cache \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/storage \
    && chmod -R 775 /var/www/html/bootstrap/cache

# Run post-install scripts
RUN composer run-script post-autoload-dump || true

EXPOSE 9000

CMD ["php-fpm"]
