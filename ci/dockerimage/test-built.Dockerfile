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
#   PUPPETEER_SKIP_CHROMIUM_DOWNLOAD=true
#   NPM_INSTALL_COMMAND="npm --no-optional install" - used set npm proxy in the ci
#   NODE_TLS_REJECT_UNAUTHORIZED=1 - needed to use the npm proxy

ARG SOURCE_IMAGE=source
ARG BUILT_IMAGE=built

#  -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -
FROM ${SOURCE_IMAGE} as source-copy
# COPY --from can not use build args

#  -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -
FROM ${BUILT_IMAGE} as test-built
ARG PUPPETEER_SKIP_CHROMIUM_DOWNLOAD=true
ARG NPM_INSTALL_COMMAND="npm --no-optional install"
ARG NODE_TLS_REJECT_UNAUTHORIZED=1
ARG TINE20ROOT=/usr/share

RUN apk add --no-cache composer git npm mysql-client

COPY docs/config/ /config

COPY --from=source-copy ${TINE20ROOT}/tests ${TINE20ROOT}/tests
COPY --from=source-copy ${TINE20ROOT}/scripts ${TINE20ROOT}/scripts
COPY --from=source-copy ${TINE20ROOT}/tine20/vendor ${TINE20ROOT}/tine20/vendor
COPY --from=source-copy ${TINE20ROOT}/tine20/library/ExtJS/src/locale ${TINE20ROOT}/tine20/library/ExtJS/src/locale
COPY --from=source-copy ${TINE20ROOT}/tine20/composer.json ${TINE20ROOT}/tine20/composer.json
COPY --from=source-copy ${TINE20ROOT}/tine20/composer.lock ${TINE20ROOT}/tine20/composer.lock
COPY --from=source-copy ${TINE20ROOT}/tine20/Tinebase/js/package.json ${TINE20ROOT}/tine20/Tinebase/js/package.json
COPY --from=source-copy ${TINE20ROOT}/tine20/Tinebase/js/npm-shrinkwrap.json ${TINE20ROOT}/tine20/Tinebase/js/npm-shrinkwrap.json
COPY --from=source-copy ${TINE20ROOT}/tine20/Tinebase/js/node_modules ${TINE20ROOT}/tine20/Tinebase/js/node_modules
COPY --from=source-copy ${TINE20ROOT}/tine20/Tinebase/js/Locale/static ${TINE20ROOT}/tine20/Tinebase/js/Locale/static

RUN cd ${TINE20ROOT}/tine20 && composer install --no-ansi --no-progress --no-suggest --no-scripts

RUN cd ${TINE20ROOT}/tine20/Tinebase/js && ${NPM_INSTALL_COMMAND}