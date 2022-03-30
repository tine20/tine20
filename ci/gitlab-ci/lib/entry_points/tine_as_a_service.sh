if test "${ARG_COPY_SOURCE}" == "true"; then
    echo "copy src to container ...";
    apk add rsync;

    echo "wait for mount"; date;
    while [ ! -f /${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/tine_as_service.env ]; do
        echo "Waiting for tine20...";
        sleep 1; 
    done;
    sleep 10;
    echo "tine20 dir available";
    echo "loading additional env variable from /${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/tine_as_service.env";
    source /${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/tine_as_service.env;
    echo "%%%% befor source copy" >> ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/timelog; date >> ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/timelog;
    ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/ci/scripts/copy_source.sh;
    echo "%%%% after source copy" >> ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/timelog; date >> ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/timelog;
fi
echo "%%%% setup ..." >> ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/timelog; date >> ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/timelog;

rm /etc/supervisor.d/worker.ini || true
rm /etc/crontabs/tine20 || true
rm /etc/confd/conf.d/worker.inc.php.toml || true 

/usr/sbin/confd -onetime -backend env;

tine20_await_db;
mysql -h$MAIL_DB_HOST -uroot -p"$MYSQL_ROOT_PASSWORD" -e"CREATE DATABASE IF NOT EXISTS dovecot";
mysql -h$MAIL_DB_HOST -uroot -p"$MYSQL_ROOT_PASSWORD" -e"CREATE DATABASE IF NOT EXISTS postfix";
mysql -h$MAIL_DB_HOST -uroot -p"$MYSQL_ROOT_PASSWORD" -e"CREATE USER IF NOT EXISTS '$MYSQL_USER'@'%' IDENTIFIED BY '$MYSQL_PASSWORD';";
mysql -h$MAIL_DB_HOST -uroot -p"$MYSQL_ROOT_PASSWORD" -e"GRANT ALL PRIVILEGES ON postfix.* TO '$MYSQL_USER'@'%'";
mysql -h$MAIL_DB_HOST -uroot -p"$MYSQL_ROOT_PASSWORD" -e"GRANT ALL PRIVILEGES ON dovecot.* TO '$MYSQL_USER'@'%'";
mysql -h$MAIL_DB_HOST -uroot -p"$MYSQL_ROOT_PASSWORD" "dovecot" < /config/sql/dovecot_tables.sql;
mysql -h$MAIL_DB_HOST -uroot -p"$MYSQL_ROOT_PASSWORD" "postfix" < /config/sql/postfix_tables.sql;

touch ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/${CI_PROJECT_NAMESPACE}/tine20.log
chown tine20:tine20 ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/${CI_PROJECT_NAMESPACE}/tine20.log

echo "%%%% tine install ..." >> ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/timelog; date >> ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/timelog;
cp /etc/tine20/conf.d/disableGeoServices.inc.php.dist /etc/tine20/conf.d/disableGeoServices.inc.php

tine20_install;

echo "%%%% tine post install ..." >> ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/timelog; date >> ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/timelog;
if [ -f ${TINE20ROOT}/scripts/postInstallGitlab.sh ]; then
    ${TINE20ROOT}/scripts/postInstallGitlab.sh;
fi;

echo "%%%% tine demodata ..." >> ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/timelog; date >> ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/timelog;
if [ -z "$TINE_DEMODATASET" ]; then
    su tine20 -c "tine20.php --method Tinebase.createAllDemoData  --username=${TINE20_LOGIN_USERNAME} --password=${TINE20_LOGIN_PASSWORD}" || touch /${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/setup_failed_$CI_JOB_ID
else
    su tine20 -c "tine20.php --method Tinebase.createAllDemoData  --username=${TINE20_LOGIN_USERNAME} --password=${TINE20_LOGIN_PASSWORD}" -- demodata=set set=$TINE_DEMODATASET || touch /${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/setup_failed_$CI_JOB_ID
fi;

echo "%%%% tine supervisord start" >> ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/timelog; date >> ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/timelog;
touch ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/tine_ready_$CI_JOB_ID;
supervisord --nodaemon