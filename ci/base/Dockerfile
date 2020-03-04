# tine20-docker-registry.mws-hosting.net/docker/tine20/base
# base docker file
# software:
# + php
# + php-fpm
# + nginx
# + supervisor -> to run multiple processes
# + confd -> for environment variable to config mapping
# + php modules: bcmath, Core, ctype, curl, date, dom, exif, fileinfo, filter, ftp, gd, hash, iconv, igbinary, intl, json, libxml, mbstring, mysqli, mysqlnd, openssl, pcntl, pcre, PDO, pdo_mysql, pdo_sqlite, Phar,
#                posix, readline, redis, Reflection, session, SimpleXML, soap, sockets, sodium, SPL, sqlite3, standard, tokenizer, xml, xmlreader, xmlwriter, xsl, yaml, Zend OPcache, zip, zlib
#
# configuration
# + metadata ./confd/conf.d
# + templates ./confd/templates
#
# running
# + nginx: port 80
# + nginx & php-fpm user: nginx
# + status: /ADMIN/nginx-status , /ADMIN/fpm-status
# + healthcheck: /ADMIN/fpm-ping
#   + container checks every 30s

# tag is replaced by the pipeline
ARG SOURCE_IMAGE_TAG=7.3-fpm-alpine
ARG SOURCE_IMAGE=php


FROM ${SOURCE_IMAGE}:${SOURCE_IMAGE_TAG}

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
    docker-php-ext-configure gd --with-gd --with-freetype-dir=/usr/include/ --with-png-dir=/usr/include/ --with-jpeg-dir=/usr/include/ && \
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
COPY install_tine.sh /install_tine.sh
COPY wait_for_db.php /wait_for_db.php

# copy config templates to confd dir
COPY confd /etc/confd
COPY supervisor/* /etc/supervisor.d/

# set workdir
WORKDIR /tine

# setup entrypoint
# + first runs confd to create configs
# + then start supervisor
COPY entrypoint.sh /entrypoint.sh
CMD ["/entrypoint.sh"]

# checks if php-fpm an nginx are working
HEALTHCHECK --timeout=30s CMD curl --silent --fail http://127.0.0.1:80/ADMIN/fpm-ping