
Just Some Notes
---------------

LAMP If not already there:

    apt-get update
    apt-get install -y --force-yes software-properties-common python-software-properties vim htop curl git npm
    add-apt-repository ppa:ondrej/php
    apt-get update
    apt-get install -y --force-yes zip
    apt-get install git
    apt-get install -y --force-yes apache2
    apt-get install -y --force-yes php7.1 
    apt-get install -y --force-yes libapache2-mod-php7.1 php7.1-cli php7.1-common php7.1-mbstring php7.1-gd php7.1-intl php7.1-xml php7.1-mysql php7.1-mcrypt php7.1-zip

Get configuration right

    a2enmod rewrite
    a2enmod expires
    a2enmod headers
    service apache2 restart
