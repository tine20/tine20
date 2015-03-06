#!/bin/bash

SOURCE_PATH=$1

(cd /var/lib/mysql/; tar xf $SOURCE_PATH/full_mysql.tar.bz2 .)
(cd /var/lib/tine20/files; tar xf $SOURCE_PATH/tine20_files.tar.bz2 .)

chown -R mysql:mysql /var/lib/mysql/*
chown -R www-daza:www-data /var/lib/tine20/files/*