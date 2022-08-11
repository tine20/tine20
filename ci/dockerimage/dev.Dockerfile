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

RUN apk add --update --no-cache --simulate git composer build-base ${ALPINE_PHP_PACKAGE}-pecl-xdebug | sha256sum >> /cachehash
RUN apk add --no-cache --simulate --repository http://dl-cdn.alpinelinux.org/alpine/v3.12/main/ npm=12.22.12-r0  nodejs=12.22.12-r0 | sha256sum >> /cachehash

#  -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -
FROM ${BASE_IMAGE} as dev
ARG ALPINE_PHP_PACKAGE=php7

COPY --from=cache-invalidator /cachehash /usr/local/lib/container/

RUN apk add --update --no-cache git composer build-base ${ALPINE_PHP_PACKAGE}-pecl-xdebug
RUN apk add --no-cache --repository http://dl-cdn.alpinelinux.org/alpine/v3.12/main/ npm=12.22.12-r0  nodejs=12.22.12-r0
