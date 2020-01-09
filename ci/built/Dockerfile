# tine20-docker-registry.mws-hosting.net/docker/tine20/built
# configuration
# + metadata ./confd/conf.d/{tine.toml, tine-install.toml}
# + templates ./confd/templates/{confing.inc.php, install.tmpl}
# + required config keys:
#   + TINE20_DATABASE_HOST
#   + TINE20_DATABASE_DBNAME
#   + TINE20_DATABASE_USERNAME
#   + TINE20_DATABASE_PASSWORD
FROM tine20-docker-registry.mws-hosting.net/docker/tine20/build:latest as build

FROM tine20-docker-registry.mws-hosting.net/docker/tine20/base:latest as built
# copy tine source
COPY --from=build /tine/tine20 /tine/tine20
COPY --from=build /tine/scripts /tine/scripts

# start up script
COPY supervisor /etc/supervisor.d/install_tine.ini

RUN chmod +x /install_tine.sh