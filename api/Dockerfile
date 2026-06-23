FROM php:8.3-fpm-alpine

RUN apk add --no-cache \
    git \
    unzip \
    libzip-dev \
    && docker-php-ext-install pdo_mysql pdo_sqlite

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Match monorepo layout: /var/www/api + /var/www/scripts (migrate.php expects this)
WORKDIR /var/www/api

COPY api/composer.json ./
RUN composer install --no-dev --optimize-autoloader --no-interaction

COPY api/ ./

WORKDIR /var/www
COPY scripts/ /var/www/scripts/

RUN mkdir -p /var/data && chown -R www-data:www-data /var/data /var/www/api

COPY api/docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

WORKDIR /var/www/api

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD ["php-fpm"]
