ARG SOURCE_IMAGE_TAG
ARG PUPPETEER_SKIP_CHROMIUM_DOWNLOAD=true
ARG SOURCE_IMAGE=dependency


FROM ${SOURCE_IMAGE}:${SOURCE_IMAGE_TAG}

# copy tine source
COPY ./tine20/.git /tine/.git
COPY ./tine20/tine20/ /tine/tine20/
COPY ./tine20/tests/ /tine/tests/
COPY ./tine20/scripts/ /tine/scripts/

# install tine dependencies
RUN cd /tine/tine20 && composer install
RUN cd /tine/tine20/Tinebase/js && npm --no-optional install