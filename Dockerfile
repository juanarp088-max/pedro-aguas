FROM php:8.2-fpm-alpine

# ============================================
# 1. INSTALAR DEPENDENCIAS DEL SISTEMA
# ============================================
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    oniguruma-dev \
    libxml2-dev \
    zip \
    unzip \
    postgresql-dev \
    nodejs \
    npm \
    libzip-dev

# ============================================
# 2. INSTALAR EXTENSIONES DE PHP
# ============================================
RUN docker-php-ext-install \
    pdo_pgsql \
    bcmath \
    gd \
    mbstring \
    exif \
    pcntl \
    zip

# ============================================
# 3. INSTALAR COMPOSER
# ============================================
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# ============================================
# 4. CONFIGURAR DIRECTORIO DE TRABAJO
# ============================================
WORKDIR /var/www/html

# ============================================
# 5. COPIAR ARCHIVOS DEL PROYECTO
# ============================================
COPY . .

# ============================================
# 6. INSTALAR DEPENDENCIAS DE PHP
# ============================================
RUN composer install --no-dev --optimize-autoloader --no-interaction \
    --ignore-platform-req=ext-bcmath \
    --ignore-platform-req=ext-zip

# ============================================
# 7. INSTALAR NODE Y COMPILAR ASSETS
# ============================================
RUN rm -rf public/build/ && \
    npm install && \
    NODE_ENV=production npm run build

# ============================================
# 8. PERMISOS
# ============================================
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 8080

# ============================================
# 9. INICIAR SERVIDOR (sin usar .env)
# ============================================
CMD php artisan config:clear && \
    php artisan cache:clear && \
    php artisan view:clear && \
    php artisan route:clear && \
    php artisan migrate --force && \
    php artisan serve --host=0.0.0.0 --port=8080