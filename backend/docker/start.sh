#!/bin/bash
set -e

# 1. Clear corrupt route cache (bkash packages have duplicate route name)
php artisan route:clear 2>/dev/null || true

# 2. Cache config + views at RUNTIME (not build time) so Coolify's
#    runtime env vars (DB_HOST, APP_KEY, etc.) are correctly captured.
php artisan config:cache 2>/dev/null || true
php artisan view:cache 2>/dev/null || true

# 3. Ensure storage dirs exist and are world-writable
mkdir -p /app/storage/logs /app/storage/framework/sessions /app/storage/framework/views /app/storage/framework/cache
chmod -R 777 /app/storage 2>/dev/null || true

# 4. Ensure log file is writable by PHP-FPM (www-data)
touch /app/storage/logs/laravel.log
chmod 666 /app/storage/logs/laravel.log

# 5. Increase PHP memory limit (38K+ users need more than default 128M)
echo "memory_limit = 512M" > /usr/local/etc/php/conf.d/memory.ini

# 6. Start PHP-FPM + Nginx
perl /assets/transform-config.pl /assets/nginx.template /nginx.conf
php-fpm -y /assets/php-fpm.conf &
nginx -c /nginx.conf
