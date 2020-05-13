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

echo -e "#Features"
git log $VERSION1...$VERSION2 --oneline | grep feature

echo -e "\n#Bugfixes"
git log $VERSION1...$VERSION2 --oneline | grep fix

# TODO allow to get all other changes with a param --full

if [ "$3" = "--full" ]
then
  echo -e "\n#Other Changes"
  git log $VERSION1...$VERSION2 --oneline | grep -v "Merge branch" | grep -v "Merge remote" | grep -v feature | grep -v fix
fi
