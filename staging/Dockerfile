FROM php:8.1.29-fpm-alpine3.19

# Install system dependencies and security updates
RUN apk update && apk upgrade && apk add --no-cache \
    git \
    curl \
    libpng-dev \
    libxml2-dev \
    zip \
    unzip \
    oniguruma-dev \
    libzip-dev \
    freetype-dev \
    libjpeg-turbo-dev \
    libwebp-dev \
    supervisor \
    nginx \
    && rm -rf /var/cache/apk/*

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        opcache

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Create application user
RUN addgroup -g 1000 -S appgroup && \
    adduser -u 1000 -S appuser -G appgroup

# Set working directory
WORKDIR /var/www/html

# Copy composer files first for better caching
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

# Copy application files
COPY --chown=appuser:appgroup . .

# Run composer scripts after copying all files
RUN composer run-script post-autoload-dump

# Set secure permissions
RUN chown -R appuser:appgroup /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 /var/www/html/storage \
    && chmod -R 775 /var/www/html/bootstrap/cache \
    && find /var/www/html -type f -exec chmod 644 {} \;

# Copy configuration files with secure permissions
COPY --chown=root:root docker/nginx.conf /etc/nginx/nginx.conf
COPY --chown=root:root docker/php.ini /usr/local/etc/php/php.ini
COPY --chown=root:root docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Set secure permissions on config files
RUN chmod 644 /etc/nginx/nginx.conf \
    && chmod 644 /usr/local/etc/php/php.ini \
    && chmod 644 /etc/supervisor/conf.d/supervisord.conf

# Create necessary directories with proper ownership
RUN mkdir -p /var/log/supervisor /run/nginx /var/log/nginx \
    && chown -R appuser:appgroup /var/log/supervisor \
    && chown -R nginx:nginx /var/log/nginx /run/nginx

# Switch to non-root user
USER appuser

# Expose port
EXPOSE 80

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD curl -f http://localhost/health || exit 1

# Start supervisor
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]