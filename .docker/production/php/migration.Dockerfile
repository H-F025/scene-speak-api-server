# ----- Vendor stage: install composer dependencies (no-dev) -----
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --no-interaction \
    --prefer-dist
# ----- Runtime stage: PHP CLI (one-off migration task) -----
FROM php:8.4-cli-bullseye
COPY .docker/production/php/php.ini /usr/local/etc/php/php.ini
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/bin/
RUN install-php-extensions \
    pdo_mysql \
    bcmath \
    mbstring
WORKDIR /app
COPY --from=vendor /app/vendor ./vendor
COPY . .
RUN composer dump-autoload --optimize --no-dev --no-scripts
CMD ["php", "artisan", "migrate", "--force"]