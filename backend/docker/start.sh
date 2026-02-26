#!/bin/bash
set -e

# 1. Clear corrupt route cache
# karim007/laravel-bkash and laravel-bkash-tokenize both register a
# route named "bkash-refund", which causes route:cache to fail.
php artisan route:clear 2>/dev/null || true

# 2. Ensure storage dirs exist and are world-writable
mkdir -p /app/storage/logs /app/storage/framework/sessions /app/storage/framework/views /app/storage/framework/cache
chmod -R 777 /app/storage 2>/dev/null || true

# 3. Ensure log file exists and is writable by PHP-FPM (www-data)
touch /app/storage/logs/laravel.log
chmod 666 /app/storage/logs/laravel.log

# 4. Start PHP-FPM + Nginx
perl /assets/transform-config.pl /assets/nginx.template /nginx.conf
php-fpm -y /assets/php-fpm.conf &
nginx -c /nginx.conf
