# description:
#   Image for dev docker setup. Baseimage with xdegug, npm, webpack and composer.
#
# build:
#   $ docker build [...] --build-arg='BASE_IMAGE=base-tag'
#
# ARGS:
#   BASE_IMAGE=base
#   ALPINE_PHP_REPOSITORY_VERSION=v3.12 from which alpine versions repository php should be installed
ARG BASE_IMAGE=base

#  -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -
# using alpine:3.12 as source here could be a problem, if a packet is not updated duto a version conflict
FROM ${BASE_IMAGE} as cache-invalidator
ARG ALPINE_PHP_REPOSITORY_VERSION=v3.12

RUN apk add --no-cache --simulate nodejs npm composer git | sha256sum >> /cachehash
RUN apk add --no-cache --simulate --repository http://nl.alpinelinux.org/alpine/${ALPINE_PHP_REPOSITORY_VERSION}/main php7-pecl-xdebug \
                                  | sha256sum >> /cachehash

#  -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -
FROM ${BASE_IMAGE} as dev
ARG ALPINE_PHP_REPOSITORY_VERSION=v3.12

COPY --from=cache-invalidator /cachehash /usr/local/lib/container/
RUN apk add --no-cache nodejs npm composer git
RUN apk add --no-cache --repository http://nl.alpinelinux.org/alpine/${ALPINE_PHP_REPOSITORY_VERSION}/main php7-pecl-xdebug