SHELL=/bin/bash
PATH=/sbin:/bin:/usr/sbin:/usr/bin

* * * * *	www-data	/usr/bin/php -f /usr/share/tine20/tine20.php -- --config=/etc/tine20/config.inc.php --method=Tinebase.triggerAsyncEvents | logger -p daemon.notice -t "Tine 2.0 scheduler"
