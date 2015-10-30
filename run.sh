#!/bin/sh
##

#startup mysql
service mysql start; mysql -u root -e "CREATE USER 'admin'@'%' IDENTIFIED BY 'pass';" && mysql -u root -e "GRANT ALL PRIVILEGES ON *.* TO 'admin'@'%' WITH GRANT OPTION;";

# Change to web-root
cd /var/www

# Download and run Composer
wget http://getcomposer.org/composer.phar -O composer.phar
php composer.phar install --optimize-autoloader --prefer-dist

# Symfony2 actions

php app/console doctrine:database:create --env=prod --no-interaction
php app/console doctrine:schema:update --force --env=prod --no-interaction

# Get rid of nasty root permissions
chown -R www-data:root /var/www

# Run Apache2
/usr/sbin/apache2ctl -D FOREGROUND

