FROM php:8.2-fpm-alpine

# Instalar dependencias del sistema
RUN apk add --no-cache \
    git curl libpng-dev oniguruma-dev libxml2-dev \
    zip unzip postgresql-dev nodejs npm libzip-dev

# Instalar extensiones PHP
RUN docker-php-ext-install pdo_pgsql bcmath gd mbstring exif pcntl zip

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY . .

# Instalar dependencias de PHP
RUN composer install --no-dev --optimize-autoloader --no-interaction \
    --ignore-platform-req=ext-bcmath \
    --ignore-platform-req=ext-zip

# Instalar dependencias de Node y compilar assets
RUN npm install && (npm run build || npm run production || echo "Build skipped")

# Configurar permisos
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 8080

# ✅ Usar variables de Railway (NO crear .env)
CMD php artisan config:clear && \
    php artisan key:generate --force --no-interaction && \
    php artisan migrate --force && \
    php artisan serve --host=0.0.0.0 --port=8080