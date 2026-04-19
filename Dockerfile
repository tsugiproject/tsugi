FROM php:8.4-apache
#Install git
EXPOSE 80
# Modules that this needs
# RUN apt-get update && apt-get install -y git netcat && apt-get clean -y
RUN apt-get update && apt-get install -y git netcat-openbsd && apt-get clean -y

RUN docker-php-ext-install pdo pdo_mysql mysqli
RUN a2enmod rewrite
# Copy all of the files in (.dockerignore excludes config.php)
COPY . /var/www/html/tsugi
# Image default is the Docker entry config; compose also bind-mounts the same file over config.php.
RUN cp /var/www/html/tsugi/docker/tsugi-docker-config.php /var/www/html/tsugi/config.php
