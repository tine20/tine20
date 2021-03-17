#!/bin/bash

./dumpContainerGrants.sh

# add those for dry-run / verbose (-d -v)
DRYRUN=$1
VERBOSE=$2
USER="admin"
PASS="pass"

for i in Addressbook Calendar Tasks; do php /usr/share/tine20/tine20.php --config=/etc/tine20 $DRYRUN $VERBOSE \
  --method $i.setContainerGrantsReadOnly --username=$USER --password=$PASS || exit; done
