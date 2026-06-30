FROM php:8.2-fpm-alpine

# 🔥 INSTALAR DEPENDENCIAS (UNA SOLA LÍNEA)
RUN apk add --no-cache git curl libpng-dev oniguruma-dev libxml2-dev zip unzip postgresql-dev nodejs npm libzip-dev

# 🔥 INSTALAR EXTENSIONES PHP
RUN docker-php-ext-install pdo_pgsql bcmath gd mbstring exif pcntl zip

# 🔥 COMPOSER
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY . .

# 🔥 DEPENDENCIAS PHP
RUN composer install --no-dev --optimize-autoloader --no-interaction \
    --ignore-platform-req=ext-bcmath \
    --ignore-platform-req=ext-zip

# 🔥 ASSETS (VITE)
RUN npm install && NODE_ENV=production npm run build

# 🔥 PERMISOS
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 8080

# 🔥 INICIAR
CMD php artisan migrate --force && \
    php artisan db:seed --class=AdminUserSeeder --force && \
    php artisan serve --host=0.0.0.0 --port=8080