ARG BASE_IMAGE=base

#  -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -
FROM ${BASE_IMAGE} as cache-invalidator
ARG CACHE_BUST=0
RUN apk add --update --no-cache --simulate git composer build-base | sha256sum >> /cachehash
RUN apk add --no-cache --simulate --repository http://dl-cdn.alpinelinux.org/alpine/v3.12/main/ npm=12.22.12-r0 nodejs=12.22.12-r0 | sha256sum >> /cachehash

#  -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -
FROM ${BASE_IMAGE} as dependency
ARG NPM_INSTALL_COMMAND="npm --no-optional install"
ARG TINE20ROOT=/usr/share

COPY --from=cache-invalidator /cachehash /usr/local/lib/container/
RUN apk add --update --no-cache git composer build-base
RUN apk add --no-cache --repository http://dl-cdn.alpinelinux.org/alpine/v3.12/main/ npm=12.22.12-r0 nodejs=12.22.12-r0

# used to inject http auth credentials for git repos
COPY ci/dockerimage/utility/.gitconfig /root/.gitconfig

RUN mkdir -p ${TINE20ROOT}/tine20/Tinebase/js
RUN mkdir ${TINE20ROOT}/scripts

COPY tine20/library ${TINE20ROOT}/tine20/library
COPY tine20/composer.json ${TINE20ROOT}/tine20/composer.json
COPY tine20/composer.lock ${TINE20ROOT}/tine20/composer.lock
COPY tine20/Tinebase/js/package.json ${TINE20ROOT}/tine20/Tinebase/js/package.json
COPY tine20/Tinebase/js/npm-shrinkwrap.json ${TINE20ROOT}/tine20/Tinebase/js/npm-shrinkwrap.json
COPY scripts/packaging/composer/composerLockRewrite.php ${TINE20ROOT}/scripts/packaging/composer/composerLockRewrite.php 


RUN if [ "COMPOSER_LOCK_REWRITE" == "true" ]; then \
        php ${TINE20ROOT}/scripts/packaging/composer/composerLockRewrite.php ${TINE20ROOT}/tine20/composer.lock satis.default.svc.cluster.local; \
    fi
RUN cd ${TINE20ROOT}/tine20 && composer install --no-scripts --no-ansi --no-progress --no-suggest
RUN cd ${TINE20ROOT}/tine20/Tinebase/js && ${NPM_INSTALL_COMMAND}
