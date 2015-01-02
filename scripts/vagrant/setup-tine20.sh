#!/usr/bin/env bash

# Tine 2.0 Vhost
tine20_vhost="
<VirtualHost *:80>\n
    DocumentRoot   \"/usr/local/share/tine20.git/tine20/\"\n
    ServerName      tine20.vagrant\n
    ServerAlias     www.tine20.vagrant\n
    \n
     <Directory /usr/local/share/tine20.git/tine20/>\n
       AllowOverride None\n
       Require all granted\n
     </Directory>\n
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
    php_value include_path "/etc/tine20:/usr/local/share/tine20.git/tine20/"\n
    \n
    ErrorLog "/var/log/tine20/error_log"\n
    CustomLog "/var/log/tine20/access_log" common\n
    \n
</VirtualHost>\n"

echo -e $tine20_vhost > /etc/apache2/sites-available/tine20-vagrant.conf

service apache2 restart

# Add database for tine20
mysql -u root -p"vagrant" -e "CREATE DATABASE tine20;"

# Enable tine20-vagrant for apache
a2ensite tine20-vagrant

# update dependencies
cd /usr/local/share/tine20.git/tine20
sudo -u vagrant composer install --dev --prefer-source --no-interaction

# setup directories
mkdir -p /var/lib/tine20/cache /var/lib/tine20/files /var/lib/tine20/tmp
mkdir -p /etc/tine20
mkdir -p /var/log/tine20
chown -R vagrant /var/lib/tine20
chown -R vagrant /var/log/tine20

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
        'path' => '/var/lib/tine20/cache',\n
        'lifetime' => 3600,\n
    ),\n
    \n
    'logger' => array (\n
        'active' => true,\n
        'filename' => '/var/log/tine20/tine20.log',\n
        'priority' => '7',\n
    ),\n
    'filesdir'  => '/var/lib/tine20/files',\n
    'tmpdir' => '/var/lib/tine20/tmp',\n
  );\n"

if [ ! -f /etc/tine20/config.inc.php ]; then
  echo -e $tine20_config > /etc/tine20/config.inc.php
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

if [ ! -f /etc/tine20/install.properties ]; then
  echo -e $tine20_installprops > /etc/tine20/install.properties
fi

cd /usr/local/share/tine20.git/tine20
sudo -u vagrant /usr/local/share/tine20.git/tine20/vendor/bin/phing -D configdir=/etc/tine20/ tine-install

echo "
##################################################################################
# Welcome to tine20.vagrant
#
# 1. make sure you have a tine20.vagrant entry in you /etc/hosts file
#    with your vagrant machine ip (10.10.10.10 per default)
# 2. navigate in your browser to
#      http://tine20.vagrant (normal login)
#      http://tine20.vagrant/setup.php (setup)
#        username: vagrant
#        password: vagrant
# 3. to run phpunit tests:
       vagrant ssh
       cd /usr/local/share/tine20.git/tests/tine20
       /usr/local/share/tine20.git/tine20/vendor/bin/phpunit \
 -d include_path=/etc/tine20 -d memory_limit=-1 AllTests.php
#
# 4. Happy codeing! :-)
##################################################################################
"