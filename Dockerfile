FROM php:8.2-apache
RUN docker-php-ext-install gd mysqli
COPY . /var/www/html/
EXPOSE 80
