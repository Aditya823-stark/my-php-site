FROM php:8.2-apache

# Install required PHP extensions
RUN docker-php-ext-install gd mysqli

# Copy files from admin folder into Apache root
COPY admin/ /var/www/html/

# Set correct permissions
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
