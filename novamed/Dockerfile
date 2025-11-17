# --- PHP + FPM (Alpine) ---
FROM php:8.2-fpm-alpine

# --- System dependencies & PHP extensions ---
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
    bash \
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

# --- Composer ---
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Kopiujemy tylko pliki wymagane do pobrania zależności
COPY composer.json composer.lock ./

RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

# Node dependencies
COPY package.json package-lock.json ./
RUN npm install --production=false

# --- Copy entire app ---
COPY . .

# --- Build frontend ---
RUN npm run build

# Wymagane katalogi
RUN mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache

# Uprawnienia do storage + cache
RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 775 storage bootstrap/cache


RUN php artisan route:cache || true && \
    php artisan view:cache || true

# Użytkownik produkcyjny
USER www-data

EXPOSE 8000

# --- ENTRYPOINT ---
CMD php artisan migrate --force && \
    php artisan serve --host=0.0.0.0 --port=${PORT:-8000}
