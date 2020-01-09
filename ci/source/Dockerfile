# tine20-docker-registry.mws-hosting.net/docker/tine20/source
FROM tine20-docker-registry.mws-hosting.net/docker/tine20/dependency:latest

# copy tine source
COPY ./tine20/.git /tine/.git
COPY ./tine20/tine20/ /tine/tine20/
COPY ./tine20/tests/ /tine/tests/
COPY ./tine20/scripts/ /tine/scripts/

# install tine dependencies
RUN cd /tine/tine20 && composer install
RUN cd /tine/tine20/Tinebase/js && npm install
