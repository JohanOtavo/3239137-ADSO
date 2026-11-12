# Backend image. infra composes it, but the Dockerfile lives with the code it
# builds.
FROM php:8.3-cli

# pdo_pgsql is the only extension to add; everything else ships with the image.
RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev unzip \
    && docker-php-ext-install pdo_pgsql

COPY --from=composer:2.8 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY docker/php.ini /usr/local/etc/php/conf.d/filmgear.ini

# Dependencies first, so a code change does not reinstall them.
COPY composer.json composer.lock* ./
RUN composer install --no-interaction --no-scripts --prefer-dist

COPY . .
RUN composer dump-autoload --optimize

EXPOSE 8000

CMD ["sh", "docker/entrypoint.sh"]
