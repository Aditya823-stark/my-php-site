FROM php:8.2-apache

# Install required PHP extensions
RUN apt-get update && apt-get install -y \
    libfreetype6-dev libjpeg62-turbo-dev libpng-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd mysqli

# Copy all files to Apache root
COPY . /var/www/html/

# Tell Apache to load index99.php as default
RUN echo "DirectoryIndex index99.php" > /etc/apache2/conf-enabled/custom-index.conf

# Set permissions
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
