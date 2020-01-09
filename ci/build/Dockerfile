ARG SOURCE_IMAGE_TAG
ARG SOURCE_IMAGE=source


FROM ${SOURCE_IMAGE}:${SOURCE_IMAGE_TAG}

# build tine
RUN cd /tine/tine20/ && vendor/bin/phing build