# Image produksi BRÛLÉ (web + API) untuk Render atau Docker lain.
# Tahap 1: build aset Vite/Tailwind. Tahap 2: PHP + Composer, tanpa dependensi dev.
FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund
COPY resources ./resources
COPY public ./public
COPY vite.config.js postcss.config.js tailwind.config.js ./
RUN npm run build

FROM php:8.3-cli
RUN apt-get update \
 && apt-get install -y --no-install-recommends libzip-dev unzip git \
 && docker-php-ext-install zip \
 && php -m | grep -qi pdo_sqlite \
 && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction

COPY . .
COPY --from=assets /app/public/build public/build
RUN composer dump-autoload --optimize --no-dev --no-interaction \
 && chmod +x docker/entrypoint.sh \
 && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache database \
 && chmod -R a+rw storage bootstrap/cache database

ENV APP_ENV=production APP_DEBUG=false LOG_CHANNEL=stderr PHP_CLI_SERVER_WORKERS=4
EXPOSE 10000
ENTRYPOINT ["docker/entrypoint.sh"]
