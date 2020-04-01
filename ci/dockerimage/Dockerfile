ARG PHP_IMAGE=php
ARG PHP_IMAGE_TAG=7.3-fpm-alpine
ARG BASE_IMAGE=base
ARG SOURCE_IMAGE=source
ARG BUILD_IMAGE=build
ARG BUILT_IMAGE=built

FROM ${PHP_IMAGE}:${PHP_IMAGE_TAG} as base
ARG PHP_IMAGE_TAG=7.3-fpm-alpine

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
    then docker-php-ext-configure gd --with-gd --with-freetype-dir=/usr/include/ --with-png-dir=/usr/include/ --with-jpeg-dir=/usr/include/ && echo "with gd args"; \
    else docker-php-ext-configure gd && echo "with out gd args"; \
    fi && \
    docker-php-ext-install bcmath exif mysqli pcntl pdo_mysql soap sockets zip xsl intl gd opcache gettext iconv ldap && \
    docker-php-ext-enable ldap && \
    pecl install igbinary && echo -e "extension=igbinary.so\nigbinary.compact_strings=On" > /usr/local/etc/php/conf.d/php-ext-igbinary.ini && \
    echo "yes" | pecl install redis-4.3.0 && echo "extension=redis.so" > /usr/local/etc/php/conf.d/php-ext-redis.ini && \
    echo "" | pecl install yaml && echo "extension=yaml.so" > /usr/local/etc/php/conf.d/php-ext-yaml.ini && \
# remove php build dependencies
    apk del --purge .build-deps && \
    docker-php-source delete && \
#install.tmpl nodejs and npm
# create tine folders
    mkdir /tine && \
    mkdir /tine/conf.d && \
    mkdir /tine/logs && \
    mkdir /tine/files && \
    mkdir /tine/tmp && \
    mkdir /tine/caching && \
    mkdir /tine/sessions && \
    mkdir /etc/tine20 && \
    mkdir /var/run/tine20 && \
    chown nginx:nginx /tine/logs && \
    chown nginx:nginx /tine/files && \
    chown nginx:nginx /tine/caching && \
    chown nginx:nginx /tine/sessions && \
    chown nginx:nginx /tine/tmp

# start up script
COPY ci/dockerimage/install_tine.sh /install_tine.sh
COPY ci/dockerimage/wait_for_db.php /wait_for_db.php

# copy config templates to confd dir
RUN mkdir -p /etc/confd/conf.d && mkdir /etc/confd/templates
COPY ci/dockerimage/confd/conf.d/nginx.toml /etc/confd/conf.d/nginx.toml
COPY ci/dockerimage/confd/conf.d/nginx-vhost.toml /etc/confd/conf.d/nginx-vhost.toml
COPY ci/dockerimage/confd/conf.d/php.toml /etc/confd/conf.d/php.toml
COPY ci/dockerimage/confd/conf.d/php-fpm.toml /etc/confd/conf.d/php-fpm.toml
COPY ci/dockerimage/confd/conf.d/tine.toml /etc/confd/conf.d/tine.toml
COPY ci/dockerimage/confd/conf.d/tine-install.toml /etc/confd/conf.d/tine-install.toml
COPY ci/dockerimage/confd/templates/config.inc.php.tmpl /etc/confd/templates/config.inc.php.tmpl
COPY ci/dockerimage/confd/templates/install.tmpl /etc/confd/templates/install.tmpl
COPY ci/dockerimage/confd/templates/nginx.conf.tmpl /etc/confd/templates/nginx.conf.tmpl
COPY ci/dockerimage/confd/templates/nginx-vhost.conf.tmpl /etc/confd/templates/nginx-vhost.conf.tmpl
COPY ci/dockerimage/confd/templates/php.ini.tmpl /etc/confd/templates/php.ini.tmpl
COPY ci/dockerimage/confd/templates/php-fpm.conf.tmpl /etc/confd/templates/php-fpm.conf.tmpl

RUN mkdir -p /etc/supervisor.d/
COPY ci/dockerimage/supervisor.d/nginx.ini /etc/supervisor.d/nginx.ini
COPY ci/dockerimage/supervisor.d/php-fpm.ini /etc/supervisor.d/php-fpm.ini

