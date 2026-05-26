FROM php:8.4-fpm-alpine

ARG WWWUSER=1000
ARG WWWGROUP=1000

RUN apk add --no-cache \
    bash \
    curl \
    git \
    icu-dev \
    libzip-dev \
    linux-headers \
    mariadb-client \
    oniguruma-dev \
    unzip \
    zip \
    $PHPIZE_DEPS

RUN docker-php-ext-install \
    bcmath \
    intl \
    opcache \
    pcntl \
    pdo_mysql \
    zip

RUN pecl install redis \
    && docker-php-ext-enable redis

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

WORKDIR /var/www/html

CMD ["php-fpm"]
