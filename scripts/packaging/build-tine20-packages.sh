#!/bin/bash

# Version: $Id$

# examples
# $ ./build-tine20-packages.sh -s "http://git.tine20.org/git/tine20" -b master -r "2011_01_beta3-1" -c "Neele"
# $ ./build-tine20-packages.sh -s "http://git.tine20.org/git/tine20" -b 2011-01 -r "2011-01-3" -c "Neele"

## GLOBAL VARIABLES ##
BASEDIR="./tine20build"
TEMPDIR="$BASEDIR/temp"
MISCPACKAGESDIR="$BASEDIR/packages/misc"

RELEASE=""
CODENAME="Neele"
GITURL="http://git.tine20.org/git/tine20"
GITBRANCH="master"
PACKAGEDIR=""

#
# checkout url to local directory
#
function checkout()
{
    echo "checkout files from branch $2 at git url $1 to $TEMPDIR/tine20 ... "
    rm -rf $TEMPDIR/tine20
    
    rm -rf $TEMPDIR/tine20.git
    mkdir $TEMPDIR/tine20.git
    cd $TEMPDIR/tine20.git
    
    git init
    git remote add -t $2 -f origin $1
    git checkout $2
    
    if [ "$RELEASE" == "" ]; then
        RELEASE=$2-$(git log --abbrev-commit --pretty=oneline -1 | cut -d " " -f 1)
    fi
    
    cd - > /dev/null
    
    mv $TEMPDIR/tine20.git/tine20 $TEMPDIR/tine20
    rm -Rf $TEMPDIR/tine20.git
    
    echo "done"
}

### create dirs ###
function createDirectories()
{
    echo -n "creating directory structure $BASEDIR ... "
    test -d $BASEDIR || mkdir -p $BASEDIR
    
    test -d $MISCPACKAGESDIR || mkdir -p $MISCPACKAGESDIR
    test -d $TEMPDIR || mkdir -p $TEMPDIR
    
    #rm -rf $TEMPDIR/*
    #test -d $TEMPDIR/Setup || mkdir $TEMPDIR/Setup
    echo "done"
}

function getOptions()
{
    while getopts ":pr:s:b:c:" optname
    do
        case "$optname" in
          "c")
            CODENAME=$OPTARG
            ;;
          "b")
            GITBRANCH=$OPTARG
            ;;
          "s")
            GITURL=$OPTARG
            ;;
          "r")
            # release
            RELEASE=$OPTARG 
            ;;
          "?")
            echo "Unknown option $OPTARG"
            ;;
          ":")
            echo "No argument value for option $OPTARG"
            ;;
          *)
          # Should not occur
            echo "Unknown error while processing options"
            ;;
        esac
        #echo "OPTIND is now $OPTIND"
    done
}

function prepareYUICompressor()
{
    local YUICOMPRELEASE="2.4.2"
    local YUICOMPURL="http://yuilibrary.com/downloads/yuicompressor/yuicompressor-$YUICOMPRELEASE.zip"
    
    if [ ! -d $BASEDIR/yuicompressor ]; then
        test -e $MISCPACKAGESDIR/yuicompressor-$YUICOMPRELEASE.zip || wget -P $MISCPACKAGESDIR $YUICOMPURL
        echo -n "extracting YUI compressor $YUICOMPRELEASE... "
            test -d $BASEDIR/yuicompressor-$YUICOMPRELEASE || unzip $MISCPACKAGESDIR/yuicompressor-$YUICOMPRELEASE.zip -d $BASEDIR 2>&1 > /dev/null
            mv $BASEDIR/yuicompressor-$YUICOMPRELEASE $BASEDIR/yuicompressor
            mv $BASEDIR/yuicompressor/build/yuicompressor-$YUICOMPRELEASE.jar $BASEDIR/yuicompressor/build/yuicompressor.jar
        echo "done"
    fi
}

