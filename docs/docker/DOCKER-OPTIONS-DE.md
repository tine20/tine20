Tine 2.0 Business Edition Docker Optionen
---
[www.tine20.com](https://www.tine20.com/) | [GitHub](https://github.com/tine20/tine20) | [Dockerfile](https://github.com/tine20/tine20/blob/main/ci/dockerimage/Dockerfile)

#### Optionen
| Option | Wert | Beschreibung |
|---|---|---|
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
