#!/bin/bash
set -e

# ── 1. Clear corrupt route cache ──────────────────────────────────
# karim007/laravel-bkash and laravel-bkash-tokenize both register a
# route named "bkash-refund", which causes route:cache to fail and
# can leave a corrupt cache that breaks ALL routes with 500.
php artisan route:clear 2>/dev/null || true

# ── 2. Ensure storage dirs exist and are world-writable ──────────
mkdir -p /app/storage/logs /app/storage/framework/sessions \
         /app/storage/framework/views /app/storage/framework/cache

chmod -R ugo+w /app/storage 2>/dev/null || true

# Ensure log file exists and is writable BY PHP-FPM (www-data)
touch /app/storage/logs/laravel.log
chmod 666 /app/storage/logs/laravel.log

# ── 3. Start PHP-FPM + Nginx ─────────────────────────────────────
perl /assets/transform-config.pl /assets/nginx.template /nginx.conf
php-fpm -y /assets/php-fpm.conf &
nginx -c /nginx.conf
