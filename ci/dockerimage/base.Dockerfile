# description:
#   The base image should contain everything that is needed for tine20. But dose not depend on the tine20 repo.
#
# build:
#   $ docker build [...] .
#
# ARGS:
#   TINE20ROOT=/usr/share

#  -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -
FROM alpine:3.12 as cache-invalidator
RUN apk add --no-cache --simulate supervisor curl bash ytnef openjdk8-jre php7 php7-fpm php7-bcmath php7-exif \
                                  php7-mysqli php7-pcntl php7-pdo_mysql php7-soap php7-sockets php7-zip php7-xsl \
                                  php7-intl php7-gd php7-opcache php7-gettext php7-iconv php7-ldap php7-pecl-igbinary \
                                  php7-pecl-yaml php7-simplexml php7-ctype php7-xml php7-xmlreader php7-curl \
                                  php7-tokenizer php7-xmlwriter php7-fileinfo gettext | sha256sum >> /cachehash
RUN apk add --no-cache --simulate --repository http://dl-cdn.alpinelinux.org/alpine/v3.10/community \
                                  php7-pecl-redis=4.3.0-r2 | sha256sum >> /cachehash
RUN apk add --no-cache --simulate --repository http://dl-3.alpinelinux.org/alpine/edge/testing gnu-libiconv \
                                  | sha256sum >> /cachehash
RUN apk add --no-cache --simulate --repository http://nl.alpinelinux.org/alpine/edge/main nginx nginx-mod-http-brotli \
                                  | sha256sum >> /cachehash

#  -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -
FROM alpine:3.12 as base
ARG TINE20ROOT=/usr/share

#todo version vars | move tika to lib
RUN wget -O /usr/sbin/confd https://github.com/kelseyhightower/confd/releases/download/v0.16.0/confd-0.16.0-linux-amd64 \
    && chmod +x /usr/sbin/confd
RUN wget -O /usr/local/bin/tika.jar http://packages.tine20.org/tika/tika-app-1.14.jar

# todo check if copy or add craetes folder
RUN mkdir /usr/local/lib/container

COPY --from=cache-invalidator /cachehash /usr/local/lib/container/
RUN apk add --no-cache supervisor curl bash ytnef openjdk8-jre php7 php7-fpm php7-bcmath php7-exif php7-mysqli \
                       php7-pcntl php7-pdo_mysql php7-soap php7-sockets php7-zip php7-xsl php7-intl php7-gd \
                       php7-opcache php7-gettext php7-iconv php7-ldap php7-pecl-igbinary php7-pecl-yaml php7-simplexml \
                       php7-ctype php7-xml php7-xmlreader php7-curl php7-tokenizer php7-xmlwriter php7-fileinfo gettext
# todo check if the new redis version 5.2.2 (alpine v3.12) also works
RUN apk add --no-cache --repository http://dl-cdn.alpinelinux.org/alpine/v3.10/community --no-cache php7-pecl-redis=4.3.0-r2

RUN apk add --no-cache --repository http://nl.alpinelinux.org/alpine/edge/main nginx nginx-mod-http-brotli

# fix alpine iconv problem e.g. could not locate filter
RUN apk add --no-cache --repository http://dl-3.alpinelinux.org/alpine/edge/testing gnu-libiconv
ENV LD_PRELOAD /usr/lib/preloadable_libiconv.so php

RUN addgroup -S -g 150 tine20 && \
    adduser -S -H -D -s /bin/ash -g "tine20 user" -G tine20 -u 150 tine20 && \
    mkdir -p /etc/tine20/conf.d && \
    mkdir -p /etc/confd && \
    mkdir -p /etc/supervisor.d && \
    mkdir -p /var/log/tine20 && \
    mkdir -p /var/lib/tine20/files && \
    mkdir -p /var/lib/tine20/tmp && \
    mkdir -p /var/lib/tine20/caching && \
    mkdir -p /var/lib/tine20/sessions && \
    mkdir -p /var/run/tine20 && \
    mkdir -p /run/nginx && \
    mkdir -p ${TINE20ROOT}/tine20 && \
    touch /var/log/tine20/tine20.log && \
    chown tine20:tine20 /var/log/tine20 && \
    chown tine20:tine20 /var/lib/tine20/files && \
    chown tine20:tine20 /var/lib/tine20/caching && \
    chown tine20:tine20 /var/lib/tine20/sessions && \
    chown tine20:tine20 /var/lib/tine20/tmp && \
    chown tine20:tine20 /var/lib/nginx && \
    chown tine20:tine20 /var/lib/nginx/tmp && \
    chown tine20:tine20 /var/log/tine20/tine20.log

COPY ci/dockerimage/confd/conf.d /etc/confd/conf.d
COPY ci/dockerimage/confd/templates/ /etc/confd/templates
COPY ci/dockerimage/supervisor.d/conf.ini /etc/supervisor.d/
COPY ci/dockerimage/supervisor.d/nginx.ini /etc/supervisor.d/
COPY ci/dockerimage/supervisor.d/php-fpm.ini /etc/supervisor.d/
COPY ci/dockerimage/supervisor.d/tail.ini /etc/supervisor.d/
COPY ci/dockerimage/supervisor.d/crond.ini /etc/supervisor.d/
COPY ci/dockerimage/scripts/* /usr/local/bin/

WORKDIR ${TINE20ROOT}
ENV TINE20ROOT=${TINE20ROOT}
CMD ["/usr/local/bin/entrypoint"]
HEALTHCHECK --timeout=30s CMD curl --silent --fail http://127.0.0.1:80/ADMIN/fpm-ping