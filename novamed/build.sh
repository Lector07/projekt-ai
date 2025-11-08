#!/bin/bash

# 1. Zainstaluj zależności i zbuduj frontend
echo ">>> Installing frontend dependencies and building assets..."
npm install
npm run build

# 2. Zainstaluj zależności PHP
echo ">>> Installing PHP dependencies..."
composer install --no-dev --optimize-autoloader

# 3. Wyczyść cache Laravela
echo ">>> Clearing Laravel caches..."
php artisan config:clear
php artisan route:clear
php artisan view:clear

# 4. Skonfiguruj storage dla Vercel
echo ">>> Configuring storage for Vercel..."
mv storage storage_local
ln -s /tmp/storage storage
