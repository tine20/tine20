# description:
#   Image for dev docker setup. Baseimage with xdegug, npm, webpack and composer.
#
# build:
#   $ docker build [...] --build-arg='BASE_IMAGE=base-tag'
#
# ARGS:
#   BASE_IMAGE=base
#   ALPINE_PHP_PACKAGE=php7
ARG BASE_IMAGE=base

#  -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -
FROM ${BASE_IMAGE} as cache-invalidator
ARG ALPINE_PHP_PACKAGE=php7
ARG CACHE_BUST=0

RUN apk add --update --no-cache --simulate git build-base ${ALPINE_PHP_PACKAGE}-pecl-xdebug unzip | sha256sum >> /cachehash
RUN if [ "${ALPINE_PHP_PACKAGE}" != "php81" ]; then \
      apk add --no-cache --simulate composer | sha256sum >> /cachehash; \
    fi
RUN apk add --no-cache --simulate --repository http://dl-cdn.alpinelinux.org/alpine/v3.12/main/ npm=12.22.12-r0  nodejs=12.22.12-r0 | sha256sum >> /cachehash

#  -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -
FROM ${BASE_IMAGE} as dev
ARG ALPINE_PHP_PACKAGE=php7

COPY --from=cache-invalidator /cachehash /usr/local/lib/container/

RUN apk add --update --no-cache git build-base ${ALPINE_PHP_PACKAGE}-pecl-xdebug unzip
RUN if [ "${ALPINE_PHP_PACKAGE}" == "php81" ]; then \
        EXPECTED_CHECKSUM="$(php -r 'copy("https://composer.github.io/installer.sig", "php://stdout");')"; \
        php -r "copy('https://getcomposer.org/installer', '/composer-setup.php');"; \
        ACTUAL_CHECKSUM="$(php -r "echo hash_file('sha384', '/composer-setup.php');")"; \
        if [ "$EXPECTED_CHECKSUM" != "$ACTUAL_CHECKSUM" ]; then \
            >&2 echo 'ERROR: Invalid installer checksum'; \
            rm /composer-setup.php; \
            exit 1; \
        fi; \
        php /composer-setup.php --install-dir=/usr/bin --filename=composer; \
        RESULT=$?; \
        rm /composer-setup.php; \
        exit $RESULT; \
    else \
      apk add --no-cache composer; \
    fi
RUN apk add --no-cache --repository http://dl-cdn.alpinelinux.org/alpine/v3.12/main/ npm=12.22.12-r0  nodejs=12.22.12-r0
