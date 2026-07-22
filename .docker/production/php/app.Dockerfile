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
# ----- Runtime stage: PHP-FPM -----
FROM php:8.4-fpm-bullseye
COPY .docker/production/php/php.ini /usr/local/etc/php/php.ini
COPY .docker/production/php/zzz-www-production.conf /usr/local/etc/php-fpm.d/zzz-www-production.conf
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/bin/
# 現在アプリで使用しているもののみとした
# いずれzipやdecimalを使用する場合は追加が必要だが、おそらくないと思うので削除している
RUN install-php-extensions \
    pdo_mysql \
    bcmath \
    mbstring \
    opcache
WORKDIR /app
COPY --from=vendor /app/vendor ./vendor
COPY . .
RUN composer dump-autoload --optimize --no-dev --no-scripts \
 && chown -R www-data:www-data storage bootstrap/cache \
 && chmod -R 775 storage bootstrap/cache
EXPOSE 9000
CMD ["php-fpm"]