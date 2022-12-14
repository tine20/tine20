#!/bin/sh
apk add git;
echo "wait for mount";
date;
while [ ! -f /${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/tine_as_service.env ]; do 
    echo "Waiting for tine20...";
    sleep 1;
done;
sleep 10;
echo "tine20 dir available";
npm --prefix ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/tine20/Tinebase/js/ install;
npm --prefix ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/tine20/Tinebase/js/ start'