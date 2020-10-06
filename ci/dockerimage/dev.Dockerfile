# description:
#   Image for dev docker setup. Baseimage with xdegug, npm, webpack and composer.
#
# build:
#   $ docker build [...] --build-arg='BASE_IMAGE=base-tag'
#
# ARGS:
#   BASE_IMAGE=base
ARG BASE_IMAGE=base

#  -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -
# using alpine:3.12 as source here could be a problem, if a packet is not updated duto a version conflict
FROM ${BASE_IMAGE} as cache-invalidator
RUN apk add --no-cache --simulate nodejs npm composer git php7-pecl-xdebug | sha256sum >> /cachehash

#  -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -
FROM ${BASE_IMAGE} as dev

COPY --from=cache-invalidator /cachehash /usr/local/lib/container/
RUN apk add --no-cache nodejs npm composer git php7-pecl-xdebug