#!/bin/sh
set -e

php artisan migrate --force
php artisan db:seed --class=RolePermissionSeeder --force

if [ ! -L public/storage ]; then
    php artisan storage:link
fi

exec php artisan serve --host=0.0.0.0 --port="${PORT:-10000}"
