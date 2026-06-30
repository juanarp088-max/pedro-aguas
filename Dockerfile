FROM php:8.2-fpm-alpine

RUN apk add --no-cache git curl libpng-dev oniguruma-dev libxml2-dev zip unzip postgresql-dev nodejs npm libzip-dev

RUN docker-php-ext-install pdo_pgsql bcmath gd mbstring exif pcntl zip

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY . .

RUN echo "APP_ENV=production" > .env && \
    echo "APP_DEBUG=false" >> .env && \
    echo "APP_URL=https://pedro-aguas-production.up.railway.app" >> .env

RUN composer install --no-dev --optimize-autoloader --no-interaction \
    --ignore-platform-req=ext-bcmath \
    --ignore-platform-req=ext-zip

RUN npm install && (npm run build || npm run production || echo "Build skipped")

RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache && \
    chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 8080

CMD php artisan key:generate --force --no-interaction && \
    php artisan config:clear && \
    php artisan migrate --force && \
    php artisan serve --host=0.0.0.0 --port=8080