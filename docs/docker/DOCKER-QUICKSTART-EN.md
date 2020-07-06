Tine 2.0 Business Edition Docker Image
---
[www.tine20.com](https://www.tine20.com/) | [GitHub](https://github.com/tine20/tine20) | [Dockerfile](https://github.com/tine20/tine20/blob/main/ci/dockerimage/Dockerfile)

##### Versions
| Tag | Status | Versions |
|---|---|---|
| *2020.11-7.3-alpine* | beta | 2020.11, php 7.3, alpine
| __2019.11-7.3-alpine__ | stable | 2019.11, php 7.3, alpine

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
Next, you must accept the tine20 license. One way to do this is by setting the TINE20_ACCEPTED_TERMS_VERSION environment variable to the current or a newer version e.g 1000. This can be done in the .env file or in the docker-compose yaml.
```
echo "TINE20_ACCEPTED_TERMS_VERSION=1000" > .env
```
Now you can start the docker-compose.
```
docker-compose up
```

Wait a moment until the web container logs `web_1    | 2027-11-23 19:59:07,137 INFO success: nginx entered RUNNING state, process has stayed up for > than 1 seconds (startsecs)` Tine2.0 is now reachable under http://127.0.0.1:4000.

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

#### Options
| Option | Value | Description |
|---|---|---|
| TINE20_ACCEPTED_TERMS_VERSION | __0__ | Accepted terms version must be the current terms version or newer.
| TINE20_ BUILDTYPE | __PRODUCTION__, DEVELOMENT | Must be set to DEVELOPMENT for webpack to function correctly.
| TINE20_LOGGER_PRIORITY | __5__,0-7 |
| TINE20_FILESDIR | `/var/lib/tine20/files` |
| TINE20_TMPDIR | `/var/lib/tine20/tmp` |
| TINE20_DATABASE_HOST |  | mandatory
| TINE20_DATABASE_DBNAME |  | mandatory
| TINE20_DATABASE_USERNAME |  | mandatory
| TINE20_DATABASE_PASSWORD |  | mandatory
| TINE20_DATABASE_TABLEPREFIX | tine20_ |
| TINE20_DATABASE_ADAPTER | __pdo_mysql__ |
| TINE20_SETUPUSER_USERNAME |  | mandatory
| TINE20_SETUPUSER_PASSWORD |  | mandatory
| TINE20_LOGIN_USERNAME |  | mandatory
| TINE20_LOGIN_PASSWORD |  | mandatory
| TINE20_CACHING_ACTIVE | __true__, false |
| TINE20_CACHING_LIFETIME | 3600 |
| TINE20_CACHING_BACKEND | __File__, Redis |
| TINE20_CACHING_PATH | `/var/lib/tine20/caching` |
| TINE20_CACHING_REDIS_HOST |  | mandatory if TINE20_CACHING_BACKEND == Redis
| TINE20_CACHING_REDIS_POR | 6379 |
| TINE20_CACHING_REDIS_PREFIX | master |
| TINE20_SESSION_LIFETIME | 86400 |
| TINE20_SESSION_BACKEND | __File__,Redis  |
| TINE20_SESSION_HOST | `/var/lib/tine20/sessions` |
| TINE20_SESSION_PORT | 6379 |
| TINE20_SESSION_PATH |  | mandatory if TINE20_SESSION_BACKEND == Redis
| TINE20_CREDENTIALCACHESCHAREDKEY |  | mandatory
| TINE20__* |  | All property which can be set with setup.php --setconfig, can also set with TINE20__<app>_<property> or for Tinebase with TINE20__<property>.
| NGINX_CLIENT_MAX_BODY_SIZE | 1m
| NGINX_KEEPALIVE_TIMEOUT | 65
| NGINX_TCP_NOPUSH | off
| NGINX_GZIP | off
| NGINX_GZIP_STATIC | off
| NGINXV_SERVER_NAME | _
| NGINXV_CLIENT_MAX_BODY_SIZE | 24M
| PHPFPM_DYNAMIC | dynamic | should be named pm
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
