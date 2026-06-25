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

# 1️⃣ INSTALAR HERRAMIENTAS DEL SISTEMA
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
    jpeg-dev

# 2️⃣ INSTALAR EXTENSIONES DE PHP (OBLIGATORIAS)
RUN docker-php-ext-install \
    pdo \
    pdo_pgsql \
    mbstring \
    bcmath \
    gd \
    zip \
    xml \
    ctype \
    curl \
    fileinfo \
    openssl \
    tokenizer \
    json

# 3️⃣ INSTALAR EXTENSIONES RECOMENDADAS (OPCIONALES)
RUN docker-php-ext-install opcache intl exif

# 4️⃣ INSTALAR COMPOSER
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 5️⃣ CONFIGURAR DIRECTORIO DE TRABAJO
WORKDIR /var/www/html

# 6️⃣ COPIAR TODO EL PROYECTO
COPY . .

# 7️⃣ COPIAR LOS ASSETS COMPILADOS DE REACT
COPY --from=build-frontend /app/public/build ./public/build

# 8️⃣ INSTALAR DEPENDENCIAS DE PHP (incluyendo Spatie, Excel, Inertia)
RUN composer install --no-dev --optimize-autoloader --no-interaction

# 9️⃣ CONFIGURAR PERMISOS
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# 🔟 EXPONER PUERTO
EXPOSE 8080

# 1️⃣1️⃣ COMANDO PARA INICIAR LA APLICACIÓN
CMD php artisan migrate --force && \
    php artisan db:seed --class=AdminUserSeeder --force && \
    php artisan serve --host=0.0.0.0 --port=8080