function activateReleaseMode()
{
    local DATETIME=`date "+%F %X%:z"`
    
    # set buildtype to DEBUG for beta releases
    if [[ $RELEASE == *beta* ]]; then  
        local BUILDTYPE="DEBUG";
    else
        local BUILDTYPE="RELEASE";
    fi
    
    echo "CODENAME: $CODENAME BUILDTYPE: $BUILDTYPE";
    
    sed -i -e "s/'buildtype', 'DEVELOPMENT'/'buildtype', '$BUILDTYPE'/" $TEMPDIR/tine20/Tinebase/Core.php
    sed -i -e "s/'buildtype', 'DEVELOPMENT'/'buildtype', '$BUILDTYPE'/" $TEMPDIR/tine20/Setup/Core.php
    
    sed -i -e "s#'TINE20_CODENAME',      getDevelopmentRevision()#'TINE20_CODENAME',      '$CODENAME'#" $TEMPDIR/tine20/Tinebase/Core.php
    sed -i -e "s#'TINE20SETUP_CODENAME', getDevelopmentRevision()#'TINE20SETUP_CODENAME',      '$CODENAME'#" $TEMPDIR/tine20/Setup/Core.php
    sed -i -e "s/'TINE20_PACKAGESTRING', 'none'/'TINE20_PACKAGESTRING', '$RELEASE'/" $TEMPDIR/tine20/Tinebase/Core.php
    sed -i -e "s/'TINE20SETUP_PACKAGESTRING', 'none'/'TINE20SETUP_PACKAGESTRING', '$RELEASE'/" $TEMPDIR/tine20/Setup/Core.php
    sed -i -e "s/'TINE20_RELEASETIME',   'none'/'TINE20_RELEASETIME',   '$DATETIME'/" $TEMPDIR/tine20/Tinebase/Core.php
    sed -i -e "s/'TINE20SETUP_RELEASETIME', 'none'/'TINE20SETUP_RELEASETIME',   '$DATETIME'/" $TEMPDIR/tine20/Setup/Core.php
    
    sed -i -e "s/Tine.clientVersion.buildType = 'DEBUG';/Tine.clientVersion.buildType = '$BUILDTYPE';/" $TEMPDIR/tine20/release.php
    sed -i -e "s#Tine.clientVersion.codeName = '\$revisionInfo';#Tine.clientVersion.codeName = '$CODENAME';#" $TEMPDIR/tine20/release.php
    sed -i -e "s/Tine.clientVersion.packageString = 'none'/Tine.clientVersion.packageString = '$RELEASE'/" $TEMPDIR/tine20/release.php
}

function compressFiles()
{
    echo -n "building compressed Javascript and CSS files ... "
    sed -i -e "s/trim(\`whoami\`)/'$RELEASE'/" $TEMPDIR/tine20/release.php
    php -d include_path=".:$TEMPDIR/tine20:$TEMPDIR/tine20/library"  -f $TEMPDIR/tine20/release.php -- -y $BASEDIR/yuicompressor/build/yuicompressor.jar -a
    echo "done"
}

function fixImagesPath()
{
    echo "Fixing image path ../../images => ../images"
    find $TEMPDIR/tine20 \( -path $TEMPDIR/tine20/Setup -prune -a -name "all-debug.js" -o -name "all.js" -o -name "all-debug.css" -o -name "all.css" \) -print0 | xargs -0 -n 1 sed -i -e "s/\.\.\/\.\.\/images/..\/images/g"
}

function createArchives()
{
    echo "building Tine 2.0 single archives... "
    for FILE in `ls $TEMPDIR/tine20`; do
        if [ -d "$TEMPDIR/tine20/$FILE/translations" ]; then
            case $FILE in
                Addressbook)
                    ;;
                Admin)
                    ;;
                Setup)
                    ;;

                Calendar)      
                    echo " $FILE"
                    echo -n "  cleanup "
                    (cd $TEMPDIR/tine20/$FILE/js;  rm -rf $(ls | grep -v all.js  | grep -v all-debug.js))
                    (cd $TEMPDIR/tine20/$FILE/css; rm -rf $(ls | grep -v all.css | grep -v all-debug.css | grep -v print.css))
                    echo "building "
                    (cd $TEMPDIR/tine20; tar cjf ../../packages/tine20/$RELEASE/tine20-${FILE,,}_$RELEASE.tar.bz2 $FILE)
                    (cd $TEMPDIR/tine20; zip -qr ../../packages/tine20/$RELEASE/tine20-${FILE,,}_$RELEASE.zip     $FILE)
                    ;;

                Tinebase)
                    echo " $FILE"
                    echo -n "  cleanup "
                    (cd $TEMPDIR/tine20/Addressbook/js;  rm -rf $(ls | grep -v all.js  | grep -v all-debug.js))
                    (cd $TEMPDIR/tine20/Addressbook/css; rm -rf $(ls | grep -v all.css | grep -v all-debug.css))
                    (cd $TEMPDIR/tine20/Admin/js;        rm -rf $(ls | grep -v all.js  | grep -v all-debug.js))
                    (cd $TEMPDIR/tine20/Admin/css;       rm -rf $(ls | grep -v all.css | grep -v all-debug.css))
                    (cd $TEMPDIR/tine20/Setup/js;        rm -rf $(ls | grep -v all.js  | grep -v all-debug.js))
                    (cd $TEMPDIR/tine20/Setup/css;       rm -rf $(ls | grep -v all.css | grep -v all-debug.css))
                    
                    # Tinebase/js/ux/Printer/print.css
                    (cd $TEMPDIR/tine20/Tinebase/js/ux/Printer; rm -rf $(ls | grep -v print.css))
                    (cd $TEMPDIR/tine20/Tinebase/js/ux;  rm -rf $(ls | grep -v Printer))
                    (cd $TEMPDIR/tine20/Tinebase/js;     rm -rf $(ls | grep -v all.js  | grep -v all-debug.js | grep -v Locale | grep -v ux))
                    (cd $TEMPDIR/tine20/Tinebase/css;    rm -rf $(ls | grep -v all.css | grep -v all-debug.css))
                    
                    # cleanup ExtJS
                    (cd $TEMPDIR/tine20/library/ExtJS/adapter; rm -rf $(ls | grep -v ext))
                    (cd $TEMPDIR/tine20/library/ExtJS/src;     rm -rf $(ls | grep -v debug.js))
                    (cd $TEMPDIR/tine20/library/ExtJS;         rm -rf $(ls | grep -v adapter | grep -v ext-all-debug.js | grep -v ext-all.js | grep -v resources | grep -v src))
                    
                    # cleanup HTMLPurifier
                    (cd $TEMPDIR/tine20/library/HTMLPurifier;  rm -rf HTMLPurifier.*.php)

                    # cleanup OpenLayers
                    (cd $TEMPDIR/tine20/library/OpenLayers;    rm -rf $(ls | grep -v img | grep -v license.txt | grep -v OpenLayers.js | grep -v theme))

                    # cleanup PHPExcel
                    (cd $TEMPDIR/tine20/library/PHPExcel/PHPExcel/Shared;  rm -rf PDF)
                    
                    echo -n "building "
                    local FILES="Addressbook Admin Setup Tinebase Zend images library styles config.inc.php.dist index.php langHelper.php LICENSE PRIVACY README RELEASENOTES release.php setup.php tine20.php"
                    (cd $TEMPDIR/tine20; tar cjf ../../packages/tine20/$RELEASE/tine20-${FILE,,}_$RELEASE.tar.bz2 $FILES)
                    (cd $TEMPDIR/tine20; zip -qr ../../packages/tine20/$RELEASE/tine20-${FILE,,}_$RELEASE.zip     $FILES)
                    
                    echo ""
                    ;;
                    
                *)
                    echo " $FILE"
                    echo -n "  cleanup "
                    (cd $TEMPDIR/tine20/$FILE/js;  rm -rf $(ls | grep -v all.js  | grep -v all-debug.js))
                    (cd $TEMPDIR/tine20/$FILE/css; rm -rf $(ls | grep -v all.css | grep -v all-debug.css))
                    echo "building "
                    (cd $TEMPDIR/tine20; tar cjf ../../packages/tine20/$RELEASE/tine20-${FILE,,}_$RELEASE.tar.bz2 $FILE)
                    (cd $TEMPDIR/tine20; zip -qr ../../packages/tine20/$RELEASE/tine20-${FILE,,}_$RELEASE.zip     $FILE)
                    ;;
            esac
            
        fi
    done
}

