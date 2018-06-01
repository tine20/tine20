from ubuntu:16.04
MAINTAINER Nico Heßler <tine20@nico-hessler.de>

ENV DEBIAN_FRONTEND noninteractive

# CONFIGURATION VARIABLES
ARG TINE20_VERSION=2016.09.5
ARG TINE20_SERVER_NAME=localhost
ARG TINE20_SERVER_ALIAS=localhost
ARG TINE20_DB_HOST=mysql-server
ARG TINE20_DB_NAME=tine20
ARG TINE20_DB_USER
ARG TINE20_DB_PASS
ARG TINE20_SETUP_USER
ARG TINE20_SETUP_PASS

RUN apt-get update && apt-get install -y \
apache2 \
curl \
libapache2-mod-php \
php-mbstring \
php-mysql \
php-mcrypt \
php-gd \
php-intl \
php-json \
php-cli \
php-xml \
php-zip \
unzip \
&& apt-get clean

HEALTHCHECK --interval=1m --timeout=3s --retries=3 CMD curl -f http://localhost || exit 1

RUN mkdir -p /opt/tine20 && curl http://packages.tine20.org/source/$TINE20_VERSION/tine20-allinone_$TINE20_VERSION.zip -o tine20.zip && unzip tine20.zip -d /opt/tine20 && rm tine20.zip

COPY apache.conf /etc/apache2/sites-available/tine20.conf
RUN rm /etc/apache2/sites-enabled/000-default.conf && a2ensite tine20 
COPY tine20.conf /opt/tine20/config.inc.php
RUN chown -R www-data:www-data /opt/tine20/
RUN mkdir -p /var/lib/tine20/cache /var/lib/tine20/files /var/lib/tine20/sessions /var/lib/tine20/tmp && chown -R www-data:www-data /var/lib/tine20

COPY ./docker-entrypoint.sh /usr/local/bin/
ENTRYPOINT ["docker-entrypoint.sh"]

CMD ["/usr/sbin/apache2ctl","-DFOREGROUND"]

EXPOSE 80
