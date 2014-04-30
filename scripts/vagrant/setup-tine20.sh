#!/usr/bin/env bash

# Tine 2.0 Vhost
tine20_vhost="
<VirtualHost *:80>\n
    DocumentRoot   \"/vagrant/tine20.git/tine20/\"\n
    ServerName      tine20.vagrant\n
    ServerAlias     www.tine20.vagrant\n
    \n
    # Active Sync\n
    RewriteEngine on\n
    RewriteRule ^/Microsoft-Server-ActiveSync /index.php?frontend=activesync [E=REMOTE_USER:%{HTTP:Authorization},L,QSA]\n
    \n
    # WebDAV / CardDAV / CalDAV API\n
    RewriteCond %{REQUEST_METHOD} !^(GET|POST)$\n
    RewriteRule ^/$            /index.php?frontend=webdav [E=REMOTE_USER:%{HTTP:Authorization},L,QSA]\n
    \n
    RewriteRule ^/addressbooks /index.php?frontend=webdav [E=REMOTE_USER:%{HTTP:Authorization},L,QSA]\n
    RewriteRule ^/calendars    /index.php?frontend=webdav [E=REMOTE_USER:%{HTTP:Authorization},L,QSA]\n
    RewriteRule ^/principals   /index.php?frontend=webdav [E=REMOTE_USER:%{HTTP:Authorization},L,QSA]\n
    RewriteRule ^/webdav       /index.php?frontend=webdav [E=REMOTE_USER:%{HTTP:Authorization},L,QSA]\n
    RewriteRule ^/remote.php   /index.php?frontend=webdav [E=REMOTE_USER:%{HTTP:Authorization},L,QSA]\n
    \n
    php_value include_path "/vagrant/conf:/vagrant/tine20.git/tine20/"\n
    \n
    ErrorLog "/vagrant/logs/error_log"\n
    CustomLog "/vagrant/logs/access_log" common\n
    \n
</VirtualHost>\n"

echo -e $tine20_vhost > /etc/apache2/sites-available/tine20-vagrant

service apache2 restart

# Add database for tine20
mysql -u root -p"vagrant" -e "CREATE DATABASE tine20;"

# Enable tine20-vagrant for apache
a2ensite tine20-vagrant

# update dependencies
cd /vagrant/tine20.git/tine20
sudo -u vagrant composer install --dev --prefer-source --no-interaction

# setup directories
mkdir -p /vagrant/logs /vagrant/conf /vagrant/cache /vagrant/files /vagrant/tmp
chown www-data /vagrant/logs /vagrant/conf /vagrant/cache /vagrant/files /vagrant/tmp

# generate config.inc.php
tine20_config="
<?php return array(\n
    'database' => array(\n
        'host'          => 'localhost',\n
        'dbname'        => 'tine20',\n
        'username'      => 'root',\n
        'password'      => 'vagrant',\n
        'adapter'       => 'pdo_mysql',\n
        'tableprefix'   => 'tine20_',\n
    ),
    'setupuser' => array(\n
        'username'      => 'vagrant',\n
        'password'      => 'vagrant'\n 
    ),\n
   'login' => array(\n
       'username'      => 'vagrant',\n
       'password'      => 'vagrant'\n
    ),\n
    \n
    'caching' => array (\n
        'active' => true,\n
        'path' => '/vagrant/cache',\n
        'lifetime' => 3600,\n
    ),\n
    \n
    'logger' => array (\n
        'active' => true,\n
        'filename' => '/vagrant/logs/tine20.log',\n
        'priority' => '7',\n
    ),\n
    'filesdir'  => '/vagrant/files',\n
    'tmpdir' => '/vargrant/tmp',\n
  );\n"

if [ ! -f /vagrant/conf/config.inc.php ]; then
  echo -e $tine20_config > /vagrant/conf/config.inc.php
fi

# generate install.properties
tine20_installprops="
adminLoginName=vagrant\n
adminPassword=vagrant\n
adminEmailAddress=vagrant@tine20.vagrant\n
#authentication=\n
#accounts=\n
#imap=\n
#smtp=\n"

if [ ! -f /vagrant/conf/install.properties ]; then
  echo -e $tine20_installprops > /vagrant/conf/install.properties
fi

cd /vagrant/tine20.git/tine20
sudo -u vagrant /vagrant/tine20.git/tine20/vendor/bin/phing -D configdir=/vagrant/conf/ tine-install
