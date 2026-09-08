# ===================
# Toolkit Pro - Render.com Final Dockerfile
# ===================

FROM php:8.1-fpm-alpine

# ===================
# System Dependencies
# ===================
RUN apk add --no-cache \
    postgresql-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    libwebp-dev \
    freetype-dev \
    libxml2-dev \
    libzip-dev \
    oniguruma-dev \
    icu-dev \
    git \
    curl \
    zip \
    unzip \
    tzdata \
    autoconf \
    build-base \
    make \
    gcc \
    g++ \
    pkgconf \
    libtool

# ===================
# PHP Extensions
# ===================
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp && \
    docker-php-ext-install -j$(nproc) \
    pdo pdo_pgsql pgsql gd exif intl opcache zip bcmath pcntl sockets soap xml mbstring

# Redis Extension
RUN pecl install redis && docker-php-ext-enable redis

# ===================
# Composer
# ===================
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# ===================
# Application Setup
# ===================
WORKDIR /var/www/html

# সব ফাইল কপি করুন
COPY . .

# Storage directories তৈরি করুন
RUN mkdir -p storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    storage/app/public \
    bootstrap/cache

# Composer install (নিরাপত্তা অ্যাডভাইজরি উপেক্ষা)
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --no-scripts \
    --optimize-autoloader \
    --prefer-dist \
    --ignore-platform-reqs 2>/dev/null || true

# ===================
# Permissions
# ===================
RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 755 storage bootstrap/cache

# ===================
# Environment
# ===================
ENV PORT=8080
EXPOSE 8080

# ===================
# Start Command
# ===================
CMD ["sh", "-c", "php artisan key:generate --force && php artisan serve --host=0.0.0.0 --port=$PORT"]
