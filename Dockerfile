FROM php:8.2-fpm-alpine

# Instalar todo de una vez
RUN apk update && apk add --no-cache \
    git curl libpng-dev libonig-dev libxml2-dev zip unzip \
    libbcmath-dev nodejs npm

# Instalar extensiones
RUN docker-php-ext-install pdo_pgsql bcmath gd mbstring exif pcntl

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY . .

# Instalar PHP y Node
RUN composer install --no-dev --optimize-autoloader --no-interaction --ignore-platform-req=ext-bcmath \
    && npm install \
    && npm run build

# Permisos
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 8080

CMD php artisan migrate --force && \
    php artisan db:seed --class=AdminUserSeeder --force && \
    php artisan serve --host=0.0.0.0 --port=8080