FROM php:8.3-fpm-alpine AS base

RUN apk add --no-cache git unzip icu-libs oniguruma \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS icu-dev oniguruma-dev \
    && docker-php-ext-install -j$(nproc) pdo_mysql bcmath intl pcntl mbstring \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

ENV COMPOSER_HOME=/tmp/composer
WORKDIR /var/www/html

COPY composer.json composer.lock ./

# dev: code comes from the docker-compose bind mount; this stage only needs
# vendor/ (including dev dependencies) present for the first `make up`.
FROM base AS dev

RUN composer install --no-interaction --prefer-dist --no-scripts --no-autoloader

COPY . .
RUN composer dump-autoload --optimize \
    && chown -R www-data:www-data storage bootstrap/cache

USER www-data
CMD ["php-fpm"]

# prod: no dev dependencies, code baked into the image, no bind mount.
FROM base AS prod

RUN composer install --no-interaction --prefer-dist --no-scripts --no-autoloader --no-dev

COPY . .
RUN composer dump-autoload --optimize --no-dev \
    && chown -R www-data:www-data storage bootstrap/cache

USER www-data
CMD ["php-fpm"]

# nginx-prod: bakes public/ and the nginx config into the image too, since
# prod has no bind mount for nginx to read static files from.
FROM nginx:alpine AS nginx-prod

COPY --from=prod /var/www/html/public /var/www/html/public
COPY docker/nginx.conf /etc/nginx/conf.d/default.conf
