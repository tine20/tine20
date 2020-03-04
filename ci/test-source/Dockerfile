ARG SOURCE_IMAGE_TAG
ARG PUPPETEER_SKIP_CHROMIUM_DOWNLOAD=true
ARG SOURCE_IMAGE=source


FROM ${SOURCE_IMAGE}:${SOURCE_IMAGE_TAG}

COPY supervisor/* /etc/supervisor.d/
COPY config/ /config
COPY install_mail.sh /install_mail.sh

RUN apk add mysql-client

#install dependencyies
RUN cd /tine/tine20 && composer install
RUN cd /tine/tine20/Tinebase/js && npm --no-optional install


ENV TINE20_INSTALL=true