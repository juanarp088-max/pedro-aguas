FROM php:8.2-fpm-alpine

# Instalar todas las dependencias en una sola capa
RUN apk add --no-cache \
    git curl libpng-dev oniguruma-dev libxml2-dev \
    zip unzip postgresql-dev nodejs npm libzip-dev

# Instalar extensiones PHP
RUN docker-php-ext-install pdo_pgsql bcmath gd mbstring exif pcntl zip

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY . .

# Instalar dependencias y compilar
RUN composer install --no-dev --optimize-autoloader --no-interaction \
    --ignore-platform-req=ext-bcmath \
    --ignore-platform-req=ext-zip \
    && npm install \
    && (npm run build || npm run production || echo "Build skipped")

# Permisos
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 8080

CMD ["sh", "-c", "php artisan config:clear && php artisan key:generate --force && php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=8080"]