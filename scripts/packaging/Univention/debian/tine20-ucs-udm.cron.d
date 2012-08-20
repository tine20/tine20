SHELL=/bin/sh
PATH=/usr/local/sbin:/usr/local/bin:/sbin:/bin:/usr/sbin:/usr/bin

10 * * * *	www-data	/usr/bin/php -f /usr/share/tine20/setup.php -- --config /etc/tine20/config.inc.php --sync_accounts_from_ldap
