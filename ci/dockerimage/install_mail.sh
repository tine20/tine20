#!/usr/bin/env sh

/usr/local/bin/php /wait_for_db.php

mysql -hdb -uroot -p"$MYSQL_ROOT_PASSWORD" -e"CREATE DATABASE IF NOT EXISTS dovecot"
mysql -hdb -uroot -p"$MYSQL_ROOT_PASSWORD" -e"CREATE DATABASE IF NOT EXISTS postfix"

mysql -hdb -uroot -p"$MYSQL_ROOT_PASSWORD" -e"CREATE USER IF NOT EXISTS '$MYSQL_USER'@'%' IDENTIFIED BY '$MYSQL_PASSWORD';"
mysql -hdb -uroot -p"$MYSQL_ROOT_PASSWORD" -e"GRANT ALL PRIVILEGES ON postfix.* TO '$MYSQL_USER'@'%'"
mysql -hdb -uroot -p"$MYSQL_ROOT_PASSWORD" -e"GRANT ALL PRIVILEGES ON dovecot.* TO '$MYSQL_USER'@'%'"

mysql -hdb -uroot -p"$MYSQL_ROOT_PASSWORD" "dovecot" < /config/dovecot_tables.sql
mysql -hdb -uroot -p"$MYSQL_ROOT_PASSWORD" "postfix" < /config/postfix_tables.sql

echo "mail stack db init"