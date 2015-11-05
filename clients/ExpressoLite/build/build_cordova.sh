#!/bin/sh

# Expresso Lite
# Script that builds the cordova application. This script follows
# these steps
#
# 1 - Checks if script usage is correct (script must be invoked
#     with one parameter at most). See showusage () for more details
# 2 - Sets variables to define relevant paths to the build process
# 3 - Checks if the cordova app project dir exists. If it does not
#     exist (or if -regenerate is informed), we call cordova to
#     create a new project, overwriting config.xml with our own and
#     adding the Android plarform
# 4 - Clears cordova app 'www' folder to prepare it for our files
# 5 - Copies Expresso Lite src folder contents to cordova app 'www'
#     folder
# 6 - Deletes files that are not needed in the cordova app (like api
#     files, accessible module, and others)
# 7 - Overwrites some specific files to make Expresso Lite behave
#     correctly in cordova. These files are present cordova-build-src.
#     Currently, only CordovaConfig.js should be overwritten
# 8 - Calls Cordova specific build operations. See usage for more
#     details
#
# @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
# @author    Charles Wust <charles.wust@serpro.gov.br>
# @copyright Copyright (c) 2015 Serpro (http://www.serpro.gov.br)

#Checks if cordova is installed
command -v cordova -v >/dev/null 2>&1 || { echo >&2 "This script requires cordova to be installed. See ExpressoLite/cordova/README.md for more details."; exit 1; }


BUILD="-build"
EMULATE="-emulate"
RUN="-run"
NOBUILD="-nobuild"
REGENERATE="-regenerate"

ALLOWED_PARAMS="$BUILD $EMULATE $RUN $NOBUILD $REGENERATE"

showusage ()
{
  echo ""
  echo "Usage: ./build_cordova.sh [$BUILD|$EMULATE|$RUN|$NOBUILD|$REGENERATE] "
  echo "  $BUILD: Will build the cordova project, generating an APK"
  echo "  $EMULATE: Will start android emulation after the build"
  echo "  $RUN: Will run the android app in any connected devices (if no device is found, it starts the emulator)"
  echo "  $NOBUILD: Will only prepare the cordova project, but no cordova build or emulation will be invoked"
  echo "  $REGENERATE: Will recreate cordova from scratch (applying new config.xml and icons). No cordova build or emulation will be invoked."
  echo ""
  echo "$BUILD will be used if no parameter is informed"
}

#Checking # of parameters ($# is the number of parameters)
case $# in
  0)
    echo "No parameter informed, using default mode: $BUILD"
    MODE=$BUILD
    ;;
  1)
    #checking if parameter is one of the allowed
    for PARAM in $ALLOWED_PARAMS
    do
      if [ "$1" = "$PARAM" ]
      then
        MODE=$1
        break
      fi
    done
    #if we get here without a mode, it means we have an invalid parameter
    if [ -z $MODE ]
    then
      echo "Unkown parameter: $1"
      showusage
      exit
    fi
    ;;
  *)
    echo "Too many parameters"
    showusage
    exit
    ;;
esac



#setting relevant paths to the build process

#Expresso Lite main dir
PROJECT_DIR=${PWD}/..

#Cordova base dir
CORDOVA_MAIN_DIR=${PWD}/cordova

#The name of the folder that will store the cordova app project
CORDOVA_APP_DIR_NAME=cordova_app

#Cordova App project full path (derived from previous variables)
CORDOVA_APP_DIR=$CORDOVA_MAIN_DIR/$CORDOVA_APP_DIR_NAME

#Folder that stores Expresso Lite src files
SRC_DIR=$PROJECT_DIR/src

#Package name used by cordova
CORDOVA_PACKAGE_NAME=br.gov.serpro.expressobr

#Name of the Cordova App. This is the name shown on the mobile devices
CORDOVA_APP_NAME=ExpressoBr




#Delete the cordova app dir to force its regenaration, it that is the case
if [ "$MODE" = "$REGENERATE" ]
then
  echo "Cleaning $CORDOVA_APP_DIR to regenerate project from scratch"
  rm -rf $CORDOVA_APP_DIR
fi



#If cordova app project dir is not available, make cordova create the project
if [ ! -d "$CORDOVA_APP_DIR" ]
then
  echo "Creating the Cordova App project"
  cd $CORDOVA_MAIN_DIR
  cordova create $CORDOVA_APP_DIR_NAME $CORDOVA_PACKAGE_NAME $CORDOVA_APP_NAME
  echo "Overwriting config.xml and adding icons"
  cp $CORDOVA_MAIN_DIR/config.xml $CORDOVA_APP_DIR
  cp -r $CORDOVA_MAIN_DIR/icons $CORDOVA_APP_DIR
  echo "Adding Android platform"
  cd $CORDOVA_APP_DIR
  cordova platform add android
fi


#Lets copy Expresso Lite to Cordova's www folder
echo "Cleaning www folder..."
rm -rf $CORDOVA_APP_DIR/www/*

echo "Copying Expresso Lite src folder to cordova project..."
cp -r $SRC_DIR/* $CORDOVA_APP_DIR/www


#the following files and folders shoudn't be in the cordova version
ITEMS_TO_REMOVE="conf.php version.php accessible api"

echo "Removing unwanted files and folders from cordova project..."
for ITEM in $ITEMS_TO_REMOVE
do
 ITEM_PATH=$CORDOVA_APP_DIR/www/$ITEM
 rm -rf $ITEM_PATH
done


#Overwrite what needs to be overwritten in the cordova project
echo "Overwriting cordova project specific files..."
cp -rf $CORDOVA_MAIN_DIR/cordova-build-src/* $CORDOVA_APP_DIR/www


#All done, let's call cordova to do what the user asked for
cd $CORDOVA_APP_DIR
case $MODE in
  "$BUILD")
    echo "Building cordova project..."
    cordova build android
    ;;
  "$EMULATE")
    echo "Emulating cordova project..."
    cordova emulate android
    ;;
  "$RUN")
    echo "Running cordova project..."
    cordova run android
    ;;
  "$NOBUILD")
    echo "-nobuild parameter informed, cordova build will be skipped."
    ;;
  "$REGENERATE")
    echo "$REGENERATE parameter was informed, project was recreated."
    ;;
  *)
    echo "Problems with this script, please contact developers"
    ;;
esac

