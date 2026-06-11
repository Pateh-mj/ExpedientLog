FROM php:8.2-apache

# Install PHP extensions required for PDO MySQL and typical needs
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libzip-dev zip unzip zlib1g-dev libonig-dev \
    && docker-php-ext-install pdo pdo_mysql mbstring zip \
    && rm -rf /var/lib/apt/lists/*

# Enable mod_rewrite for pretty URLs
RUN a2enmod rewrite

# Copy app into container
COPY . /var/www/html

# Set Apache document root to the `public` folder
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri 's!DocumentRoot /var/www/html!DocumentRoot ${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri 's!<Directory /var/www/html>!<Directory ${APACHE_DOCUMENT_ROOT}>!g' /etc/apache2/apache2.conf

# Ensure storage and uploads are writable
RUN mkdir -p /var/www/html/storage/uploads \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/public

EXPOSE 80

CMD ["apache2-foreground"]
