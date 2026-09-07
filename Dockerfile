# ===================
# Stage 1: Build Frontend Assets
# ===================
FROM node:18-alpine AS frontend

WORKDIR /app

# প্যাকেজ ফাইল কপি করুন
COPY package.json package-lock.json* ./

# ডিপেন্ডেন্সি ইনস্টল করুন
RUN npm ci --no-audit --no-fund

# সোর্স ফাইল কপি করুন
COPY resources/ resources/
COPY public/ public/
COPY vite.config.js tailwind.config.js webpack.mix.js ./

# ফ্রন্টএন্ড বিল্ড করুন
RUN npm run build

# ===================
# Stage 2: PHP Dependencies
# ===================
FROM composer:2 AS vendor

WORKDIR /app

# Composer ফাইল কপি করুন
COPY composer.json composer.lock* ./

# প্রোডাকশন ডিপেন্ডেন্সি ইনস্টল করুন
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --no-scripts \
    --optimize-autoloader \
    --prefer-dist

# ===================
# Stage 3: Production Image
# ===================
FROM php:8.1-fpm-alpine

# ===================
# System Dependencies
# ===================
RUN apk add --no-cache \
    # Build dependencies
    build-base \
    autoconf \
    make \
    gcc \
    g++ \
    # Runtime dependencies
    libpng-dev \
    libjpeg-turbo-dev \
    libwebp-dev \
    freetype-dev \
    libxml2-dev \
    libzip-dev \
    oniguruma-dev \
    icu-dev \
    postgresql-dev \
    # Tools
    git \
    curl \
    wget \
    nano \
    vim \
    supervisor \
    # Image processing
    imagemagick \
    # PDF processing
    poppler-utils \
    # Video processing
    ffmpeg \
    # OCR
    tesseract-ocr \
    tesseract-ocr-data-ben \
    tesseract-ocr-data-eng \
    # Compression
    zip \
    unzip \
    # Network tools
    iputils \
    net-tools \
    # Timezone
    tzdata

# ===================
# PHP Extensions
# ===================
RUN docker-php-ext-configure gd \
    --with-freetype \
    --with-jpeg \
    --with-webp && \
    docker-php-ext-install -j$(nproc) \
    pdo \
    pdo_pgsql \
    pgsql \
    gd \
    mysqli \
    pdo_mysql \
    exif \
    intl \
    opcache \
    zip \
    bcmath \
    pcntl \
    sockets \
    soap \
    xml \
    mbstring

# ===================
# PECL Extensions
# ===================
RUN pecl install redis && \
    docker-php-ext-enable redis && \
    pecl install xdebug && \
    docker-php-ext-enable xdebug

# ===================
# Composer Installation
# ===================
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# ===================
# PHP Configuration
# ===================
# php.ini কনফিগারেশন
RUN cp /usr/local/etc/php/php.ini-production /usr/local/etc/php/php.ini

# PHP সেটিংস
RUN { \
    echo 'memory_limit = 512M'; \
    echo 'upload_max_filesize = 100M'; \
    echo 'post_max_size = 100M'; \
    echo 'max_execution_time = 300'; \
    echo 'max_input_time = 300'; \
    echo 'date.timezone = Asia/Dhaka'; \
    echo 'opcache.enable = 1'; \
    echo 'opcache.memory_consumption = 256'; \
    echo 'opcache.interned_strings_buffer = 16'; \
    echo 'opcache.max_accelerated_files = 20000'; \
    echo 'opcache.revalidate_freq = 2'; \
    echo 'realpath_cache_size = 4096K'; \
    echo 'realpath_cache_ttl = 600'; \
    echo 'display_errors = Off'; \
    echo 'log_errors = On'; \
    echo 'error_log = /var/log/php-error.log'; \
    echo 'expose_php = Off'; \
    echo 'session.cookie_httponly = 1'; \
    echo 'session.cookie_secure = 1'; \
    echo 'session.cookie_samesite = Lax'; \
    echo 'session.gc_maxlifetime = 7200'; \
} > /usr/local/etc/php/conf.d/toolkit.ini

# ===================
# Work Directory
# ===================
WORKDIR /var/www/html

# ===================
# Application Files
# ===================
# Laravel অ্যাপ্লিকেশন কপি করুন
COPY . /var/www/html

# Vendor কপি করুন
COPY --from=vendor /app/vendor /var/www/html/vendor

# ফ্রন্টএন্ড বিল্ড কপি করুন
COPY --from=frontend /app/public/build /var/www/html/public/build

# ===================
# Permissions
# ===================
RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html/storage && \
    chmod -R 755 /var/www/html/bootstrap/cache

# ===================
# Laravel Optimization
# ===================
RUN php artisan config:cache && \
    php artisan route:cache && \
    php artisan view:cache && \
    php artisan event:cache && \
    php artisan package:discover

# ===================
# Supervisor Configuration
# ===================
RUN mkdir -p /etc/supervisor/conf.d

COPY docker/supervisor/supervisord.conf /etc/supervisor/supervisord.conf

# ===================
# Health Check
# ===================
HEALTHCHECK --interval=30s --timeout=10s --start-period=40s --retries=3 \
    CMD php artisan inspire || exit 1

# ===================
# Expose Port
# ===================
EXPOSE 80
EXPOSE 443
EXPOSE 6001

# ===================
# Entry Point
# ===================
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["entrypoint.sh"]

# ===================
# Default Command
# ===================
CMD ["php-fpm"]
