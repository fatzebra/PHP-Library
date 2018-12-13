ARG WEB_PHP_VERSION=7-apache
ARG TEST_PHP_VERSION=7-cli
FROM "php:$TEST_PHP_VERSION" AS deps

# Install git and some deps
RUN apt-get update \
 && apt-get install -y git unzip zlib1g zlib1g-dev libzip-dev

# Install composer
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
 && php -r "if (hash_file('SHA384', 'composer-setup.php') === '93b54496392c062774670ac18b134c3b3a95e5a5e5c8f1a9f115f203b75bf9a129d5daa8ba6a13e2cc8a1da0806388a8') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;" \
 && php composer-setup.php --install-dir=/bin --filename=composer \
 && php -r "unlink('composer-setup.php');" \
 && docker-php-ext-install zip \
 && rm -rf /.composer \
 && mkdir /.composer /app \
 && chmod 777 /.composer /app
WORKDIR /app
USER 1000

COPY ./composer.json ./
RUN composer config --global github-protocols https \
 && composer install --prefer-dist --no-interaction

COPY ./ ./


FROM "php:$WEB_PHP_VERSION" AS web

WORKDIR /var/www/html
COPY --from=deps /app /var/www
COPY --from=deps /app/example /var/www/html

EXPOSE 80


FROM "php:$TEST_PHP_VERSION" AS final

WORKDIR /app
COPY --from=deps /app /app
CMD vendor/bin/phpunit
