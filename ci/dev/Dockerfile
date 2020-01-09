# tine20-docker-registry.mws-hosting.net/docker/tine20/dev
# tine container for dev setup
# software:
# + xdebug
# + npm
# + composer

FROM tine20-docker-registry.mws-hosting.net/docker/tine20/base:2476

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
