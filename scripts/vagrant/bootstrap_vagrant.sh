#!/usr/bin/env bash

# Mysql server setup
echo mysql-server mysql-server/root_password select vagrant | debconf-set-selections
echo mysql-server mysql-server/root_password_again select vagrant | debconf-set-selections

# phpMyAdmin setup
echo phpmyadmin phpmyadmin/dbconfig-install boolean true | debconf-set-selections
echo phpmyadmin phpmyadmin/app-password-confirm password vagrant | debconf-set-selections
echo phpmyadmin phpmyadmin/mysql/admin-pass password vagrant | debconf-set-selections
echo phpmyadmin phpmyadmin/mysql/app-pass password vagrant | debconf-set-selections
echo phpmyadmin phpmyadmin/reconfigure-webserver multiselect apache2 | debconf-set-selections

################### INSTALL AND UPDATE ######################

# Remove backports
sed -i 's/.*backports.*//g' /etc/apt/sources.list

# Update package lists
apt-get update

# Install lamp stack
apt-get install -y mysql-server apache2 php5 libapache2-mod-php5 php5-mysql php5-gd php5-curl php-pear php5-xsl phpmyadmin

# run apache as vagrant to ease things
service apache2 stop
sed -i 's/www-data/vagrant/g' /etc/apache2/envvars
chown -R vagrant /var/lock/apache2
service apache2 start

# Install dev tools
apt-get install -y  vim git subversion curl make wget nfs-common portmap

# Additional stuff
apt-get install -y zsh language-pack-de

# Setup composer or if allready installed it would update
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer

# Install jslint
#svn co https://svn.code.sf.net/p/javascriptlint/code/trunk jsl 
#cd jsl/
#python setup.py install

#################### USER CONFIG ##########################

# Get grml-zsh
#wget -O /home/vagrant/.zshrc http://git.grml.org/f/grml-etc-core/etc/zsh/zshrc

# Set zsh as default shell
#chsh vagrant -s /usr/bin/zsh

# Create www link to /vagrant
#ln -s /vagrant /home/vagrant/www

################# MYSQL AND WEBSERVER #####################

# Allow unsecured remote access to MySQL.
mysql -u root -p"vagrant" -e "GRANT ALL PRIVILEGES ON *.* TO 'root'@'%' IDENTIFIED BY '' WITH GRANT OPTION; FLUSH PRIVILEGES;"

# Fix mysql bug
sed -e 's/127.0.0.1/0.0.0.0/g' -i '/etc/mysql/my.cnf'

# Allow modules for Apache.
a2enmod rewrite

# Disable apache default sites
a2dissite default 000-default

# phpmyadmin vhost
phpmyadmin_vhost="
<VirtualHost *:80>\n
	DocumentRoot	\"/usr/share/phpmyadmin\"\n
	ServerName	pma.local\n
	ServerAlias	www.pma.local\n
</VirtualHost>\n"

echo -e $phpmyadmin_vhost > /etc/apache2/sites-available/pma-local


# Enable pma-local for apache
a2ensite pma-local

# Allow override for default site
sed -i '/AllowOverride None/c AllowOverride All' /etc/apache2/sites-available/default

# Remove phpmyadmin alias to enforce own vhost
sed -i 's/^Alias.*$//' /etc/apache2/conf.d/phpmyadmin.conf

###################### INSTALL TINE20 ######################
if [ -d /vagrant/tine20.git/tine20 ]; then
    source /vagrant/setup-tine20.sh
fi

###################### FINALS ###########################

# Restart / Start services and clean up
service apache2 restart
service mysql restart
apt-get clean

# Add apache2 to autostart
update-rc.d apache2 enable
