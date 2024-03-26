ARG PHP_VERSION=8.3

FROM composer:latest AS composer

FROM php:${PHP_VERSION}-cli-alpine

RUN apk add libpq-dev && docker-php-ext-install pdo pdo_mysql pdo_pgsql && docker-php-ext-enable pdo pdo_mysql pdo_pgsql

COPY --from=composer /usr/bin/composer /usr/bin/composer
