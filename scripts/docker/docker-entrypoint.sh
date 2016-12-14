#!/bin/bash
set -e

if [ "$TINE20_DB_HOST" ]; then
	sed -i "s/ENTER DATABASE HOSTNAME/$TINE20_DB_HOST/" /opt/tine20/config.inc.php
else
	sed -i "s/ENTER DATABASE HOSTNAME/mysql-server/" /opt/tine20/config.inc.php
fi

if [ "$TINE20_DB_NAME" ]; then
	sed -i "s/ENTER DATABASE NAME/$TINE20_DB_NAME/" /opt/tine20/config.inc.php
else
	sed -i "s/ENTER DATABASE NAME/tine20/" /opt/tine20/config.inc.php
fi

if [ "$TINE20_DB_USER" ]; then
	sed -i "s/ENTER DATABASE USERNAME/$TINE20_DB_USER/" /opt/tine20/config.inc.php
else
	sed -i "s/ENTER DATABASE USERNAME/tine20/" /opt/tine20/config.inc.php
fi

if [ "$TINE20_DB_PASS" ]; then
	sed -i "s/ENTER DATABASE PASSWORD/$TINE20_DB_PASS/" /opt/tine20/config.inc.php
else
	sed -i "s/ENTER DATABASE PASSWORD//" /opt/tine20/config.inc.php
fi

if [ "$TINE20_SETUP_USER" ]; then
	sed -i "s/SETUP USERNAME/$TINE20_SETUP_USER/" /opt/tine20/config.inc.php
else
	sed -i "s/SETUP USERNAME/tine20setup/" /opt/tine20/config.inc.php
fi

if [ "$TINE20_SETUP_PASS" ]; then
	sed -i "s/SETUP PASSWORD/$TINE20_SETUP_PASS/" /opt/tine20/config.inc.php
else
	sed -i "s/SETUP PASSWORD//" /opt/tine20/config.inc.php
fi

exec "$@"
