# description:
#
#   First stage of the tine20 built image. It is splite in to files, to be able to use cashing in the ci
#   This stage builds tine20. And cleans all unnessery files from ${TINE20ROOT}/tine20.
#
# build:
#   $ docker build [...] --build-arg='SOURCE_IMAGE=source-tag' .
#
# ARGS:
#   SOURCE_IMAGE=source
#   TINE20ROOT=/usr/share
#   todo comment vars
#   RELEASE=local -
#   CODENAME=local -
#   REVISION=local -

ARG SOURCE_IMAGE=source

#  -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -
FROM ${SOURCE_IMAGE} as build
ARG TINE20ROOT=/usr/share
ARG RELEASE=local
ARG CODENAME=local
ARG REVISION=local

COPY ci/dockerimage/build/build_script.sh /build_script.sh

COPY ci/dockerimage/build/cacert20150305.pem ${TINE20ROOT}/tine20/Tinebase/License/cacert20150305.pem

RUN bash -c "source /build_script.sh && activateReleaseMode"
RUN bash -c "source /build_script.sh && buildLangStats"
RUN bash -c "source /build_script.sh && buildClient"
RUN bash -c "source /build_script.sh && removeComposerDevDependencies"
RUN bash -c "source /build_script.sh && cleanup"