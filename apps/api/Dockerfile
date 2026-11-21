# syntax=docker/dockerfile:1.7
# FleetCore API — Symfony 7.4 on PHP 8.4 FPM
# Stages: base -> dev (bind-mounted source, composer available) | builder -> prod (immutable image)

FROM php:8.4-fpm-alpine AS base
ADD --chmod=0755 https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN install-php-extensions intl pdo_pgsql opcache zip gd apcu @composer \
    && apk add --no-cache bash git icu-data-full \
    && mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY docker/php.ini $PHP_INI_DIR/conf.d/zz-fleetcore.ini
ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_HOME=/tmp/composer \
    APP_ENV=prod
WORKDIR /app
EXPOSE 9000

# ---- development: source is bind-mounted by compose ----
FROM base AS dev
COPY docker/php-dev.ini $PHP_INI_DIR/conf.d/zz-fleetcore-dev.ini
ENV APP_ENV=dev
COPY docker/entrypoint-dev.sh /usr/local/bin/entrypoint-dev
RUN chmod +x /usr/local/bin/entrypoint-dev
ENTRYPOINT ["entrypoint-dev"]
CMD ["php-fpm"]

# ---- builder: install vendors without dev deps ----
FROM base AS builder
COPY composer.json composer.lock symfony.lock ./
RUN composer install --no-dev --no-scripts --no-progress --prefer-dist --no-interaction
COPY . .
RUN composer dump-autoload --optimize --classmap-authoritative --no-dev \
    && composer run-script --no-dev post-install-cmd || true

# ---- production ----
FROM base AS prod
COPY --from=builder /app /app
RUN chown -R www-data:www-data /app/var
USER www-data
CMD ["php-fpm"]
