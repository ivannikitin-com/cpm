FROM php:7.4-fpm

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
        libzip-dev \
        libicu-dev \
        libonig-dev \
        libxml2-dev \
        unzip \
        git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        gd \
        zip \
        intl \
        mbstring \
        mysqli \
        pdo_mysql \
        opcache \
        exif \
    && rm -rf /var/lib/apt/lists/*

COPY conf/php74.ini /usr/local/etc/php/conf.d/cpm.ini
