#!/bin/sh
set -e

echo $0 init git submodules ...

git submodule init

echo $0 copying ...

cp ${CI_PROJECT_DIR}/ci/dockerimage/supervisor.d/conf.ini /etc/supervisor.d/;
cp ${CI_PROJECT_DIR}/ci/dockerimage/supervisor.d/nginx.ini /etc/supervisor.d/;
cp ${CI_PROJECT_DIR}/ci/dockerimage/supervisor.d/php-fpm.ini /etc/supervisor.d/;
cp ${CI_PROJECT_DIR}/ci/dockerimage/supervisor.d/tail.ini /etc/supervisor.d/;
cp ${CI_PROJECT_DIR}/ci/dockerimage/supervisor.d/crond.ini /etc/supervisor.d/;
cp ${CI_PROJECT_DIR}/ci/dockerimage/supervisor.d/webpack.ini /etc/supervisor.d/;
cp ${CI_PROJECT_DIR}/ci/dockerimage/scripts/* /usr/local/bin/;
cp ${CI_PROJECT_DIR}/phpstan.neon ${TINE20ROOT}/phpstan.neon;
cp ${CI_PROJECT_DIR}/phpstan-baseline.neon ${TINE20ROOT}/phpstan-baseline.neon;
rsync -a -I --delete ${CI_PROJECT_DIR}/etc/ /config;
rsync -a -I --delete ${CI_PROJECT_DIR}/ci/dockerimage/confd/conf.d/ /etc/confd/conf.d;
rsync -a -I --delete ${CI_PROJECT_DIR}/ci/dockerimage/confd/templates/ /etc/confd/templates;
rsync -a -I --delete ${CI_PROJECT_DIR}/etc/tine20/config.inc.php.tmpl /etc/confd/templates/config.inc.php.tmpl
rsync -a -I --delete ${CI_PROJECT_DIR}/etc/nginx/sites-available/tine20.conf.tmpl /etc/confd/templates/nginx-vhost.conf.tmpl
rsync -a -I --delete ${CI_PROJECT_DIR}/etc/nginx/conf.d/ /etc/nginx/conf.d
rsync -a -I --delete ${CI_PROJECT_DIR}/etc/nginx/snippets/ /etc/nginx/snippets
rsync -a -I --delete ${CI_PROJECT_DIR}/scripts/ ${TINE20ROOT}/scripts/;
rsync -a -I --delete ${CI_PROJECT_DIR}/tests/ ${TINE20ROOT}/tests/;
rsync -a -I --delete --exclude 'vendor' --exclude 'Tinebase/js/node_modules' --exclude 'images/icon-set' ${CI_PROJECT_DIR}/tine20/ ${TINE20ROOT}/tine20/;
rm -r ${TINE20ROOT}/tine20/vendor/metaways
cd ${TINE20ROOT}/tine20;
composer install --no-ansi --no-progress --no-suggest --no-scripts

$CI_PROJECT_DIR/ci/scripts/install_custom_app.sh

echo $0 ... done
