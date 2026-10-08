#!/bin/bash

shopt -s nocaseglob

cd tsugi

# Setup the config file if missing (normally provided by the image + bind mount over config.php)
if [ ! -f config.php ]; then
	echo "Setting up config.php from docker/tsugi-docker-config.php"
	cp docker/tsugi-docker-config.php config.php
fi
# PDO and URLs are supplied via TSUGI_* in docker-compose.yml (see docker/tsugi-docker-config.php).

echo "Waiting for DB"
while ! nc -z tsugi_db 3306; do   
  sleep 5 # wait 5 seconds before check again
  echo "Database unavailable, rechecking in 5 seconds"
done

# Get the database setup
cd admin && php upgrade.php

# $CFG->wwwroot is http://localhost:<host port>/tsugi. The published map sends
# that host port to container port 80, so a request from PHP in this container
# to its own wwwroot never reaches Apache unless Apache also listens there.
if [ -n "${TSUGI_HOST_PORT:-}" ] && [ "$TSUGI_HOST_PORT" != "80" ]; then
  if ! grep -q "^Listen ${TSUGI_HOST_PORT}$" /etc/apache2/ports.conf; then
    echo "Listen ${TSUGI_HOST_PORT}" >> /etc/apache2/ports.conf
  fi
  vhost=/etc/apache2/sites-enabled/000-default.conf
  if [ -f "$vhost" ] && grep -q "<VirtualHost \*:80>" "$vhost"; then
    sed -i "s/<VirtualHost \*:80>/<VirtualHost *:80 *:${TSUGI_HOST_PORT}>/" "$vhost"
  fi
fi

# This is the entry line
apache2-foreground
