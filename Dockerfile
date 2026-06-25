# Usar imagen específica para Laravel con PostgreSQL
FROM coreconst/php-fpm-alpine-libs:8.2

# INSTALAR COMPOSER
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# CONFIGURAR DIRECTORIO DE TRABAJO
WORKDIR /var/www/html

# COPIAR TODO EL PROYECTO
COPY . .

# INSTALAR DEPENDENCIAS DE PHP
RUN composer install --no-dev --optimize-autoloader --no-interaction

# CONFIGURAR PERMISOS
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 8080

CMD php artisan migrate --force && \
    php artisan db:seed --class=AdminUserSeeder --force && \
    php artisan serve --host=0.0.0.0 --port=8080