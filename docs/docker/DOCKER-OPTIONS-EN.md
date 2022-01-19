Tine 2.0 Business Edition Docker Options
---
[www.tine20.com](https://www.tine20.com/) | [GitHub](https://github.com/tine20/tine20) | [Dockerfile](https://github.com/tine20/tine20/blob/main/ci/dockerimage/Dockerfile)

#### Options
| Option | Value | Description |
|---|---|---|
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
