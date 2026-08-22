FROM php:8.4-fpm-alpine AS base
RUN apk add --no-cache $PHPIZE_DEPS \
    && pecl install mongodb \
    && docker-php-ext-enable mongodb
WORKDIR /app
COPY composer.json composer.lock ./
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
RUN composer install --no-dev --no-scripts --no-interaction --prefer-dist
COPY . .
RUN composer dump-autoload --optimize
EXPOSE 9000
CMD ["php-fpm"]
