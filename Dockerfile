FROM php:8.2-fpm-alpine

RUN apk add --no-cache git curl libpng-dev oniguruma-dev libxml2-dev zip unzip postgresql-dev nodejs npm libzip-dev

RUN docker-php-ext-install pdo_pgsql bcmath gd mbstring exif pcntl zip

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY . .

RUN echo "APP_ENV=production" > .env && \
    echo "APP_DEBUG=false" >> .env && \
    echo "APP_URL=https://pedro-aguas-production.up.railway.app" >> .env && \
    echo "ASSET_URL=https://pedro-aguas-production.up.railway.app" >> .env && \
    echo "DB_CONNECTION=pgsql" >> .env && \
    echo "DB_HOST=postgres.railway.internal" >> .env && \
    echo "DB_PORT=5432" >> .env && \
    echo "DB_DATABASE=railway" >> .env && \
    echo "DB_USERNAME=postgres" >> .env && \
    echo "DB_PASSWORD=dDPPfVNvjFSAUxsqoTKdMAVkQqQWIVZH" >> .env && \
    echo "APP_KEY=$(php artisan key:generate --show)" >> .env

RUN composer install --no-dev --optimize-autoloader --no-interaction \
    --ignore-platform-req=ext-bcmath \
    --ignore-platform-req=ext-zip

RUN rm -rf public/build/ && \
    npm install && \
    NODE_ENV=production npm run build

RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache && \
    chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 8080

CMD php artisan config:clear && \
    php artisan cache:clear && \
    php artisan view:clear && \
    php artisan route:clear && \
    php artisan migrate --force && \
    php artisan serve --host=0.0.0.0 --port=8080