#!/bin/sh
set -e

php /var/www/scripts/migrate.php apply
php /var/www/scripts/seed.php

exec docker-php-entrypoint "$@"
