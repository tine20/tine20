ARG PHP_IMAGE=php
ARG PHP_IMAGE_TAG=7.3-fpm-alpine
ARG BASE_IMAGE=base
ARG SOURCE_IMAGE=source
ARG BUILD_IMAGE=build
ARG BUILT_IMAGE=built
ARG DEPENDENCY_IMAGE=dependency

# scripts and config files still need to be change manualy
ARG TINE20ROOT=/usr/share

FROM ${PHP_IMAGE}:${PHP_IMAGE_TAG} as base
ARG PHP_IMAGE_TAG=7.3-fpm-alpine
ARG TINE20ROOT=/usr/share

# https://github.com/docker-library/php/issues/240
ENV LD_PRELOAD /usr/lib/preloadable_libiconv.so php
RUN apk add --no-cache --repository http://dl-3.alpinelinux.org/alpine/edge/community gnu-libiconv

# install.tmpl webserver and pcs
RUN apk --no-cache add nginx supervisor curl && \
    mkdir -p /etc/supervisor.d/ && \
    mkdir -p /run/nginx/ && \
# install.tmpl configuration managers working like a charm. Also the changing
# confd enabels configuration with env variables
# there for it creates configuration files from templates
    wget https://github.com/kelseyhightower/confd/releases/download/v0.16.0/confd-0.16.0-linux-amd64 && \
    mv ./confd-0.16.0-linux-amd64 /usr/sbin/confd && \
    chmod +x /usr/sbin/confd && \
    mkdir -p /etc/confd && \
# install.tmpl php build dependencies
    apk add --no-cache --virtual .build-deps autoconf gcc musl-dev make g++ && \
    docker-php-source extract && \
# install.tmpl php module dependencies
    apk add --no-cache libjpeg-turbo-dev libpng-dev icu-dev libxslt-dev zlib-dev libzip-dev yaml-dev freetype-dev gettext libintl gettext-dev bash openldap-dev && \
# install.tmpl php extentions
    if test "$PHP_IMAGE_TAG" == "7.3-fpm-alpine"; \
    then docker-php-ext-configure gd --with-gd --with-freetype-dir=/usr/include/ --with-png-dir=/usr/include/ --with-jpeg-dir=/usr/include/ && echo "configure gd with 7.3 args"; \
    else docker-php-ext-configure --with-freetype  --with-jpeg --enable-gd && echo "configure gd with args"; \
    fi && \
    docker-php-ext-install bcmath exif mysqli pcntl pdo_mysql soap sockets zip xsl intl gd opcache gettext iconv ldap && \
    docker-php-ext-enable ldap && \
    pecl install igbinary && echo -e "extension=igbinary.so\nigbinary.compact_strings=On" > /usr/local/etc/php/conf.d/php-ext-igbinary.ini && \
    echo "yes" | pecl install redis-4.3.0 && echo "extension=redis.so" > /usr/local/etc/php/conf.d/php-ext-redis.ini && \
    echo "" | pecl install yaml && echo "extension=yaml.so" > /usr/local/etc/php/conf.d/php-ext-yaml.ini && \
# install tika and ytnef
    apk add --no-cache ytnef openjdk8-jre && \
    wget -O /usr/local/bin/tika.jar http://packages.tine20.org/tika/tika-app-1.14.jar && \
# remove php build dependencies
    apk del --purge .build-deps && \
    docker-php-source delete

# create tine20 user and folders
RUN addgroup -S -g 150 tine20 && \
    adduser -S -H -D -s /bin/ash -g "tine20 user" -G tine20 -u 150 tine20 && \
    mkdir -p /etc/tine20/conf.d && \
    mkdir -p /var/log/tine20 && \
    mkdir -p /var/lib/tine20/files && \
    mkdir -p /var/lib/tine20/tmp && \
    mkdir -p /var/lib/tine20/caching && \
    mkdir -p /var/lib/tine20/sessions && \
    mkdir -p /var/run/tine20 && \
    mkdir -p ${TINE20ROOT}/tine20 && \
    chown tine20:tine20 /var/log/tine20 && \
    chown tine20:tine20 /var/lib/tine20/files && \
    chown tine20:tine20 /var/lib/tine20/caching && \
    chown tine20:tine20 /var/lib/tine20/sessions && \
    chown tine20:tine20 /var/lib/tine20/tmp && \
    chown tine20:tine20 /var/lib/nginx && \
    chown tine20:tine20 /var/lib/nginx/tmp

