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
#   SOURCE_ICON_SET_PROVIDER=source-icon-set-provider can not be skipt, but can be run with prebuild image
#   TINE20ROOT=/usr/share
#   PUPPETEER_SKIP_CHROMIUM_DOWNLOAD=true
#   NPM_INSTALL_COMMAND="npm --no-optional install" - used set npm proxy in the ci
#   NODE_TLS_REJECT_UNAUTHORIZED=1 - needed to use the npm proxy
#
#   - indended for custom app testing
#   - adds one composer git repo and requires one composer packet if CUSTOM_APP_NAME ist set
#   CUSTOM_APP_VENDOR=metaways
#   CUSTOM_APP_NAME="" - e.g. tine20-exampleapp
#   CUSTOM_APP_GIT_URL="" - e.g. "https://gitlab.metaways.net/tine20/tine20-exampleapp.git"
#   CUSTOM_APP_VERSION=dev-master - e.g "dev-master#${CI_COMMIT_SHA}"

ARG BASE_IMAGE=base

#  -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -
FROM ${BASE_IMAGE} as cache-invalidator
RUN apk add --no-cache --simulate composer git npm | sha256sum >> /cachehash

#  -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -
# .git changes with every commit and broke cachin form source downwards. So it needs to be excluded and only the result is used
FROM ${BASE_IMAGE} as icon-set-provider
ARG TINE20ROOT=/usr/share
COPY .git ${TINE20ROOT}/.git
RUN apk add --no-cache git
RUN git submodule update --init

#  -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -
FROM ${BASE_IMAGE} as source
ARG PUPPETEER_SKIP_CHROMIUM_DOWNLOAD=true
ARG NPM_INSTALL_COMMAND="npm --no-optional install"
ARG NODE_TLS_REJECT_UNAUTHORIZED=1
ARG TINE20ROOT=/usr/share
ARG GERRIT_URL="gerrit.tine20.com"
ARG GERRIT_USER
ARG GERRIT_PASSWORD
ARG CUSTOM_APP_VENDOR=metaways
ARG CUSTOM_APP_NAME=""
ARG CUSTOM_APP_GIT_URL=""
ARG CUSTOM_APP_VERSION=dev-master

COPY --from=cache-invalidator /cachehash /usr/local/lib/container/
RUN apk add --no-cache composer git npm

# used to inject http auth credentials for git repos
COPY ci/dockerimage/utility/.gitconfig /root/.gitconfig

RUN mkdir -p ${TINE20ROOT}/tine20/Tinebase/js

COPY tine20/library ${TINE20ROOT}/tine20/library
COPY tine20/composer.json ${TINE20ROOT}/tine20/composer.json
COPY tine20/composer.lock ${TINE20ROOT}/tine20/composer.lock
COPY tine20/Tinebase/js/package.json ${TINE20ROOT}/tine20/Tinebase/js/package.json
COPY tine20/Tinebase/js/npm-shrinkwrap.json ${TINE20ROOT}/tine20/Tinebase/js/npm-shrinkwrap.json

RUN printf "machine ${GERRIT_URL}\nlogin ${GERRIT_USER}\npassword ${GERRIT_PASSWORD}\n" > /root/.netrc
RUN cd ${TINE20ROOT}/tine20 && composer install --no-scripts --no-ansi --no-progress --no-suggest
RUN cd ${TINE20ROOT}/tine20/Tinebase/js && ${NPM_INSTALL_COMMAND}

# first part ^^
# until here everything schould be cachable for a normal tine20 code change. Only if composer or npm packets are change
# the cache schould be rebuild. Becouse of this we need to copy composer.json and co first and can now overwrite them
# with the tine20 folder
# second part vv

COPY tine20 ${TINE20ROOT}/tine20/
COPY tests ${TINE20ROOT}/tests/
COPY scripts ${TINE20ROOT}/scripts/

# install custom app specified by env
RUN if test -n "${CUSTOM_APP_NAME}"; \
    then cd ${TINE20ROOT}/tine20; \
    apk add jq; \
    echo jq 'del(.repositories[]|select(.url=="https://gerrit.tine20.com/customers/p/'${CUSTOM_APP_NAME}'.git"))' $TINE20ROOT/tine20/composer.json '>' /tmp/replace-composer.json '&&' mv /tmp/replace-composer.json $TINE20ROOT/tine20/composer.json; \
    jq 'del(.repositories[]|select(.url=="https://gerrit.tine20.com/customers/p/'${CUSTOM_APP_NAME}'.git"))' $TINE20ROOT/tine20/composer.json > /tmp/replace-composer.json && mv /tmp/replace-composer.json $TINE20ROOT/tine20/composer.json; \
    echo composer config "repositories.${CUSTOM_APP_VENDOR}/${CUSTOM_APP_NAME}" git "${CUSTOM_APP_GIT_URL}"; \
    composer config "repositories.${CUSTOM_APP_VENDOR}/${CUSTOM_APP_NAME}" git "${CUSTOM_APP_GIT_URL}"; \
    echo composer require "${CUSTOM_APP_VENDOR}/${CUSTOM_APP_NAME}" "${CUSTOM_APP_VERSION}"; \
    composer require "${CUSTOM_APP_VENDOR}/${CUSTOM_APP_NAME}" "${CUSTOM_APP_VERSION}"; \
    fi

RUN cd ${TINE20ROOT}/tine20 && composer install --no-ansi --no-progress --no-suggest --no-scripts

COPY --from=icon-set-provider ${TINE20ROOT}/tine20/images/icon-set/ ${TINE20ROOT}/tine20/images/icon-set