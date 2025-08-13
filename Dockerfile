FROM php:8.2-apache

# Install PHP extensions
RUN docker-php-ext-install gd mysqli

# Copy all project files into Apache root
COPY . /var/www/html/

# Set default homepage to index99.php
RUN echo "DirectoryIndex index99.php" > /etc/apache2/conf-enabled/custom-index.conf

# Set correct permissions
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
