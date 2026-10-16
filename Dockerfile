FROM php:8.2-apache

# Install cURL and required dependencies
RUN apt-get update && apt-get install -y \
    libcurl4-openssl-dev \
    && docker-php-ext-install curl \
    && rm -rf /var/lib/apt/lists/*

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Copy repository files into Apache root
COPY . /var/www/html/

# Create data directory with write permissions for local logging
RUN mkdir -p /var/www/html/data \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/data

# Render dynamically assigns PORT (usually 10000), default to 80
EXPOSE 80 10000

# Start script to bind Apache to the dynamic PORT assigned by Render/Cloud hosts
CMD ["sh", "-c", "sed -i \"s/Listen 80/Listen ${PORT:-80}/\" /etc/apache2/ports.conf && sed -i \"s/<VirtualHost \\*:80>/<VirtualHost \\*:${PORT:-80}>/\" /etc/apache2/sites-available/000-default.conf && apache2-foreground"]
