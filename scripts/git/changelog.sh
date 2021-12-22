#!/bin/bash

VERSION1=$1
VERSION2=$2

if [ "$VERSION1" = "" ]
then
  # TODO allow to fetch this via "git describe"
  echo "please enter version 1:"
  read -r VERSION1
fi

if [ "$VERSION2" = "" ]
then
  echo "please enter version 2:"
  read -r VERSION2
fi

echo -e "## Features"
git log $VERSION1...$VERSION2 --oneline | egrep " \"*feature\(" | egrep -v "\(ci" | egrep -v "fixup" | egrep -v "WIP" | egrep -v "Draft"

echo -e "\n## Bugfixes"
git log $VERSION1...$VERSION2 --oneline | egrep " \"*fix\(" | egrep -v "\(ci" | egrep -v "fixup" | egrep -v "WIP" | egrep -v "Draft"

echo -e "\n## Refactoring"
git log $VERSION1...$VERSION2 --oneline | egrep " \"*refactor\(" | egrep -v "\(ci" | egrep -v "fixup" | egrep -v "WIP" | egrep -v "Draft"

# TODO allow to get all other changes with a param --full

if [ "$3" = "--full" ]
then
  echo -e "\n## Other Changes"
  git log $VERSION1...$VERSION2 --oneline | grep -v "Merge branch" \
    | grep -v "Merge remote" | egrep -v " \"*feature\(" | egrep -v " \"*fix\(" | egrep -v " \"*refactor\(" | egrep -v "\(ci"

  echo -e "\n## CI Changes"
  git log $VERSION1...$VERSION2 --oneline | egrep "\(ci" | egrep -v "fixup"
fi
