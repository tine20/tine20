#!/usr/bin/env sh
/usr/sbin/confd -onetime -backend env
/usr/bin/supervisord -c /etc/supervisord.conf --nodaemon