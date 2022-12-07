FROM composer:latest AS composer
FROM php:7.4
ENV COMPOSER_ALLOW_SUPERUSER=1
WORKDIR /app
COPY --from=composer /usr/bin/composer /usr/bin/composer
COPY . /app/
RUN apt update -y
RUN apt install zip -y
RUN docker-php-ext-install mysqli
RUN composer install --ignore-platform-req=ext-sockets