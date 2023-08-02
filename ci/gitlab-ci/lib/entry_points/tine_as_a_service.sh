#!/bin/bash
echo -n 'wait for signal_files_ready ...'; while [ ! -f ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/signal_files_ready ]; do sleep 1; done; echo ' done'

cp -r /usr/share/tine20/Tinebase/js/node_modules $TINE20ROOT/Tinebase/js/node_modules
cp -r /usr/share/tine20/vendor $TINE20ROOT/tine20/vendor

# delete potentially old code, to make sure it can not be used 
if test "${TINE20ROOT}" != "/usr/share/tine20"; then rm -rf /usr/share/tine20; fi

touch ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/signal_node_modules_copied

# install php deps
cd $TINE20ROOT/tine20
composer install --no-ansi --no-progress --no-suggest --no-scripts --ignore-platform-reqs
$TINE20ROOT/ci/scripts/install_custom_app.sh

touch ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/signal_php_deps_installed

# install js deps
npm --prefix $TINE20ROOT/tine20/Tinebase/js/ install;
touch ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/signal_js_deps_installed

# setup configs
/usr/sbin/confd -onetime -backend env;

# setup database
if ! tine20_await_db; then
    touch ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/signal_wait_for_database_failed
    touch ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/signal_tine_ready
    exit 1
fi

mysql -h$MAIL_DB_HOST -uroot -p"$MYSQL_ROOT_PASSWORD" -e"CREATE DATABASE IF NOT EXISTS dovecot";
mysql -h$MAIL_DB_HOST -uroot -p"$MYSQL_ROOT_PASSWORD" -e"CREATE DATABASE IF NOT EXISTS postfix";
mysql -h$MAIL_DB_HOST -uroot -p"$MYSQL_ROOT_PASSWORD" -e"CREATE USER IF NOT EXISTS '$MYSQL_USER'@'%' IDENTIFIED BY '$MYSQL_PASSWORD';";
mysql -h$MAIL_DB_HOST -uroot -p"$MYSQL_ROOT_PASSWORD" -e"GRANT ALL PRIVILEGES ON postfix.* TO '$MYSQL_USER'@'%'";
mysql -h$MAIL_DB_HOST -uroot -p"$MYSQL_ROOT_PASSWORD" -e"GRANT ALL PRIVILEGES ON dovecot.* TO '$MYSQL_USER'@'%'";
mysql -h$MAIL_DB_HOST -uroot -p"$MYSQL_ROOT_PASSWORD" "dovecot" < /config/sql/dovecot_tables.sql;
mysql -h$MAIL_DB_HOST -uroot -p"$MYSQL_ROOT_PASSWORD" "postfix" < /config/sql/postfix_tables.sql;

# setup tine enviroment
touch ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/${CI_PROJECT_NAMESPACE}/tine20.log
chown tine20:tine20 ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/${CI_PROJECT_NAMESPACE}/tine20.log
rm /etc/supervisor.d/worker.ini || true # todo delete when merged with "speared node container" change
rm /etc/crontabs/tine20 || true
rm /etc/confd/conf.d/worker.inc.php.toml || true 

echo -n 'wait for signal_js_deps_installed ...'; while [ ! -f ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/signal_js_deps_installed ]; do sleep 1; done; echo ' done'

# insteall tine
tine20_install;

if [ -f ${TINE20ROOT}/scripts/postInstallGitlab.sh ]; then
    ${TINE20ROOT}/scripts/postInstallGitlab.sh;
fi;

# install demodata
if [ -z "$TINE_DEMODATASET" ]; then
    su tine20 -c "tine20.php --method Tinebase.createAllDemoData  --username=${TINE20_LOGIN_USERNAME} --password=${TINE20_LOGIN_PASSWORD}" || touch ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/signal_demo_data_install_failed
else
    su tine20 -c "tine20.php --method Tinebase.createAllDemoData  --username=${TINE20_LOGIN_USERNAME} --password=${TINE20_LOGIN_PASSWORD}" -- demodata=set set=$TINE_DEMODATASET || touch ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/signal_demo_data_install_failed
fi;

touch ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/signal_tine_ready

# start tine
supervisord --nodaemon