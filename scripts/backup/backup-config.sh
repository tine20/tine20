#!/bin/bash

BACKUP_PATH=$(mktemp --directory --tmpdir=/tmp/)
TODAY=$(date +"%Y-%m-%d")

test -d /var/lib/tine20/backup/$TODAY || mkdir -p /var/lib/tine20/backup/$TODAY

cp -ra /etc/tine20/config.inc.php /var/lib/tine20/backup/$TODAY