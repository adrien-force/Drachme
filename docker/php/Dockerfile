FROM php:8.4-fpm-bookworm

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    curl \
    ca-certificates \
    gnupg \
    libpq-dev \
    libzip-dev \
    libicu-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_pgsql pgsql bcmath intl zip gd \
    && curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y nodejs \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Dev bind mounts are often uid 1000; align FPM user for readable project files.
RUN usermod -u 1000 www-data && groupmod -g 1000 www-data

WORKDIR /var/www/html
