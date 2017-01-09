Using Google's Login
--------------------

https://console.developers.google.com/apis/credentials

Set the configuration values:

    $CFG->google_client_id = false; // '96041-nljpjj8jlv4.apps.googleusercontent.com';
    $CFG->google_client_secret = false; // '6Q7w_x4ESrl29a';
    $CFG->google_map_api_key = false; // 'Ve8eH49498430843cIA9IGl8';

Make sure to add the proper JavaScript origins and authorized redirect urls.
Here are some of my JavaScript Origins:

    https://online.dr-chuck.com
    http://localhost
    https://online.dr-chuck.com
    https://pr4e.dr-chuck.com
    https://lti-tools.dr-chuck.com
    https://www.php-intro.com
    https://pylearn.sites.uofmhosting.net
    https://www.py4e.com
    https://www.wa4e.com

You don't need a port number for the JavaScript origin.

Here are my sample redirect URIs:

    http://localhost/GoogleLogin/index.php
    http://localhost/tsugi/login.php
    https://lti-tools.dr-chuck.com/tsugi/login.php
    https://online.dr-chuck.com/login.php
    https://pr4e.dr-chuck.com/tsugi/login.php
    https://pr4e.dr-chuck.com/GoogleLogin/index.php
    https://www.php-intro.com/tsugi/login.php
    http://localhost/pythonlearn/tsugi/login.php
    https://www.py4e.com/tsugi/login.php
    https://www.wa4e.com/tsugi/login.php
    http://localhost/wa4e/tsugi/login.php

Again, the port seems not to matter.

