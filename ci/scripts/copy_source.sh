#!/bin/sh
set -e

echo $0 init git submodules ...

cd ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20

git submodule init
git submodule update

echo $0 copying ...

#cp ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/ci/dockerimage/supervisor.d/conf.ini /etc/supervisor.d/;
#cp ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/ci/dockerimage/supervisor.d/nginx.ini /etc/supervisor.d/;
#cp ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/ci/dockerimage/supervisor.d/php-fpm.ini /etc/supervisor.d/;
#cp ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/ci/dockerimage/supervisor.d/tail.ini /etc/supervisor.d/;
#cp ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/ci/dockerimage/supervisor.d/crond.ini /etc/supervisor.d/;
cp ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/ci/dockerimage/supervisor.d/webpack.ini /etc/supervisor.d/;
#cp ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/ci/dockerimage/scripts/* /usr/local/bin/;
cp ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/phpstan.neon ${TINE20ROOT}/phpstan.neon;
cp ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/phpstan-baseline.neon ${TINE20ROOT}/phpstan-baseline.neon;
rsync -a -I --delete ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/etc/ /config;
#rsync -a -I --delete ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/ci/dockerimage/confd/conf.d/ /etc/confd/conf.d;
#rsync -a -I --delete ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/ci/dockerimage/confd/templates/ /etc/confd/templates;
#rsync -a -I --delete ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/etc/tine20/config.inc.php.tmpl /etc/confd/templates/config.inc.php.tmpl
#rsync -a -I --delete ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/etc/nginx/sites-available/tine20.conf.tmpl /etc/confd/templates/nginx-vhost.conf.tmpl
#rsync -a -I --delete ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/etc/nginx/conf.d/ /etc/nginx/conf.d
#rsync -a -I --delete ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/etc/nginx/snippets/ /etc/nginx/snippets
rsync -a -I --delete ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/scripts/ ${TINE20ROOT}/scripts/;
rsync -a -I --delete ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/tests/ ${TINE20ROOT}/tests/;
rsync -a -I --delete --exclude 'vendor' --exclude 'Tinebase/js/node_modules' ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/tine20/ ${TINE20ROOT}/tine20/;
rm -r ${TINE20ROOT}/tine20/vendor/metaways || true
cd ${TINE20ROOT}/tine20;

if test "COMPOSER_LOCK_REWRITE" == "true"; then
    php ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/scripts/packaging/composer/composerLockRewrite.php ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/tine20/composer.lock satis.default.svc.cluster.local;
fi
composer install --no-ansi --no-progress --no-suggest --no-scripts

${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/ci/scripts/install_custom_app.sh

echo $0 ... done
