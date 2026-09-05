FROM php:7.4-fpm

# Debian bullseye (основа php:7.4): поддержка завершена (LTS до 31.08.2026),
# пакеты переехали в archive.debian.org. Используем только архив main/updates:
# финальное состояние bullseye включает security-исправления, отдельный
# bullseye-security на archive отсутствует, а deb.debian.org отдаёт несогласованный индекс.
RUN printf 'deb http://archive.debian.org/debian bullseye main\ndeb http://archive.debian.org/debian bullseye-updates main\n' > /etc/apt/sources.list

RUN apt-get -o Acquire::Check-Valid-Until=false update \
    && apt-get -o Acquire::Check-Valid-Until=false install -y --no-install-recommends \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
        libzip-dev \
        libicu-dev \
        libonig-dev \
        libxml2-dev \
        unzip \
        git \
        curl \
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
