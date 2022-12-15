# description:
#   This image is used to run tests in the ci pipeline.
#
# build:
#   $ docker build [...] --build-arg='SOURCE_IMAGE=source-tag' .
#
# ARGS:
#   SOURCE_IMAGE=source
#   TINE20ROOT=/usr/share

ARG SOURCE_IMAGE=source

#  -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -
FROM ${SOURCE_IMAGE} as test-source
ARG TINE20ROOT=/usr/share

RUN apk add mysql-client jq rsync coreutils

COPY ci/dockerimage/supervisor.d/webpack.ini /etc/supervisor.d/webpack.ini
COPY etc /config
COPY phpstan.neon ${TINE20ROOT}/phpstan.neon
COPY phpstan-baseline.neon ${TINE20ROOT}/phpstan-baseline.neon
