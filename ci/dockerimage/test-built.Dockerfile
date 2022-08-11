# description:
#   This image is used to run test in the ci.
#
# build:
#   $ docker build [...] --build-arg='BUILT_IMAGE=built-tag' --build-arg='SOURCE_IMAGE=source-tag' .
#
# ARGS:
#   BUILT_IMAGE=built
#   SOURCE_IMAGE=source
#   TINE20ROOT=/usr/share
#   NPM_INSTALL_COMMAND="npm --no-optional install" - used set npm proxy in the ci

ARG SOURCE_IMAGE=source
ARG BUILT_IMAGE=built

#  -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -
FROM ${SOURCE_IMAGE} as source-copy
# COPY --from can not use build args

#  -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -
FROM ${BUILT_IMAGE} as test-built
ARG NPM_INSTALL_COMMAND="npm --no-optional install"
ARG TINE20ROOT=/usr/share

RUN apk add --update --no-cache git mysql-client jq rsync composer build-base
RUN apk add --no-cache --repository http://dl-cdn.alpinelinux.org/alpine/v3.12/main/ npm=12.22.12-r0 nodejs=12.22.12-r0

COPY etc /config

COPY --from=source-copy ${TINE20ROOT}/tests ${TINE20ROOT}/tests
COPY --from=source-copy ${TINE20ROOT}/scripts ${TINE20ROOT}/scripts
COPY --from=source-copy ${TINE20ROOT}/.git ${TINE20ROOT}/.git
COPY --from=source-copy ${TINE20ROOT}/tine20/vendor ${TINE20ROOT}/tine20/vendor
COPY --from=source-copy ${TINE20ROOT}/tine20/library/ExtJS/src/locale ${TINE20ROOT}/tine20/library/ExtJS/src/locale
COPY --from=source-copy ${TINE20ROOT}/tine20/composer.json ${TINE20ROOT}/tine20/composer.json
COPY --from=source-copy ${TINE20ROOT}/tine20/composer.lock ${TINE20ROOT}/tine20/composer.lock
COPY --from=source-copy ${TINE20ROOT}/tine20/Tinebase/js/package.json ${TINE20ROOT}/tine20/Tinebase/js/package.json
COPY --from=source-copy ${TINE20ROOT}/tine20/Tinebase/js/npm-shrinkwrap.json ${TINE20ROOT}/tine20/Tinebase/js/npm-shrinkwrap.json
COPY --from=source-copy ${TINE20ROOT}/tine20/Tinebase/js/node_modules ${TINE20ROOT}/tine20/Tinebase/js/node_modules
COPY --from=source-copy ${TINE20ROOT}/tine20/Tinebase/js/Locale/static ${TINE20ROOT}/tine20/Tinebase/js/Locale/static

RUN if [ "COMPOSER_LOCK_REWRITE" == "true" ]; then \
        php ${TINE20ROOT}/scripts/packaging/composer/composerLockRewrite.php ${TINE20ROOT}/tine20/composer.lock satis.default.svc.cluster.local; \
    fi
RUN cd ${TINE20ROOT}/tine20 && composer install --no-ansi --no-progress --no-suggest

RUN cd ${TINE20ROOT}/tine20/Tinebase/js && ${NPM_INSTALL_COMMAND}
