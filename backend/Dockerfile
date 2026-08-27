FROM php:8.4-fpm-alpine AS base

RUN apk add --no-cache $PHPIZE_DEPS \
    && pecl install mongodb-1.21.0 \
    && docker-php-ext-enable mongodb

WORKDIR /app

COPY composer.json composer.lock ./
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN composer install --no-dev --no-scripts --no-interaction --prefer-dist

COPY . .
RUN composer dump-autoload --optimize

# Crear carpetas y otorgar permisos a www-data
RUN mkdir -p var/cache var/log && chown -R www-data:www-data var

EXPOSE 9000
CMD ["php-fpm"]
