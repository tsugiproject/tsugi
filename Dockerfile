FROM php:8.1-apache
#Install git
EXPOSE 80
# Modules that this needs
RUN apt-get update && apt-get install -y git netcat && apt-get clean -y
RUN docker-php-ext-install pdo pdo_mysql mysqli
RUN a2enmod rewrite
# Copy all of the files in
COPY . /var/www/html/tsugi
