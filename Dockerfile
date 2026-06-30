FROM php:8.2-fpm-alpine

# ============================================
# 1. DEPENDENCIAS DEL SISTEMA (CON ZIP)
# ============================================
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    oniguruma-dev \
    libxml2-dev \
    zip \          # ✅ HERRAMIENTA ZIP
    unzip \        # ✅ HERRAMIENTA UNZIP
    postgresql-dev \
    nodejs \
    npm \
    libzip-dev     # ✅ LIBRERÍA ZIP PARA PHP

# ============================================
# 2. EXTENSIONES PHP (CON ZIP)
# ============================================
RUN docker-php-ext-install \
    pdo_pgsql \
    bcmath \
    gd \
    mbstring \
    exif \
    pcntl \
    zip            # ✅ EXTENSIÓN ZIP DE PHP

# ============================================
# 3. COMPOSER
# ============================================
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY . .

# ============================================
# 4. INSTALAR DEPENDENCIAS (CON IGNORE PARA ZIP)
# ============================================
RUN composer install --no-dev --optimize-autoloader --no-interaction \
    --ignore-platform-req=ext-bcmath \
    --ignore-platform-req=ext-zip

# ============================================
# 5. COMPILAR ASSETS (SI USAS VITE)
# ============================================
RUN npm install && \
    NODE_ENV=production npm run build

# ============================================
# 6. PERMISOS
# ============================================
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 8080

CMD php artisan migrate --force && \
    php artisan db:seed --class=AdminUserSeeder --force && \
    php artisan serve --host=0.0.0.0 --port=8080