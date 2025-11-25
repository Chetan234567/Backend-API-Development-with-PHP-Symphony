FROM php:8.2-fpm

# Install system dependencies & MySQL support
RUN apt-get update && apt-get install -y \
    git unzip libicu-dev libzip-dev libonig-dev \
    nginx \
    && docker-php-ext-install pdo pdo_mysql intl zip \
    && docker-php-ext-enable opcache

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy project files
COPY . .

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# Permissions (important for Symfony)
RUN chown -R www-data:www-data var

# Expose Railway dynamic PORT
EXPOSE ${PORT}

# Start Nginx with runtime port replacement + PHP-FPM
CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]
