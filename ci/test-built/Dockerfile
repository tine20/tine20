# tine20-docker-registry.mws-hosting.net/docker/tine20/test-build
FROM tine20-docker-registry.mws-hosting.net/docker/tine20/build:latest as build

FROM tine20-docker-registry.mws-hosting.net/docker/tine20/built:latest as test

COPY --from=build /tine/tests /tine/tests
COPY --from=build /tine/.git /tine/.git

COPY supervisor/* /etc/supervisor.d/

#install.tmpl tools
RUN apk add --no-cache composer git npm

RUN cd /tine/tine20 && composer install
RUN cd /tine/tine20/Tinebase/js && npm install

ENV TINE20_INSTALL=true