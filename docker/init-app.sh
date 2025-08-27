#!/bin/sh
set -e

cd /var/www/html

composer install --no-dev --optimize-autoloader --prefer-dist

php artisan key:generate --force

php artisan migrate --force
php artisan migrate:refresh --seed
php artisan storage:link

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan optimize

chmod -R 775 storage bootstrap/cache

php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan event:clear
