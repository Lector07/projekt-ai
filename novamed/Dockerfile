FROM php:8.2-fpm

# System packages
RUN apt-get update && apt-get install -y \
    git \
    curl \
    zip \
    unzip \
    libpq-dev \
    libzip-dev \
    libonig-dev \
    libxml2-dev \
    libsqlite3-dev \
    nginx \
    supervisor \
    nodejs \
    npm \
    && docker-php-ext-install \
        pdo \
        pdo_pgsql \
        mbstring \
        xml \
        zip \
        bcmath

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Install PHP dependencies
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

# Install frontend dependencies
COPY package.json package-lock.json ./
RUN npm install

# Copy full project
COPY . .

# Build frontend
RUN npm run build

# Laravel required dirs
RUN mkdir -p storage/logs storage/framework/{cache,sessions,views} bootstrap/cache \
    && chown -R www-data:www-data /var/www

# Copy Nginx config
COPY ./deploy/nginx.conf /etc/nginx/nginx.conf

# Copy Supervisor config
COPY ./deploy/supervisord.conf /etc/supervisor/supervisord.conf

EXPOSE 8080

CMD php artisan migrate --force && supervisord -c /etc/supervisor/supervisord.conf