# set workdir
WORKDIR /tine

# setup entrypoint
# + first runs confd to create configs
# + then start supervisor
COPY ci/dockerimage/entrypoint.sh /entrypoint.sh
CMD ["/entrypoint.sh"]

# checks if php-fpm an nginx are working
HEALTHCHECK --timeout=30s CMD curl --silent --fail http://127.0.0.1:80/ADMIN/fpm-ping

# ======================================================================================================================

FROM ${BASE_IMAGE} as source
ARG PUPPETEER_SKIP_CHROMIUM_DOWNLOAD=true
ARG NPM_INSTALL_COMMAND="npm --no-optional install"
ARG NODE_TLS_REJECT_UNAUTHORIZED=1

RUN apk add --no-cache composer git npm

RUN mkdir /tine/tine20
RUN mkdir -p /tine/tine20/Tinebase/js

COPY tine20/library /tine/tine20/library
COPY tine20/composer.json /tine/tine20/composer.json
COPY tine20/composer.lock /tine/tine20/composer.lock
COPY tine20/Tinebase/js/package.json /tine/tine20/Tinebase/js/package.json
COPY tine20/Tinebase/js/npm-shrinkwrap.json /tine/tine20/Tinebase/js/npm-shrinkwrap.json

# install tine dependencies
RUN cd /tine/tine20 && composer install --no-scripts --no-ansi --no-progress --no-suggest

RUN cd /tine/tine20/Tinebase/js && ${NPM_INSTALL_COMMAND}

# copy tine source
COPY .git /tine/.git
COPY tine20 /tine/tine20/
COPY tests /tine/tests/
COPY scripts /tine/scripts/

# run composer post install command, cant be run before becouse git is needed
RUN cd /tine/tine20 && composer run-script post-install-cmd

# ======================================================================================================================

FROM ${SOURCE_IMAGE} as test-source
ARG PUPPETEER_SKIP_CHROMIUM_DOWNLOAD=true

RUN apk add mysql-client

COPY ci/dockerimage/supervisor.d/webpack.ini /etc/supervisor.d/webpack.ini
COPY docs/config /config
COPY ci/dockerimage/install_mail.sh /install_mail.sh
COPY phpstan.neon /tine/phpstan.neon

ENV TINE20_INSTALL=true

# ======================================================================================================================

FROM ${SOURCE_IMAGE} as build

RUN cd /tine/tine20/ && vendor/bin/phing build
RUN cd /tine/tine20 && composer install --no-dev --no-ansi --no-progress --no-suggest

# ======================================================================================================================

FROM ${BUILD_IMAGE} as copy-build

# ======================================================================================================================

FROM ${BASE_IMAGE} as built
# copy tine source
COPY --from=copy-build /tine/tine20 /tine/tine20
COPY --from=copy-build /tine/scripts /tine/scripts

# start up script
COPY ci/dockerimage/supervisor.d/install_tine.ini /etc/supervisor.d/install_tine.ini
RUN chmod +x /install_tine.sh

# ======================================================================================================================

FROM ${BUILT_IMAGE} as test-built
ARG PUPPETEER_SKIP_CHROMIUM_DOWNLOAD=true
ARG NPM_INSTALL_COMMAND="npm --no-optional install"
ARG NODE_TLS_REJECT_UNAUTHORIZED=1

RUN apk add --no-cache composer git npm mysql-client

COPY docs/config/ /config
COPY ci/dockerimage/install_mail.sh /install_mail.sh

COPY --from=copy-build /tine/tests /tine/tests
COPY --from=copy-build /tine/.git /tine/.git

RUN cd /tine/tine20 && composer install --no-ansi --no-progress --no-suggest

RUN cd /tine/tine20/Tinebase/js && ${NPM_INSTALL_COMMAND}

ENV TINE20_INSTALL=true

# ======================================================================================================================

FROM ${BASE_IMAGE} as dev

# install.tmpl php build dependencies
RUN apk add --no-cache --virtual .build-deps autoconf gcc musl-dev make g++
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
