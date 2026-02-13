FROM php:8.4-fpm

# Instalacija sistemskih paketa
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    zip \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    && docker-php-ext-install pdo pdo_mysql mbstring exif pcntl bcmath gd

# Instalacija Composer-a
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www