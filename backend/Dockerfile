FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libwebp-dev \
    libgd-dev \
    libicu-dev \
    zip \
    unzip \
    nginx \
    supervisor \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip intl opcache \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /app

# Copy composer files first for better caching
COPY composer.json composer.lock ./

# Install dependencies (no dev)
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-scripts

# Copy the rest of the application
COPY . .

# Run post-install scripts
RUN composer dump-autoload --optimize

# Create required directories
RUN mkdir -p storage/framework/{sessions,views,cache} \
    && mkdir -p bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Configure Nginx
RUN echo 'server { \n\
    listen 80; \n\
    server_name _; \n\
    root /app/public; \n\
    index index.php; \n\
    \n\
    client_max_body_size 100M; \n\
    \n\
    location / { \n\
        try_files $uri $uri/ /index.php?$query_string; \n\
    } \n\
    \n\
    location ~ \.php$ { \n\
        fastcgi_pass 127.0.0.1:9000; \n\
        fastcgi_index index.php; \n\
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name; \n\
        include fastcgi_params; \n\
        fastcgi_read_timeout 300; \n\
    } \n\
    \n\
    location ~ /\.ht { \n\
        deny all; \n\
    } \n\
}' > /etc/nginx/sites-available/default

# Configure Supervisor
RUN echo '[supervisord] \n\
nodaemon=true \n\
\n\
[program:php-fpm] \n\
command=php-fpm \n\
autostart=true \n\
autorestart=true \n\
\n\
[program:nginx] \n\
command=nginx -g "daemon off;" \n\
autostart=true \n\
autorestart=true' > /etc/supervisor/conf.d/app.conf

# Configure PHP opcache
RUN echo 'opcache.enable=1 \n\
opcache.memory_consumption=256 \n\
opcache.interned_strings_buffer=64 \n\
opcache.max_accelerated_files=30000 \n\
opcache.validate_timestamps=0' > /usr/local/etc/php/conf.d/opcache.ini

EXPOSE 80

# Cache Laravel config/routes/views and start supervisor
CMD php artisan config:cache && \
    php artisan route:cache && \
    php artisan view:cache && \
    /usr/bin/supervisord -c /etc/supervisor/supervisord.conf
