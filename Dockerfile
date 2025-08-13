FROM php:8.2-apache

# Install dependencies for GD and mysqli
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    && docker-php-ext-configure gd --with-jpeg --with-freetype \
    && docker-php-ext-install gd mysqli

# Copy all files to Apache's root folder
COPY . /var/www/html/

# Set correct permissions
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
