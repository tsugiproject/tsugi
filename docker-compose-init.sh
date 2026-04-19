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

# This is the entry line
apache2-foreground
