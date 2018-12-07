FROM composer:latest AS deps

WORKDIR /app
COPY ./composer.json ./
RUN composer install --prefer-source --no-interaction

COPY ./ ./


FROM php:7-apache AS web

WORKDIR /var/www/html
COPY --from=deps /app /var/www/html

EXPOSE 80


FROM php:7-cli AS final

WORKDIR /app
COPY --from=deps /app /app
CMD vendor/bin/phpunit
