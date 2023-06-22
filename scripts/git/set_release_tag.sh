#!/bin/bash

# (c) copyright Metaways Infosystems GmbH 2016-2022
# authors:  Philipp Schüle <p.schuele@metaways.de>
#
# USAGE: sh ~/Desktop/scripts/set_release_tag.sh 2018.11.1

TAG=$1

if [ "$TAG" = "" ]
then
  echo "Please enter release tag:"
  read -r TAG
fi

git pull
git tag -a $TAG -m "version $TAG"
git push origin $TAG

