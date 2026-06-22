FROM php:8.3-fpm-alpine

RUN apk add --no-cache \
    git \
    unzip \
    libzip-dev \
    && docker-php-ext-install pdo_mysql pdo_sqlite

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY api/composer.json ./
RUN composer install --no-dev --optimize-autoloader --no-interaction

COPY api/ ./
COPY scripts/ /var/www/scripts/

RUN mkdir -p /var/data && chown -R www-data:www-data /var/data /var/www/html

COPY api/docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD ["php-fpm"]
