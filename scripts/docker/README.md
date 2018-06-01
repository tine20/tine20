```
docker build -t tine20-server .

docker run --name tine20-server -d -p 80:80 -v tine20-data:/var/lib/tine20/files -e TINE20_DB_PASS=foobar -e TINE20_SETUP_PASS=foo tine20-server
docker run --name mysql-server -d -e MYSQL_ROOT_PASSWORD=foobar -e MYSQL_DATABASE=tine20 -e MYSQL_ROOT_HOST=% mysql/mysql-server


docker network create tine20
docker network connect tine20 mysql-server
docker network connect tine20 tine20-server
```

or easier ;)
```
docker-compose up -d
```

open your browser to
http://localhost/setup.php
