FROM php:8.2-apache
RUN docker-php-ext-install gd mysqli
COPY . /var/www/html/
RUN echo "DirectoryIndex index99.php" > /etc/apache2/conf-enabled/custom-index.conf
EXPOSE 80
