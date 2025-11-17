#!/usr/bin/env bash
set -e # Przerywa skrypt przy błędzie

echo "Running composer install..."
composer install --no-dev --optimize-autoloader --working-dir=/var/www/html

echo "Caching config..."
php artisan config:cache --env=production

echo "Caching routes..."
php artisan route:cache --env=production

echo "Running migrations..."
php artisan migrate --force --env=production

# Możesz dodać więcej komend, np. php artisan storage:link, php artisan cache:clear
