FROM php:8.2-fpm

ENV COMPOSER_ALLOW_SUPERUSER=1

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    libicu-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    nginx \
    nodejs \
    npm \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
    pdo \
    pdo_mysql \
    gd \
    zip \
    intl \
    mbstring \
    xml \
    ctype \
    iconv \
    fileinfo \
    opcache

# Configure OPcache for production
RUN echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.memory_consumption=256" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.max_accelerated_files=20000" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.validate_timestamps=0" >> /usr/local/etc/php/conf.d/opcache.ini

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy composer files first for layer caching
COPY composer.json composer.lock ./

# Install PHP dependencies (no dev, optimized autoloader)
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

# Copy package files and install Node dependencies
COPY package.json webpack.config.js postcss.config.mjs ./
RUN npm install

# Copy the rest of the application
COPY . .

# Regenerate Composer autoload files after the full source tree is present.
RUN composer dump-autoload --classmap-authoritative --no-dev --no-interaction

# Build frontend assets for production
RUN npm run build

# Run Composer scripts now that full code is present
RUN composer run-script --no-dev post-install-cmd || true

# Set proper permissions
RUN mkdir -p \
    /var/www/html/var/cache \
    /var/www/html/var/log \
    /var/www/html/var/sessions \
    /var/www/html/config/jwt \
    && chown -R www-data:www-data /var/www/html/var /var/www/html/config/jwt \
    && chmod -R 775 /var/www/html/var

# Copy nginx config template
COPY docker/nginx.conf /etc/nginx/sites-available/default.template

# Expose port
EXPOSE 80

# Start script
COPY docker/start.sh /start.sh
RUN chmod +x /start.sh

CMD ["/start.sh"]
