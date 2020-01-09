ARG SOURCE_IMAGE_TAG_BUILD
ARG SOURCE_IMAGE_TAG_BUILT
ARG PUPPETEER_SKIP_CHROMIUM_DOWNLOAD=true
ARG SOURCE_IMAGE_BUILD=build
ARG SOURCE_IMAGE_BUILT=built

FROM ${SOURCE_IMAGE_BUILD}:${SOURCE_IMAGE_TAG_BUILD} as build

FROM ${SOURCE_IMAGE_BUILT}:${SOURCE_IMAGE_TAG_BUILT} as test

COPY --from=build /tine/tests /tine/tests
COPY --from=build /tine/.git /tine/.git

COPY supervisor/* /etc/supervisor.d/
COPY config/ /config
COPY install_mail.sh /install_mail.sh

#install.tmpl tools
RUN apk add --no-cache composer git npm mysql-client

RUN cd /tine/tine20 && composer install

RUN cd /tine/tine20/Tinebase/js && npm --no-optional install


ENV TINE20_INSTALL=true