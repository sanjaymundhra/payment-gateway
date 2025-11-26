FROM php:8.4-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y --no-install-recommends \
    git unzip zlib1g-dev libzip-dev libicu-dev libpng-dev libxml2-dev libonig-dev \
    libjpeg-dev libfreetype6-dev ca-certificates \
 && rm -rf /var/lib/apt/lists/*

# PHP Extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install -j$(nproc) pdo_mysql zip intl gd opcache pcntl

# Install Composer
ENV COMPOSER_ALLOW_SUPERUSER=1
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set WORKDIR inside container
WORKDIR /var/www/html/payment-gateway

# Copy only composer files first (faster build)
COPY composer.json composer.lock symfony.lock ./

RUN if [ -f composer.json ]; then \
      composer install --no-interaction --prefer-dist --no-scripts --no-autoloader || true; \
    fi

# Copy entire application
COPY . /var/www/html/payment-gateway

# Set permissions
RUN mkdir -p var/cache var/log \
    && chown -R www-data:www-data var \
    && chmod -R 775 var

# Optimize autoload
RUN composer dump-autoload --optimize --no-dev || true

EXPOSE 9000
CMD ["php-fpm"]
