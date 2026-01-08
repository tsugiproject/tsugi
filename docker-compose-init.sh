#!/bin/bash

shopt -s nocaseglob

cd tsugi

# Setup the config file
if [ "${TSUGI_DOCKER_FORCE_CONFIG:-0}" = "1" ] || [ ! -f config.php ]; then
	echo "Setting up config.php file"
	cp config-dist.php config.php
	# Update the database to point to the container
	sed -i 's/127\.0\.0\.1/tsugi_db/g' config.php
fi

if [ -n "${TSUGI_ADMIN_PW:-}" ]; then
	php -r '
		$path = "config.php";
		$pw = getenv("TSUGI_ADMIN_PW");
		if ($pw === false || $pw === "") { exit(0); }
		$contents = file_get_contents($path);
		if ($contents === false) { fwrite(STDERR, "Unable to read config.php\n"); exit(1); }
		$escaped = str_replace("\\\\", "\\\\\\\\", $pw);
		$escaped = str_replace("'", "\\\\'", $escaped);
		$replacement = "\$CFG->adminpw = \\'" . $escaped . "\\';";
		$count = 0;
		$contents = preg_replace("/\\\$CFG->adminpw\\s*=\\s*[^;]*;/", $replacement, $contents, 1, $count);
		if ($count === 0) {
			$contents .= "\\n" . $replacement . "\\n";
		}
		file_put_contents($path, $contents);
	'
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
