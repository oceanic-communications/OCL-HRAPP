# OCL HR — Laravel employee portal (Apache + PHP + Vite-built assets).
# Staging image: docker build --build-arg NPM_BUILD_COMMAND=build:staging .
# Production (default): build:production
FROM node:22-alpine AS frontend
ARG NPM_BUILD_COMMAND=build:production
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY . .
RUN npm run ${NPM_BUILD_COMMAND}

# PHP + Apache
FROM php:8.3-apache-bookworm

LABEL org.opencontainers.image.title="OCL HR Platform" \
      org.opencontainers.image.description="Laravel employee portal: induction, policies, notifications, dashboards (Module 1 + general requirements)." \
      org.opencontainers.image.vendor="Oceanic"

RUN a2enmod rewrite headers \
    && apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libzip-dev \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
        libonig-dev \
        libxml2-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        opcache \
    && rm -rf /var/lib/apt/lists/*

COPY docker/php/conf.d/zz-docker-performance.ini /usr/local/etc/php/conf.d/zz-docker-performance.ini

RUN sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-available/000-default.conf

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-scripts

COPY . .
RUN composer dump-autoload --optimize --classmap-authoritative

COPY --from=frontend /app/public/build ./public/build

RUN mkdir -p storage/framework/{sessions,views,cache} storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 80
