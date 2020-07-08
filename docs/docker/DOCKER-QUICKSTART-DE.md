Tine 2.0 Business Edition Docker Image
---
[www.tine20.com](https://www.tine20.com/) | [GitHub](https://github.com/tine20/tine20) | [Dockerfile](https://github.com/tine20/tine20/blob/main/ci/dockerimage/Dockerfile)

##### Versions
| Tag | Status | Versions |
|---|---|---|
| *2020.11-7.3-alpine* | beta | 2020.11, php 7.3, alpine
| __2019.11-7.3-alpine__ | stable | 2019.11, php 7.3, alpine

### Schnellstart

Dies ist eine schnelle und leichte Möglichkeit, Tine 2.0 auszuprobieren. Hierfür benötigen Sie Docker und Docker Compose (https://docs.docker.com/compose/).

Erstellen Sie im ersten Schritt einen Ordner. Docker Compose verwendet die Ordnernamen zur Identifizierung.

```
mkdir tine20
cd tine20
```
Als zweiten Schritt laden Sie die aktuelle Datei docker-compose.yaml herunter und speichern diese in dem soeben erstellten Ordner.

```
wget http://packages.tine20.com/maintenance/docker/current/quickstart/docker-compose.yaml
```

Als nächstes müssen Sie die Tine 2.0-Lizenz akzeptieren. Dies kann durch Setzen der Umgebungsvariablen TINE20_ACCEPTED_TERMS_VERSION auf die aktuelle oder auch eine neuere Version erfolgen (z. B. 1000). Das wiederum kann in der ENV-Datei oder im docker-compose.yaml eingestellt werden.

```
echo "TINE20_ACCEPTED_TERMS_VERSION=1000" > .env
```
Jetzt können Sie Docker-Compose starten.
```
docker-compose up
```

Warten Sie einen Moment, bis sich der Webcontainer anmeldet  `web_1    | 2027-11-23 19:59:07,137 INFO success: nginx entered RUNNING state, process has stayed up for > than 1 seconds (startsecs)`
Tine 2.0 ist jetzt unter http://127.0.0.1:4000 erreichbar.

##### Aufräumen
Um alle von Docker Compose erstellten Container, Netzwerke und Volumes zu stoppen und löschen nutzen Sie:
```
docker-compose down --volumes
```

#### compose
```
version: '2'
services:
  db:
    image: mariadb:10.4.1
    environment:
      MYSQL_ROOT_PASSWORD: root
      MYSQL_DATABASE: &MYSQL_DATABASE tine20db
      MYSQL_USER: &MYSQL_USER tine20
      MYSQL_PASSWORD: &MYSQL_PASSWORD tine20
    networks:
      - internal_network

  cache:
    image: redis:5.0.5
    networks:
      - internal_network

  web:
    image: tine20/tine20:2019.11-7.3-fpm-alpine
    depends_on:
      - db
      - cache
    environment:
      TINE20_DATABASE_HOST: db
      TINE20_DATABASE_DBNAME: *MYSQL_DATABASE
      TINE20_DATABASE_USERNAME: *MYSQL_USER
      TINE20_DATABASE_PASSWORD: *MYSQL_PASSWORD
      TINE20_SETUPUSER_USERNAME: tine20setup
      TINE20_SETUPUSER_PASSWORD: tine20setup
      TINE20_LOGIN_USERNAME: tine20admin
      TINE20_LOGIN_PASSWORD: tine20admin
      TINE20_ADMIN_EMAIL: tine20admin@mail.invalid
      TINE20_CACHING_BACKEND: Redis
      TINE20_CACHING_REDIS_HOST: cache
      TINE20_SESSION_BACKEND: Redis
      TINE20_SESSION_HOST: cache
      TINE20_CREDENTIALCACHESHAREDKEY: change_me
      TINE20_ACCEPTED_TERMS_VERSION: ${TINE20_ACCEPTED_TERMS_VERSION}
    networks:
      - external_network
      - internal_network
    ports:
      - "127.0.0.1:4000:80"

networks:
  external_network:
  internal_network:
    internal: true
```
### Image
Dieses Image enthält den Tine 2.0-Code, PHP-FPM und Nginx. Zusätzlich benötigen Sie eine Datenbank, beispielsweise MariaDB. In der Produktion sollte dieses Image mit einem Reverse-Proxy verwendet werden, der die gesamte benutzerdefinierte Konfiguration und SSL-Terminierung übernimmt.

#### Path
| Path | Definition |
|---|---|
| `/etc/tine20/config.inc.php` | Tine 2.0's Hauptkonfigurationsdatei.
| `/etc/tine20/conf.d/*` | Tine 2.0 Konfigurationsdateien werden automatisch eingeschlossen.
| `/var/lib/tine20/files` | Speichern der User-Daten. Dateien wie die im Tine 2.0-Dateimanager
| `/var/lib/tine20/tmp` | Temporäre Dateispeicherung
|`/var/lib/tine20/caching` | Wird zum Zwischenspeichern verwendet, wenn `TINE20_CACHING_BACKEND == 'File'`
|`/var/lib/tine20/sessions`  | Wird als Sitzungsspeicher verwendet, wenn `TINE20_SESSION_BACKEND == 'File'`

#### Options
| Option | Value | Definition |
|---|---|---|
| TINE20_ACCEPTED_TERMS_VERSION | __0__ | Die Version der akzeptierten Begriffe muss die aktuelle oder neuere Version sein.
| TINE20_ BUILDTYPE | __PRODUCTION__, DEVELOMENT | Muss auf DEVELOMENT eingestellt sein, damit das Webpack ordnungsgemäß funktioniert.
| TINE20_LOGGER_PRIORITY | __5__,0-7 |
| TINE20_FILESDIR | `/var/lib/tine20/files` |
| TINE20_TMPDIR | `/var/lib/tine20/tmp` |
| TINE20_DATABASE_HOST |  | verpflichtend
| TINE20_DATABASE_DBNAME |  | verpflichtend
| TINE20_DATABASE_USERNAME |  | verpflichtend
| TINE20_DATABASE_PASSWORD |  | verpflichtend
| TINE20_DATABASE_TABLEPREFIX | tine20_ |
| TINE20_DATABASE_ADAPTER | __pdo_mysql__ |
| TINE20_SETUPUSER_USERNAME |  | verpflichtend
| TINE20_SETUPUSER_PASSWORD |  | verpflichtend
| TINE20_LOGIN_USERNAME |  | verpflichtend
| TINE20_LOGIN_PASSWORD |  | verpflichtend
| TINE20_CACHING_ACTIVE | __true__, false |
| TINE20_CACHING_LIFETIME | 3600 |
| TINE20_CACHING_BACKEND | __File__, Redis |
| TINE20_CACHING_PATH | `/var/lib/tine20/caching` |
| TINE20_CACHING_REDIS_HOST |  | verpflichtend wenn TINE20_CACHING_BACKEND == Redis
| TINE20_CACHING_REDIS_POR | 6379 |
| TINE20_CACHING_REDIS_PREFIX | master |
| TINE20_SESSION_LIFETIME | 86400 |
| TINE20_SESSION_BACKEND | __File__,Redis  |
| TINE20_SESSION_HOST | `/var/lib/tine20/sessions` |
| TINE20_SESSION_PORT | 6379 |
| TINE20_SESSION_PATH |  | mandatory if TINE20_SESSION_BACKEND == Redis
| TINE20_CREDENTIALCACHESCHAREDKEY |  | verpflichtend
| TINE20__* |  | Alle Eigenschaften, die mit setup.php --setconfig festgelegt werden können, können auch mit TINE20__<app>_<property> or for Tinebase with TINE20__<property> festgelegt werden.
| NGINX_CLIENT_MAX_BODY_SIZE | 1m
| NGINX_KEEPALIVE_TIMEOUT | 65
| NGINX_TCP_NOPUSH | off
| NGINX_GZIP | off
| NGINX_GZIP_STATIC | off
| NGINXV_SERVER_NAME | _
| NGINXV_CLIENT_MAX_BODY_SIZE | 24M
| PHPFPM_DYNAMIC | dynamic | sollte pm genannt werden
| PHPFPM_PM_MAX_CHILDREN | 5
| PHPFPM_PM_START_SERVER | 2
| PHPFPM_PM_MIN_SPARE_SERVERS | 1
| PHPFPM_PM_MAX_SPARE_SERVERS | 3
| PHPFPM_PM_MAX_REQUETS | 500
| PHP_MAX_EXECUTION_TIME | 30
| PHP_MAX_INPUT_TIME | 60
| PHP_MEMORY_LIMIT | 1024M
| PHP_POST_MAX_SIZE | 8M
| PHP_UPLOAD_MAX_FILESIZE |2M
| PHP_MAX_FILE_UPLOADS | 20
| PHP_DEFAULT_SOCKET_TIMEOUT | 60
