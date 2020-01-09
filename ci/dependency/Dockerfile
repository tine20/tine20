# tine20-docker-registry.mws-hosting.net/docker/tine20/dependency
FROM tine20-docker-registry.mws-hosting.net/docker/tine20/base:latest

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
RUN cd /tine/tine20/Tinebase/js && npm install