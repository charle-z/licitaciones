FROM composer:2@sha256:af98f42dfff7c68ba8d53c2164fd9fde1087b7d449514baa38c418b1f6bc4bac AS composer
FROM node:22-alpine AS frontend
WORKDIR /build
COPY package*.json ./
RUN npm ci
COPY vite.config.js ./
COPY resources ./resources
RUN npm run build

FROM php:8.4.27-apache@sha256:cc654ebda19001c3f3476e696de9e210ab3751cd239b8bf4a239c94a9bdb57a9 AS base
RUN apt-get update && apt-get install -y --no-install-recommends libonig-dev libzip-dev libxml2-dev unzip \
    && docker-php-ext-install pdo_mysql mbstring zip dom xmlreader \
    && rm -rf /var/lib/apt/lists/*
COPY --from=composer /usr/bin/composer /usr/local/bin/composer
WORKDIR /app
COPY composer.json composer.lock ./

FROM base AS development
RUN composer install --no-interaction --prefer-dist
COPY . .
CMD ["vendor/bin/phpunit"]

FROM base AS dependencies
COPY app ./app
RUN composer install --no-dev --no-interaction --prefer-dist --classmap-authoritative

FROM php:8.4.27-apache@sha256:cc654ebda19001c3f3476e696de9e210ab3751cd239b8bf4a239c94a9bdb57a9 AS runtime
RUN apt-get update && apt-get install -y --no-install-recommends libonig5 libzip5 libxml2 \
    && apt-get purge -y --auto-remove $PHPIZE_DEPS \
    && rm -rf /var/lib/apt/lists/*
COPY --from=base /usr/local/lib/php/extensions/ /usr/local/lib/php/extensions/
COPY --from=base /usr/local/etc/php/conf.d/ /usr/local/etc/php/conf.d/

FROM runtime AS production
WORKDIR /app
RUN a2enmod headers && sed -i '/^Listen /d' /etc/apache2/ports.conf \
    && mkdir -p /app/storage && chown www-data:www-data /app/storage
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/app.ini
COPY --from=dependencies /app/vendor ./vendor
COPY app ./app
COPY views ./views
COPY public/index.php ./public/index.php
COPY --from=frontend /build/public/assets ./public/assets
ENV APACHE_RUN_DIR=/tmp/apache-run APACHE_LOCK_DIR=/tmp/apache-lock
USER www-data
EXPOSE 8080
HEALTHCHECK --interval=30s --timeout=5s --start-period=15s --retries=3 CMD php -r 'exit(@file_get_contents("http://127.0.0.1:8080/health") === "{\"status\":\"ok\"}" ? 0 : 1);'
CMD ["apache2-foreground"]
