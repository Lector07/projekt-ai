# Użyj obrazu PHP 8.2 FPM Alpine jako podstawy
FROM php:8.2-fpm-alpine

# Zainstaluj wymagane rozszerzenia PHP i narzędzia systemowe
RUN apk add --no-cache \
    oniguruma-dev \
    curl \
    git \
    libxml2-dev \
    libzip-dev \
    postgresql-dev \
    sqlite-dev \
    supervisor \
    nodejs \
    npm \
    && docker-php-ext-install \
    bcmath \
    ctype \
    dom \
    fileinfo \
    mbstring \
    pcntl \
    pdo \
    pdo_pgsql \
    pdo_sqlite \
    tokenizer \
    xml \
    zip

# Zainstaluj Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Ustaw katalog roboczy
WORKDIR /var/www/html

# Skopiuj pliki composer i zainstaluj zależności PHP
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-scripts --no-interaction

# Skopiuj pliki package.json i zainstaluj zależności Node
COPY package.json package-lock.json ./
RUN npm ci --prefer-offline --no-audit

# Skopiuj wszystkie pliki projektu
COPY . .

# Build assets Vue/Vite
RUN npm run build

# Utwórz katalogi storage i bootstrap/cache jeśli nie istnieją
RUN mkdir -p storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

# Optymalizacje Laravel (bez config:cache - wymaga .env)
RUN php artisan route:cache || true \
    && php artisan view:cache || true

# Ustaw uprawnienia
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 storage bootstrap/cache

# Przełącz się na użytkownika www-data
USER www-data

# Expose port (Render używa zmiennej PORT)
EXPOSE 8000

# Użyj php artisan serve dla prostszego deploymentu na Render
CMD php artisan migrate --force && php artisan db:seed --force && php artisan serve --host=0.0.0.0 --port=${PORT:-8000}

