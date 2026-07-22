#!/bin/sh
set -e

mkdir -p /var/www/var/cache /var/www/var/log

chown -R www-data:www-data /var/www/var

exec "$@"