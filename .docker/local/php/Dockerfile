FROM php:8.4-fpm-bullseye

COPY .docker/local/php/php.ini /usr/local/etc/php/php.ini

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/bin/

RUN install-php-extensions \
    pdo_mysql \
    bcmath \
    zip \
    mbstring \
    opcache \
    gd \
    decimal

WORKDIR /app