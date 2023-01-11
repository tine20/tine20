ARG BUILT_IMAGE=built

FROM ${BUILT_IMAGE} as packaging
ARG TINE20PACKAGES=/root/packages
ARG RELEASE=local
ARG CODENAME=local
ARG REVISION=local
ARG ZIP_PACKAGES="false"

RUN apk add zip

COPY ci/dockerimage/build/build_script.sh /build_script.sh

RUN bash -c "source /build_script.sh && createArchives"
RUN bash -c "source /build_script.sh && createSpecialArchives"
RUN bash -c "source /build_script.sh && packageTranslations"
RUN bash -c "source /build_script.sh && buildChecksum"
