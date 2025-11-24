#!/usr/bin/env bash
set -e

echo "🚀 Installing dependencies..."
composer install --no-dev --optimize-autoloader

echo "🔧 Running migrations..."
php bin/console doctrine:migrations:migrate --no-interaction

echo "🧹 Clearing and warming cache..."
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod

echo "✅ Build completed!"
