# description:
#   The fist part of the source image installs all dependencies specified in the tine20 repo. But it should not change,
#   if only the tine20 code changes. E.g. when the composer.lock or npm-shrinkwrap.json changes this image part can change.
#
#   This second part adds the tine20 source. And executes dependency scripts, which need the tine20 source.
#
# build:
#   $ docker build [...] --build-arg='BASE_IMAGE=base-tag' .
#
# ARGS:
#   BASE_IMAGE=base
#   TINE20ROOT=/usr/share
#   NPM_INSTALL_COMMAND="npm --no-optional install" - used set npm proxy in the ci

ARG BASE_IMAGE=base

#  -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -
FROM ${BASE_IMAGE} as cache-invalidator
ARG CACHE_BUST=0
RUN apk add --no-cache --simulate git npm composer build-base | sha256sum >> /cachehash

#  -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -
# .git changes with every commit and broke cachin form source downwards. So it needs to be excluded and only the result is used
FROM ${BASE_IMAGE} as icon-set-provider
ARG TINE20ROOT=/usr/share
COPY .git ${TINE20ROOT}/.git
RUN apk add --no-cache git
RUN git submodule update --init

#  -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -
FROM ${BASE_IMAGE} as source
ARG NPM_INSTALL_COMMAND="npm --no-optional install"
ARG TINE20ROOT=/usr/share

COPY --from=cache-invalidator /cachehash /usr/local/lib/container/
RUN apk add --no-cache git npm composer build-base

# used to inject http auth credentials for git repos
COPY ci/dockerimage/utility/.gitconfig /root/.gitconfig

COPY tine20 ${TINE20ROOT}/tine20/
COPY tests ${TINE20ROOT}/tests/
COPY scripts ${TINE20ROOT}/scripts/

RUN if [ "COMPOSER_LOCK_REWRITE" == "true" ]; then \
        php ${TINE20ROOT}/scripts/packaging/composer/composerLockRewrite.php ${TINE20ROOT}/tine20/composer.lock satis.default.svc.cluster.local; \
    fi
RUN cd ${TINE20ROOT}/tine20 && composer install --no-ansi --no-progress --no-suggest --no-scripts
RUN cd ${TINE20ROOT}/tine20/Tinebase/js && ${NPM_INSTALL_COMMAND}

COPY --from=icon-set-provider ${TINE20ROOT}/tine20/images/icon-set/ ${TINE20ROOT}/tine20/images/icon-set
