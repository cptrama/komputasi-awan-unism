FROM php:8.2-apache

# Install required system packages & libraries
RUN apt-get update && apt-get install -y --no-install-recommends \
    libzip-dev \
    libsqlite3-dev \
    zip \
    unzip \
    git \
    && docker-php-ext-install \
    zip \
    pdo_sqlite \
    opcache \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy Apache configuration and PHP settings
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/custom.ini
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh

# Fix Windows CRLF line endings on entrypoint and make executable
RUN sed -i 's/\r$//' /usr/local/bin/entrypoint.sh \
    && chmod +x /usr/local/bin/entrypoint.sh

# Copy application source code
COPY . /var/www/html

# Install Composer dependencies (production optimize)
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Prepare directories and assign permissions
RUN mkdir -p /var/www/html/database /var/www/html/storage/uploads /var/www/html/storage/logs \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/database /var/www/html/storage

# Expose default HTTP port
EXPOSE 80

# Start container through entrypoint
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
