ARG SOURCE_IMAGE_TAG
ARG PUPPETEER_SKIP_CHROMIUM_DOWNLOAD=true
ARG SOURCE_IMAGE=base


FROM ${SOURCE_IMAGE}:${SOURCE_IMAGE_TAG}

#install.tmpl tools
RUN apk add --no-cache composer git npm

RUN mkdir /tine/tine20
RUN mkdir -p /tine/tine20/Tinebase/js

COPY ./library /tine/tine20/library
COPY ./composer.json /tine/tine20/composer.json
COPY ./composer.lock /tine/tine20/composer.lock
COPY ./package.json /tine/tine20/Tinebase/js/package.json
COPY ./npm-shrinkwrap.json /tine/tine20/Tinebase/js/package.json

RUN cd /tine/tine20 && composer install --no-scripts
RUN cd /tine/tine20/Tinebase/js && npm --no-optional install