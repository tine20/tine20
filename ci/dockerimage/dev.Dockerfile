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

RUN apk add --no-cache --simulate nodejs npm git composer build-base ${ALPINE_PHP_PACKAGE}-pecl-xdebug | sha256sum >> /cachehash

#  -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -
FROM ${BASE_IMAGE} as dev
ARG ALPINE_PHP_PACKAGE=php7

COPY --from=cache-invalidator /cachehash /usr/local/lib/container/

RUN apk add --no-cache nodejs npm git composer build-base ${ALPINE_PHP_PACKAGE}-pecl-xdebug
