#!/bin/bash

shopt -s nocaseglob

cd tsugi
# Setup the config file
if [ ! -f config.php ]; then
	echo "Setting up config.php file"
	cp config-dist.php config.php
	# Update the database to point to the container
	sed -i 's/127\.0\.0\.1/tsugi_db/g' config.php
fi

echo "Waiting for DB"
while ! nc -z tsugi_db 3306; do   
  sleep 5 # wait 5 seconds before check again
  echo "Database unavailable, rechecking in 5 seconds"
done

# Get the database setup
cd admin && php upgrade.php

# This is the entry line
apache2-foreground
