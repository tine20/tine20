Tine 2.0 Business Edition Docker Image
---
[www.tine20.com](https://www.tine20.com/) | [GitHub](https://github.com/tine20/tine20) | [Dockerfile](https://github.com/tine20/tine20/blob/main/ci/dockerimage/Dockerfile)

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
| `/etc/tine20/config.inc.php` | Tine 2.0 Hauptkonfigurationsdatei.
| `/etc/tine20/conf.d/*` | Tine 2.0 Konfigurationsdateien werden automatisch eingeschlossen.
| `/var/lib/tine20/files` | Speichern der User-Daten. Dateien wie die im Tine 2.0-Dateimanager
| `/var/lib/tine20/tmp` | Temporäre Dateispeicherung
|`/var/lib/tine20/caching` | Wird zum Zwischenspeichern verwendet, wenn `TINE20_CACHING_BACKEND == 'File'`
|`/var/lib/tine20/sessions`  | Wird als Sitzungsspeicher verwendet, wenn `TINE20_SESSION_BACKEND == 'File'`
