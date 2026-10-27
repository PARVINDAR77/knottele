FROM php:8.2-fpm-alpine

# Install system dependencies and Nginx
RUN apk add --no-cache \
    nginx \
    supervisor \
    curl \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    icu-dev \
    oniguruma-dev \
    libxml2-dev

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        mbstring \
        bcmath \
        zip \
        gd \
        intl \
        opcache

# Set working directory
WORKDIR /var/www/html

# Copy application backend files (includes pre-built frontend in public/)
COPY backend/ /var/www/html/

# Copy Nginx, PHP, and Supervisor configs
COPY docker/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/uploads.ini /usr/local/etc/php/conf.d/uploads.ini
COPY docker/supervisord.conf /etc/supervisord.conf

# Setup permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 8090

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
