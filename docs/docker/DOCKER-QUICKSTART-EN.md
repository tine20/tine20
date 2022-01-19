Tine 2.0 Business Edition Docker Image
---
[www.tine20.com](https://www.tine20.com/) | [GitHub](https://github.com/tine20/tine20) | [Dockerfile](https://github.com/tine20/tine20/blob/main/ci/dockerimage/Dockerfile)

### Quickstart
This is an easy way to try out tine20. You need Docker and Docker Compose (https://docs.docker.com/compose/).

First, create a folder. Docker Compose uses the folder names as an identifier.

```
mkdir tine20
cd tine20
```
Then you need to download the current docker-compose.yaml. And save it in the folder just created.
```
wget http://packages.tine20.com/maintenance/docker/current/quickstart/docker-compose.yaml
```
Now you can start the docker-compose.
```
docker-compose up
```

Wait for the database to become available. If it is, the web container will log `web_1    | DB available`. Now open another terminal and start the tine installer. There you need to accept the tine-license and Privacy policy and you will be able to set the initial admin password.

```
docker-compose exec web tine20_install
```

Tine2.0 is now reachable under http://127.0.0.1:4000.

##### Cleanup
Use the following to stop and delete all containers, networks and volumes created by this compose.
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
This image contains the Tine 2.0 code, PHP-FPM, and Nginx. Additionally, a database e.g MariaDB is required. In production, this image should be utilized with a reverse proxy handling all the custom configuration and ssl termination.

#### Path
| Path | Description |
|---|---|
| `/etc/tine20/config.inc.php` | Tine 2.0 main config file.
| `/etc/tine20/conf.d/*` | Tine 2.0 auto include config files.
| `/var/lib/tine20/files` | Stores user data. Files like in Tine 2.0 Filemanager
| `/var/lib/tine20/tmp` | Temporary file storage
|`/var/lib/tine20/caching` | Used for caching if `TINE20_CACHING_BACKEND == 'File'`
|`/var/lib/tine20/sessions`  | Used as session store if `TINE20_SESSION_BACKEND == 'File'`
