ARG SOURCE_IMAGE_TAG_BUILD
ARG SOURCE_IMAGE_TAG_BASE
ARG SOURCE_IMAGE_BUILD=build
ARG SOURCE_IMAGE_BASE=base


FROM ${SOURCE_IMAGE_BUILD}:${SOURCE_IMAGE_TAG_BUILD} as build

FROM ${SOURCE_IMAGE_BASE}:${SOURCE_IMAGE_TAG_BASE} as built
# copy tine source
COPY --from=build /tine/tine20 /tine/tine20
COPY --from=build /tine/scripts /tine/scripts

# start up script
COPY supervisor /etc/supervisor.d

RUN chmod +x /install_tine.sh