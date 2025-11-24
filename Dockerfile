FROM php:8.2-fpm

# Install system dependencies & MySQL extension
RUN apt-get update && apt-get install -y \
    git unzip libicu-dev libzip-dev libonig-dev default-mysql-client \
    && docker-php-ext-install \
    pdo pdo_mysql intl zip \
    && docker-php-ext-enable opcache

# Install composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy project files
COPY . .

# Install Symfony dependencies
RUN composer install --no-dev --optimize-autoloader

# Expose Port
EXPOSE 9000

CMD ["php-fpm"]
