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

Jetzt können Sie Docker-Compose starten.

```
docker-compose up
```

Warten Sie einen Moment, bis die Datenbank erreichbar ist. Im Webcontainer Log steht dann `web_1    | DB available`. Dann können Sie Tine installieren. Öffnen Sie dafür ein neues Terminal und führen Sie den Installer aus. Im Installer müssen Sie die Tine-Lizenz und Datenschutzerklärung bestätigen und können das Password für den initialen Admin festlegen.

```
docker-compose exec web tine20_install
```

Tine 2.0 ist jetzt unter http://127.0.0.1:4000 erreichbar.

##### Aufräumen
Um alle von Docker Compose erstellten Container, Netzwerke und Volumes zu stoppen und löschen nutzen Sie:
```
docker-compose down --volumes
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
| `/var/lib/tine20/caching` | Wird zum Zwischenspeichern verwendet, wenn `TINE20_CACHING_BACKEND == 'File'`
| `/var/lib/tine20/sessions`  | Wird als Sitzungsspeicher verwendet, wenn `TINE20_SESSION_BACKEND == 'File'`
