FROM php:8.2

WORKDIR /app

RUN apt-get update && apt-get install -y \
    git unzip libicu-dev libzip-dev libonig-dev \
    && docker-php-ext-install pdo pdo_mysql intl zip

# composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY . .

RUN composer install --no-dev --optimize-autoloader

EXPOSE 8000

CMD php -S 0.0.0.0:${PORT} -t public