# copy config templates to confd dir
RUN mkdir -p /etc/confd/
COPY ci/dockerimage/confd/conf.d /etc/confd/conf.d
COPY ci/dockerimage/confd/templates/ /etc/confd/templates

RUN mkdir -p /etc/supervisor.d/
COPY ci/dockerimage/supervisor.d/conf.ini /etc/supervisor.d
COPY ci/dockerimage/supervisor.d/nginx.ini /etc/supervisor.d
COPY ci/dockerimage/supervisor.d/php-fpm.ini /etc/supervisor.d
COPY ci/dockerimage/supervisor.d/tail.ini /etc/supervisor.d

COPY ci/dockerimage/scripts/* /usr/local/bin

# set up logging
RUN touch /var/log/tine20/tine20.log && \
    chown tine20:tine20 /var/log/tine20/tine20.log

# set workdir
WORKDIR ${TINE20ROOT}
ENV TINE20ROOT=${TINE20ROOT}
CMD ["/usr/local/bin/entrypoint"]

# checks if php-fpm an nginx are working
HEALTHCHECK --timeout=30s CMD curl --silent --fail http://127.0.0.1:80/ADMIN/fpm-ping

# ======================================================================================================================
FROM ${BASE_IMAGE} as dependency
ARG PUPPETEER_SKIP_CHROMIUM_DOWNLOAD=true
ARG NPM_INSTALL_COMMAND="npm --no-optional install"
ARG NODE_TLS_REJECT_UNAUTHORIZED=1
ARG TINE20ROOT=/usr/share

RUN apk add --no-cache composer git npm

RUN mkdir -p ${TINE20ROOT}/tine20/Tinebase/js

COPY tine20/library ${TINE20ROOT}/tine20/library
COPY tine20/composer.json ${TINE20ROOT}/tine20/composer.json
COPY tine20/composer.lock ${TINE20ROOT}/tine20/composer.lock
COPY tine20/Tinebase/js/package.json ${TINE20ROOT}/tine20/Tinebase/js/package.json
COPY tine20/Tinebase/js/npm-shrinkwrap.json ${TINE20ROOT}/tine20/Tinebase/js/npm-shrinkwrap.json

COPY ci/dockerimage/.gitconfig /root/.gitconfig

# install tine dependencies
RUN cd ${TINE20ROOT}/tine20 && composer install --no-scripts --no-ansi --no-progress --no-suggest

RUN cd ${TINE20ROOT}/tine20/Tinebase/js && ${NPM_INSTALL_COMMAND}

# ======================================================================================================================
FROM ${DEPENDENCY_IMAGE} as source
ARG PUPPETEER_SKIP_CHROMIUM_DOWNLOAD=true
ARG NPM_INSTALL_COMMAND="npm --no-optional install"
ARG NODE_TLS_REJECT_UNAUTHORIZED=1
ARG TINE20ROOT=/usr/share
ARG CUSTOM_APP_VENDOR=metaways
ARG CUSTOM_APP_NAME=""
ARG CUSTOM_APP_GIT_URL=""
ARG CUSTOM_APP_VERSION=dev-master

# copy tine source
COPY .git ${TINE20ROOT}/.git
COPY tine20 ${TINE20ROOT}/tine20/
COPY tests ${TINE20ROOT}/tests/
COPY scripts ${TINE20ROOT}/scripts/
COPY ci/dockerimage/.gitconfig /root/.gitconfig

RUN if test -n "${CUSTOM_APP_NAME}"; \
    then cd ${TINE20ROOT}/tine20; \
    echo composer config "repositories.${CUSTOM_APP_VENDOR}-${CUSTOM_APP_NAME}" git "${CUSTOM_APP_GIT_URL}"; \
    composer config "repositories.${CUSTOM_APP_VENDOR}-${CUSTOM_APP_NAME}" git "${CUSTOM_APP_GIT_URL}"; \
    echo composer require "${CUSTOM_APP_VENDOR}/${CUSTOM_APP_NAME}" "${CUSTOM_APP_VERSION}"; \
    composer require "${CUSTOM_APP_VENDOR}/${CUSTOM_APP_NAME}" "${CUSTOM_APP_VERSION}"; \
    fi

RUN cd ${TINE20ROOT}/tine20 && composer install --no-ansi --no-progress --no-suggest

# ======================================================================================================================

FROM ${SOURCE_IMAGE} as test-source
ARG PUPPETEER_SKIP_CHROMIUM_DOWNLOAD=true
ARG TINE20ROOT=/usr/share

RUN apk add mysql-client

COPY ci/dockerimage/supervisor.d/webpack.ini /etc/supervisor.d/webpack.ini
COPY docs/config /config
COPY phpstan.neon ${TINE20ROOT}/phpstan.neon
COPY phpstan-baseline.neon ${TINE20ROOT}/phpstan-baseline.neon

# ======================================================================================================================

FROM ${SOURCE_IMAGE} as build
ARG TINE20ROOT=/usr/share

ARG RELEASE=local
ARG CODENAME=local
ARG REVISION=local

COPY ci/dockerimage/build/build_script.sh /build_script.sh

COPY ci/dockerimage/build/cacert20150305.pem ${TINE20ROOT}/tine20/Tinebase/License/cacert20150305.pem

RUN bash -c "source /build_script.sh && activateReleaseMode"
RUN bash -c "source /build_script.sh && buildLangStats"
RUN bash -c "source /build_script.sh && buildClient"
RUN bash -c "source /build_script.sh && removeComposerDevDependencies"
RUN bash -c "source /build_script.sh && cleanup"

# ======================================================================================================================

# fixes some bug: Forgot to document it in time, so i do not no witch one
FROM ${BUILD_IMAGE} as copy-build

FROM ${SOURCE_IMAGE} as copy-source
# ======================================================================================================================

FROM ${BASE_IMAGE} as built
ARG TINE20ROOT=/usr/share
# copy tine source
COPY --from=copy-build ${TINE20ROOT}/tine20 ${TINE20ROOT}/tine20

# ======================================================================================================================

FROM ${BUILT_IMAGE} as test-built
ARG PUPPETEER_SKIP_CHROMIUM_DOWNLOAD=true
ARG NPM_INSTALL_COMMAND="npm --no-optional install"
ARG NODE_TLS_REJECT_UNAUTHORIZED=1
ARG TINE20ROOT=/usr/share

RUN apk add --no-cache composer git npm mysql-client

COPY docs/config/ /config

COPY --from=copy-build ${TINE20ROOT}/.git ${TINE20ROOT}/.git
COPY --from=copy-build ${TINE20ROOT}/tests ${TINE20ROOT}/tests
COPY --from=copy-source ${TINE20ROOT}/tine20/vendor ${TINE20ROOT}/tine20/vendor
COPY --from=copy-source ${TINE20ROOT}/tine20/library/ExtJS/src/locale ${TINE20ROOT}/tine20/library/ExtJS/src/locale
COPY --from=copy-source ${TINE20ROOT}/tine20/composer.json ${TINE20ROOT}/tine20/composer.json
COPY --from=copy-source ${TINE20ROOT}/tine20/composer.lock ${TINE20ROOT}/tine20/composer.lock
COPY --from=copy-source ${TINE20ROOT}/tine20/Tinebase/js/package.json ${TINE20ROOT}/tine20/Tinebase/js/package.json
COPY --from=copy-source ${TINE20ROOT}/tine20/Tinebase/js/npm-shrinkwrap.json ${TINE20ROOT}/tine20/Tinebase/js/npm-shrinkwrap.json
COPY --from=copy-source ${TINE20ROOT}/tine20/Tinebase/js/node_modules ${TINE20ROOT}/tine20/Tinebase/js/node_modules
COPY --from=copy-source ${TINE20ROOT}/tine20/Tinebase/js/Locale/static ${TINE20ROOT}/tine20/Tinebase/js/Locale/static

RUN cd ${TINE20ROOT}/tine20 && composer install --no-ansi --no-progress --no-suggest

RUN cd ${TINE20ROOT}/tine20/Tinebase/js && ${NPM_INSTALL_COMMAND}

# ======================================================================================================================

FROM ${BASE_IMAGE} as dev

# install.tmpl php build dependencies
RUN apk add --no-cache --virtual .build-deps autoconf gcc musl-dev make g++
RUN apk add nodejs npm composer git
RUN docker-php-source extract

# xdebug
RUN pecl install xdebug
RUN docker-php-ext-enable xdebug
RUN echo -e "zend_extension=xdebug.so\nxdebug.default_enable=on\nxdebug.remote_enable=on\nxdebug.remote_handler=dbgp\nxdebug.remote_port=9001\nxdebug.remote_host=172.118.0.1\nxdebug.remote_autostart=on\nxdebug.idekey=tine20docker" >> /usr/local/etc/php/conf.d/xdebug.ini

# webpack
COPY ci/dockerimage/supervisor.d/webpack.ini /etc/supervisor.d/webpack.ini

# cleanup
RUN apk del --purge .build-deps
RUN docker-php-source delete
