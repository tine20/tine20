#!/bin/bash

TINEPATH="${TINE20ROOT:-/usr/share}/tine20"
BACKUPFILE="/var/lib/tine20/backup/container_acl.sql"

# docker-dev
#DBCONNECT="-hdb -utine20 -ptine20pw"
#apk add mysql-client

mysql $DBCONNECT tine20 < $BACKUPFILE
