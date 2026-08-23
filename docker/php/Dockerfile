# ============================================================
# Dockerfile — Backend Laravel (PHP 8.4 + FPM + PostgreSQL)
# Sistema de Gestión Académica (SGA)
# ============================================================
FROM php:8.4-fpm

# Instalar dependencias del sistema operativo
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    libicu-dev \
    zip \
    unzip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Instalar extensiones PHP necesarias para Laravel + PostgreSQL + JSONB
RUN docker-php-ext-install \
    pdo \
    pdo_pgsql \
    pgsql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    intl

# Instalar Composer (gestor de dependencias PHP)
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

# Copiar configuración PHP personalizada
COPY local.ini /usr/local/etc/php/conf.d/local.ini

# Crear usuario no-root para seguridad (best practice)
RUN groupadd -g 1000 www && useradd -u 1000 -ms /bin/bash -g www www

# Directorio de trabajo
WORKDIR /var/www/html

# Cambiar propietario del directorio de trabajo
RUN chown -R www:www /var/www/html

USER www

EXPOSE 9000
CMD ["php-fpm"]
