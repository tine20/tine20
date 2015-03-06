#!/bin/bash

SOURCE_PATH=$1

if [ -e $SOURCE_PATH/config.inc.php ]; then
    cp $SOURCE_PATH/config.inc.php /etc/tine20/config.inc.php
    chown root:www-data /etc/tine20/config.inc.php
    chmod 0660 /etc/tine20/config.inc.php
fi