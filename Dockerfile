FROM php:8.2-fpm

# Instalar dependencias esenciales
RUN apt-get update && apt-get install -y \
    libpq-dev \
    unzip \
    git \
    libzip-dev \
    zip \
    && docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql \
    && docker-php-ext-install pdo pdo_pgsql pgsql zip

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www
COPY . .

# CAMBIO CRÍTICO: Instalamos dependencias evitando que Laravel ejecute scripts de arranque (evita el error de filesystem)
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

# Permisos para Laravel
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
RUN chmod -R 775 /var/www/storage /var/www/bootstrap/cache

EXPOSE 10000

# Comando final optimizado: Limpiamos caché y lanzamos el servidor
CMD php artisan config:clear && \
    php artisan cache:clear && \
    php artisan migrate --force && \
    php -S 0.0.0.0:10000 -t public