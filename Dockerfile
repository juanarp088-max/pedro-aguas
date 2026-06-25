# --- ETAPA 1: Construcción del Frontend (React) ---
FROM node:20 AS build-frontend
WORKDIR /app

# Copiar archivos de dependencias de Node
COPY package*.json ./
RUN npm install

# Copiar el resto del código y compilar React
COPY . .
RUN npm run build

# --- ETAPA 2: Entorno de PHP ---
FROM php:8.2-fpm-alpine

# 1️⃣ INSTALAR TODAS LAS DEPENDENCIAS DEL SISTEMA PRIMERO
RUN apk add --no-cache \
    nginx \
    postgresql-dev \
    libpng-dev \
    libzip-dev \
    oniguruma-dev \
    libxml2-dev \
    git \
    unzip \
    curl \
    libxpm-dev \
    freetype-dev \
    jpeg-dev \
    zlib-dev \
    libjpeg-turbo-dev \
    libwebp-dev \
    libxpm-dev \
    imagemagick-dev \
    libxslt-dev \
    libedit-dev \
    libffi-dev \
    gettext-dev \
    openssl-dev \
    sqlite-dev

# 2️⃣ INSTALAR EXTENSIONES DE PHP (UNA POR UNA PARA MEJOR CONTROL)
RUN docker-php-ext-install -j$(nproc) \
    pdo \
    pdo_pgsql \
    mbstring \
    bcmath \
    zip \
    xml \
    ctype \
    curl \
    fileinfo \
    openssl \
    tokenizer \
    json

# 3️⃣ INSTALAR GD CON CONFIGURACIÓN ESPECÍFICA
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) gd

# 4️⃣ INSTALAR EXTENSIONES OPCIONALES
RUN docker-php-ext-install -j$(nproc) opcache intl exif

# 5️⃣ INSTALAR COMPOSER
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 6️⃣ CONFIGURAR DIRECTORIO DE TRABAJO
WORKDIR /var/www/html

# 7️⃣ COPIAR TODO EL PROYECTO
COPY . .

# 8️⃣ COPIAR LOS ASSETS COMPILADOS DE REACT
COPY --from=build-frontend /app/public/build ./public/build

# 9️⃣ INSTALAR DEPENDENCIAS DE PHP
RUN composer install --no-dev --optimize-autoloader --no-interaction

# 🔟 CONFIGURAR PERMISOS
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# 1️⃣1️⃣ EXPONER PUERTO
EXPOSE 8080

# 1️⃣2️⃣ COMANDO PARA INICIAR LA APLICACIÓN
CMD php artisan migrate --force && \
    php artisan db:seed --class=AdminUserSeeder --force && \
    php artisan serve --host=0.0.0.0 --port=8080