# tine20-docker-registry.mws-hosting.net/docker/tine20/test-source
FROM tine20-docker-registry.mws-hosting.net/docker/tine20/source:latest

COPY supervisor/* /etc/supervisor.d/

#install dependencyies
RUN cd /tine/tine20 && composer install
RUN cd /tine/tine20/Tinebase/js && npm install

ENV TINE20_INSTALL=true