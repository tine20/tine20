#!/bin/bash

BACKUP_PATH=$(mktemp --directory --tmpdir=/tmp/)
TODAY=$(date +"%Y-%m-%d")

if [ ! -x /usr/bin/innobackupex ]; then
    echo "innobackupex not found exiting"
    exit 1
fi

if [ ! -x /usr/bin/xtrabackup ]; then
    echo "xtrabackup not found exiting"
    exit 1
fi

# MyISAM and Innodb tables
innobackupex --defaults-extra-file /etc/tine20/xtrabackup.cnf --no-timestamp $BACKUP_PATH/mysql

# prepare Innodb tables
xtrabackup --prepare --target-dir=$BACKUP_PATH/mysql
xtrabackup --prepare --target-dir=$BACKUP_PATH/mysql


(cd $BACKUP_PATH/mysql/ && tar cjf ../full_mysql.tar.bz2 .)

rm -rf $BACKUP_PATH/mysql

(cd /var/lib/tine20/files; tar cjf $BACKUP_PATH/tine20_files.tar.bz2 .)

test -d /var/lib/tine20/backup/$TODAY || mkdir -p /var/lib/tine20/backup/$TODAY

mv $BACKUP_PATH/full_mysql.tar.bz2 /var/lib/tine20/backup/$TODAY
mv $BACKUP_PATH/tine20_files.tar.bz2 /var/lib/tine20/backup/$TODAY

rm -rf $BACKUP_PATH

