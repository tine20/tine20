ARG BASE_IMAGE=base

#  -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -
FROM ${BASE_IMAGE} as cache-invalidator
ARG ALPINE_PHP_REPOSITORY_BRANCH=v3.12
ARG CACHE_BUST=0
RUN apk add --no-cache --simulate git npm | sha256sum >> /cachehash
RUN if [ ${ALPINE_PHP_PACKAGE} != "php8" ]; then \
        apk add --no-cache --simulate --repository http://nl.alpinelinux.org/alpine/${ALPINE_PHP_REPOSITORY_BRANCH}/community \
        composer | sha256sum >> /cachehash; \
    fi

#  -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -
FROM ${BASE_IMAGE} as dependency
ARG PUPPETEER_SKIP_CHROMIUM_DOWNLOAD=true
ARG NPM_INSTALL_COMMAND="npm --no-optional install"
ARG NODE_TLS_REJECT_UNAUTHORIZED=1
ARG TINE20ROOT=/usr/share
ARG ALPINE_PHP_REPOSITORY_BRANCH=v3.12
ARG ALPINE_PHP_PACKAGE=php7

COPY --from=cache-invalidator /cachehash /usr/local/lib/container/
RUN apk add --no-cache git npm

RUN if [ ${ALPINE_PHP_PACKAGE} == "php8" ]; then \
        php -r "copy('https://getcomposer.org/installer', '/composer-setup.php');"; \
        php -r "if (hash_file('sha384', '/composer-setup.php') === '756890a4488ce9024fc62c56153228907f1545c228516cbf63f885e036d37e9a59d27d63f46af1d4d07ee0f76181c7d3') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"; \
        php /composer-setup.php; \
        php -r "unlink('/composer-setup.php');"; \
        rm -f /usr/bin/composer; \
        ln -s /usr/share/composer.phar /usr/bin/composer; \
    else \
        apk add --no-cache --repository http://nl.alpinelinux.org/alpine/${ALPINE_PHP_REPOSITORY_BRANCH}/community composer; \
    fi

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
