tine Business Edition Docker Image
---
[www.tine-groupware.de](https://www.tine-groupware.de/) | [GitHub](https://github.com/tine20/tine20) | [Dockerfile](https://github.com/tine20/tine20/blob/main/ci/dockerimage/built.Dockerfile)

[[_TOC_]]

## Schnellstart

Dies ist eine schnelle und leichte Möglichkeit, tine auszuprobieren. Hierfür benötigen Sie Docker und Docker Compose (https://docs.docker.com/compose/).

Erstellen Sie im ersten Schritt einen Ordner. Docker Compose verwendet die Ordnernamen zur Identifizierung.

```
mkdir tine
cd tine
```
Als zweiten Schritt laden Sie die aktuelle Datei docker-compose.yaml herunter und speichern diese in dem soeben erstellten Ordner.

```
wget https://packages.tine20.com/maintenance/docker/current/quickstart/docker-compose.yaml
```

Jetzt können Sie Docker-Compose starten.

```
docker-compose up
```

Warten Sie einen Moment, bis die Datenbank erreichbar ist. Im Webcontainer Log steht dann `web_1    | DB available`. Dann können Sie Tine installieren. Öffnen Sie dafür ein neues Terminal und führen Sie den Installer aus. Im Installer müssen Sie die Tine-Lizenz und Datenschutzerklärung bestätigen und können das Password für den initialen Admin festlegen.

```
docker-compose exec web tine20_install
```

tine ist jetzt unter http://127.0.0.1:4000 erreichbar.

### Aufräumen
Um alle von Docker Compose erstellten Container, Netzwerke und Volumes zu stoppen und löschen nutzen Sie:
```
docker-compose down --volumes
```

## Image
Dieses Image enthält den tine-Code, PHP-FPM und Nginx. Zusätzlich benötigen Sie eine Datenbank, beispielsweise MariaDB. In der Produktion sollte dieses Image mit einem Reverse-Proxy verwendet werden, der die gesamte benutzerdefinierte Konfiguration und SSL-Terminierung übernimmt.

### Paths
| Path | Definition |
|---|---|
| `/etc/tine20/config.inc.php` | tine Hauptkonfigurationsdatei.
| `/etc/tine20/conf.d/*` | tine Konfigurationsdateien werden automatisch eingeschlossen.
| `/var/lib/tine20/files` | Speichern der User-Daten. Dateien wie die im tine-Dateimanager
| `/var/lib/tine20/tmp` | Temporäre Dateispeicherung
| `/var/lib/tine20/caching` | Wird zum Zwischenspeichern verwendet, wenn `TINE20_CACHING_BACKEND == 'File'`
| `/var/lib/tine20/sessions`  | Wird als Sitzungsspeicher verwendet, wenn `TINE20_SESSION_BACKEND == 'File'`

## Update

Zum Updaten einmal 'docker-compose down && docker-composer up' machen. Falls man eine andere Major-Version haben möchte, kann
vorher in der docker-compose.yml auch eine konkrete Version angegeben werden.

Zum Updaten von tine selbst verwendet man folgenden Befehl (ggf. muss der Name des Containers angepasst werden, herausfinden
kann man ihn z.B. mit 'docker ps'):

```
docker exec --user tine20 tine-docker_web_1 sh -c "php /usr/share/tine20/setup.php --config=/etc/tine20 --update"
```

## SSL / Reverse Proxy

Um den tine-Container "von aussen" verfügbar zu machen, kann man einen NGINX, Traefik oder HAProxy davorschalten.

### NGINX

Beispiel einer NGINX VHOST conf:

```apacheconf
server {
    listen 80;
    listen 443 ssl;
    
    ssl_certificate /etc/letsencrypt/live/MYDOMAIN.de/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/MYDOMAIN.de/privkey.pem;
    
    server_name tine.MYDOMAIN.de autodiscover.MYDOMAIN.de;
    
    if ($ssl_protocol = "" ) {
        rewrite        ^ https://$server_name$request_uri? permanent;
    }
    
    access_log /var/www/MYDOMAIN/logs/nginx-access.log;
    error_log /var/www/MYDOMAIN/logs/nginx-error.log;
    
    client_max_body_size 2G; # set maximum upload size
    
    location /.well-known { }
    
    location / {
        proxy_pass http://127.0.0.1:4000;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header Host $host;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

### TRAEFIK

Alternativ zu NGINX kann man auch traefik zur docker-composer.yml hinzufügen:

```yaml
  traefik:
    image: "traefik:v2.6"
    restart: always
    container_name: "traefik"
    command:
      - "--providers.docker=true"
      - "--providers.docker.exposedbydefault=false"
      - "--entrypoints.web.address=:80"
      - "--entrypoints.web.http.redirections.entryPoint.to=websecure"
      - "--entrypoints.web.http.redirections.entryPoint.scheme=https"
      - "--entrypoints.web.http.redirections.entrypoint.permanent=true"
      - "--entrypoints.websecure.address=:443"
      - "--certificatesresolvers.http01.acme.httpchallenge=true"
      - "--certificatesresolvers.http01.acme.httpchallenge.entrypoint=web"
      - "--certificatesresolvers.http01.acme.storage=/letsencrypt/acme.json"
    ports:
      - "80:80"
      - "443:443"
      - "8080:8080"
    volumes:
      - "./letsencrypt:/letsencrypt"
      - "/var/run/docker.sock:/var/run/docker.sock:ro"

  web:
    image: tinegroupware/tine:2021.11
    #[...]
    labels:
      - "traefik.enable=true"
      - "traefik.http.routers.tile-server.rule=Host(`MYDOMAIN.de`)"
      - "traefik.http.routers.tile-server.entrypoints=websecure"
      - "traefik.http.routers.tile-server.tls.certresolver=http01"
      - "traefik.http.services.tile-server.loadbalancer.server.port=80"
```

## Migration

Um von einer alten Installation mit lokaler tine auf das Docker-Setup zu migrieren, müssen nur die Volumes entsprechend gemountet werden:

```yaml
  db:
    image: mariadb:10.6
    volumes:
      - "/var/lib/mysql:/var/lib/mysql"
    #[...]
    
  web:
    image: tinegroupware/tine:2021.11
    volumes:
      - "/var/lib/tine20/files:/var/lib/tine20/files"
    #[...]
```

## Custom-Konfiguration

Wenn man möchte, kann man Custom-Configs (via conf.d) ebenfalls als eigenes Volume in den Container mounten:

```yaml
  web:
    image: tinegroupware/tine:2021.11
    volumes:
      - "conf.d:/etc/tine20/conf.d"
    #[...]
```
