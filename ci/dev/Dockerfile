ARG SOURCE_IMAGE_TAG
ARG SOURCE_IMAGE=base


FROM ${SOURCE_IMAGE}:${SOURCE_IMAGE_TAG}

# install.tmpl php build dependencies
RUN apk add --no-cache --virtual .build-deps autoconf gcc musl-dev make g++
RUN docker-php-source extract

# xdebug
RUN pecl install xdebug
RUN docker-php-ext-enable xdebug
RUN echo -e "zend_extension=xdebug.so\nxdebug.default_enable=on\nxdebug.remote_enable=on\nxdebug.remote_handler=dbgp\nxdebug.remote_port=9001\nxdebug.remote_host=172.118.0.1\nxdebug.remote_autostart=on\nxdebug.idekey=tine20docker" >> /usr/local/etc/php/conf.d/xdebug.ini

# webpack
COPY supervisor* /etc/supervisor.d/*

# cleanup
RUN apk del --purge .build-deps
RUN docker-php-source delete
