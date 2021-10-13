# description:
#   This image is used to run tests in the ci pipeline.
#
# build:
#   $ docker build [...] --build-arg='SOURCE_IMAGE=source-tag' .
#
# ARGS:
#   SOURCE_IMAGE=source
#   TINE20ROOT=/usr/share
#   PUPPETEER_SKIP_CHROMIUM_DOWNLOAD=true - is this still needed here?

ARG DEPENDENCY_IMAGE=dependency

#  -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -
FROM ${DEPENDENCY_IMAGE} as test-dependency
ARG PUPPETEER_SKIP_CHROMIUM_DOWNLOAD=true
ARG TINE20ROOT=/usr/share

RUN apk add mysql-client jq rsync

COPY ci/dockerimage/supervisor.d/webpack.ini /etc/supervisor.d/webpack.ini
COPY etc /config
COPY phpstan.neon ${TINE20ROOT}/phpstan.neon
COPY phpstan-baseline.neon ${TINE20ROOT}/phpstan-baseline.neon