function createSpecialArchives()
{
    echo "building Tine 2.0 allinone archive... "
    rm -rf $TEMPDIR/allinone
    mkdir $TEMPDIR/allinone
    
    for ARCHIVENAME in calendar tinebase crm felamimail sales tasks timetracker; do
        (cd $TEMPDIR/allinone; tar xjf ../../packages/tine20/$RELEASE/tine20-${ARCHIVENAME}_$RELEASE.tar.bz2)
    done
    
    (cd $TEMPDIR/allinone; tar cjf ../../packages/tine20/$RELEASE/tine20-allinone_$RELEASE.tar.bz2 .)
    (cd $TEMPDIR/allinone; zip -qr ../../packages/tine20/$RELEASE/tine20-allinone_$RELEASE.zip     .)
    

    echo "building Tine 2.0 voip archive... "
    rm -rf $TEMPDIR/voip
    mkdir $TEMPDIR/voip
    
    for ARCHIVENAME in phone voipmanager; do
        (cd $TEMPDIR/voip; tar xjf ../../packages/tine20/$RELEASE/tine20-${ARCHIVENAME}_$RELEASE.tar.bz2)
    done
    
    (cd $TEMPDIR/voip; tar cjf ../../packages/tine20/$RELEASE/tine20-voip_$RELEASE.tar.bz2 .)
    (cd $TEMPDIR/voip; zip -qr ../../packages/tine20/$RELEASE/tine20-voip_$RELEASE.zip     .)
}

function setupPackageDir()
{
    PACKAGEDIR="$BASEDIR/packages/tine20/$RELEASE"
    rm -rf $PACKAGEDIR
    mkdir -p $PACKAGEDIR
}

function packageTranslations()
{
    echo -n "building translation files for translators... "
    php -d include_path=".:$TEMPDIR/tine20:$TEMPDIR/tine20/library"  -f $TEMPDIR/tine20/langHelper.php -- --package
    mv $TEMPDIR/tine20/translations.zip $PACKAGEDIR
    echo "done"
}

function buildChecksum()
{
    echo -n "calculating SHA1 checksums... "
    
    test -e $PACKAGEDIR/sha1sum_$RELEASE.txt && rm $PACKAGEDIR/sha1sum_$RELEASE.txt
    
    for fileName in $PACKAGEDIR/*; do
        (cd $PACKAGEDIR; sha1sum `basename $fileName`) >> $PACKAGEDIR/sha1sum_$RELEASE.txt 2>&1
    done
    
    echo "done"
}

getOptions $*
                 
createDirectories
prepareYUICompressor
checkout $GITURL $GITBRANCH
setupPackageDir
activateReleaseMode
compressFiles
#fixImagesPath
createArchives
createSpecialArchives
packageTranslations
buildChecksum
