#!/bin/bash

# Version: $Id$

# examples
# $ ./build-tine20-packages.sh -s "http://git.tine20.org/git/tine20" -b master -r "2011_01_beta3-1" -c "Kristina"
# $ ./build-tine20-packages.sh -s "http://git.tine20.org/git/tine20" -b 2011-01 -r "2011-01-3" -c "Kristina"

## GLOBAL VARIABLES ##
BASEDIR=`readlink -f ./tine20build`
TEMPDIR="$BASEDIR/temp"
MISCPACKAGESDIR="$BASEDIR/packages/misc"

CODENAME="Collin"
GITURL="http://git.tine20.org/git/tine20"

RELEASE=""
GITBRANCH=""
PACKAGEDIR=""

PATH=$MISCPACKAGESDIR:$TEMPDIR/tine20/vendor/bin:$PATH

#
# checkout url to local directory
#
function checkout()
{
    echo "checkout files from git url $1 to $TEMPDIR/tine20 ... "
    rm -rf $TEMPDIR/tine20
    rm -rf $TEMPDIR/debian
    rm -rf $TEMPDIR/fedora
    rm -rf $TEMPDIR/Univention
    
    rm -rf $TEMPDIR/tine20.git
    mkdir $TEMPDIR/tine20.git
    cd $TEMPDIR/tine20.git
    
    git clone "$1" .

    if [ -n "$GITBRANCH" ]; then
        echo "checkout refspec"
        git checkout $GITBRANCH
        RETVAL=$?

        if [ $RETVAL -ne 0 ]; then
            echo "refspec $GITBRANCH not found. Either define a valid release name or a valid git refspec (-b)"
            exit
        fi
    elif [ -n "$RELEASE" ]; then   
        echo "checkout release tag"
        git checkout refs/tags/${RELEASE/\~/-}
        RETVAL=$?

        if [ $RETVAL -ne 0 ]; then
            echo "release tag refs/tags/$RELEASE not found. Either define a valid release name or a valid git refspec (-b)"
            exit
        fi
    else
        echo "You must either define a release (-r) or a branch (-b)"
        exit
    fi
    
    REVISION=$(git describe --tags)
    if [ "$RELEASE" == "" ]; then
        RELEASE=${REVISION}
    fi

    cd - > /dev/null

    mv $TEMPDIR/tine20.git/tine20 $TEMPDIR/tine20
    mv $TEMPDIR/tine20.git/scripts/packaging/debian $TEMPDIR/debian
    mv $TEMPDIR/tine20.git/scripts/packaging/fedora $TEMPDIR/fedora
    mv $TEMPDIR/tine20.git/scripts/packaging/Univention $TEMPDIR/Univention
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
    
    echo "done"
}

function getOptions()
{
    while getopts "pr:s:b:c:" optname
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

function activateReleaseMode()
{
    # utc datetime, like this: 2013-09-24 14:27:06
    local DATETIME=`date -u "+%F %T"`
    
    # set buildtype to DEBUG for beta releases
    if [[ $RELEASE == *beta* ]]; then  
        local BUILDTYPE="DEBUG";
    else
        local BUILDTYPE="RELEASE";
    fi
    
    echo "RELEASE: $RELEASE REVISION: $REVISION CODENAME: $CODENAME BUILDTYPE: $BUILDTYPE";
    
    sed -i -e "s/'buildtype', 'DEVELOPMENT'/'buildtype', '$BUILDTYPE'/" $TEMPDIR/tine20/Tinebase/Core.php
    sed -i -e "s/'buildtype', 'DEVELOPMENT'/'buildtype', '$BUILDTYPE'/" $TEMPDIR/tine20/Setup/Core.php
    
    sed -i -e "s#'TINE20_CODENAME',      getDevelopmentRevision()#'TINE20_CODENAME',      '$CODENAME'#" $TEMPDIR/tine20/Tinebase/Core.php
    sed -i -e "s#'TINE20SETUP_CODENAME',       getDevelopmentRevision()#'TINE20SETUP_CODENAME',      '$CODENAME'#" $TEMPDIR/tine20/Setup/Core.php
    sed -i -e "s/'TINE20_PACKAGESTRING', 'none'/'TINE20_PACKAGESTRING', '$RELEASE'/" $TEMPDIR/tine20/Tinebase/Core.php
    sed -i -e "s/'TINE20SETUP_PACKAGESTRING', 'none'/'TINE20SETUP_PACKAGESTRING', '$RELEASE'/" $TEMPDIR/tine20/Setup/Core.php
    sed -i -e "s/'TINE20_RELEASETIME',   'none'/'TINE20_RELEASETIME',   '$DATETIME'/" $TEMPDIR/tine20/Tinebase/Core.php
    sed -i -e "s/'TINE20SETUP_RELEASETIME',   'none'/'TINE20SETUP_RELEASETIME',   '$DATETIME'/" $TEMPDIR/tine20/Setup/Core.php
    
    sed -i -e "s/Tine.clientVersion.buildRevision[^;]*/Tine.clientVersion.buildRevision = '$REVISION'/" $TEMPDIR/tine20/Tinebase/js/tineInit.js
    sed -i -e "s/Tine.clientVersion.codeName[^;]*/Tine.clientVersion.codeName = '$CODENAME'/" $TEMPDIR/tine20/Tinebase/js/tineInit.js
    sed -i -e "s/Tine.clientVersion.packageString[^;]*/Tine.clientVersion.packageString = '$RELEASE'/" $TEMPDIR/tine20/Tinebase/js/tineInit.js
    sed -i -e "s/Tine.clientVersion.releaseTime[^;]*/Tine.clientVersion.releaseTime = '$DATETIME'/" $TEMPDIR/tine20/Tinebase/js/tineInit.js
}

function buildLangStats()
{
    echo -n "building lang stats ... "
    php -f $TEMPDIR/tine20/langHelper.php -- --statistics
    echo "done"
}

function buildClient()
{
    echo -n "building javascript clients ... "
    $TEMPDIR/tine20/vendor/bin/phing -f $TEMPDIR/tine20/build.xml build
    echo "done"
}

function createArchives()
{
    echo "building Tine 2.0 single archives... "
    CLIENTBUILDFILTER="FAT"
    
    for FILE in `ls $TEMPDIR/tine20`; do
        UCFILE=`echo ${FILE} | tr '[A-Z]' '[a-z]'`
        
        if [ -d "$TEMPDIR/tine20/$FILE/translations" ]; then
            case $FILE in
                Addressbook)
                    # handled in Tinebase
                    ;;
                Admin)
                    # handled in Tinebase
                    ;;
                Setup)
                    # handled in Tinebase
                    ;;

                Calendar)      
                    echo " $FILE"
                    echo -n "  cleanup "
                    (cd $TEMPDIR/tine20/$FILE/js;  rm -rf $(ls | grep -v ${CLIENTBUILDFILTER} | grep -v "\-lang\-"))
                    (cd $TEMPDIR/tine20/$FILE/css; rm -rf $(ls | grep -v ${CLIENTBUILDFILTER} | grep -v print.css))
                    echo "building "
                    (cd $TEMPDIR/tine20; tar cjf ../../packages/tine20/$RELEASE/tine20-${UCFILE}_$RELEASE.tar.bz2 $FILE)
                    (cd $TEMPDIR/tine20; zip -qr ../../packages/tine20/$RELEASE/tine20-${UCFILE}_$RELEASE.zip     $FILE)
                    ;;

                Tinebase)
                    echo " $FILE"
                    echo -n "  cleanup "
                    (cd $TEMPDIR/tine20/Addressbook/js;  rm -rf $(ls | grep -v ${CLIENTBUILDFILTER} | grep -v "\-lang\-"))
                    (cd $TEMPDIR/tine20/Addressbook/css; rm -rf $(ls | grep -v ${CLIENTBUILDFILTER}))
                    (cd $TEMPDIR/tine20/Admin/js;        rm -rf $(ls | grep -v ${CLIENTBUILDFILTER} | grep -v "\-lang\-"))
                    (cd $TEMPDIR/tine20/Admin/css;       rm -rf $(ls | grep -v ${CLIENTBUILDFILTER}))
                    (cd $TEMPDIR/tine20/Setup/js;        rm -rf $(ls | grep -v ${CLIENTBUILDFILTER} | grep -v "\-lang\-"))
                    (cd $TEMPDIR/tine20/Setup/css;       rm -rf $(ls | grep -v ${CLIENTBUILDFILTER}))
                    
                    # Tinebase/js/ux/Printer/print.css
                    (cd $TEMPDIR/tine20/Tinebase/js/ux/Printer; rm -rf $(ls | grep -v print.css))
                    # Tinebase/js/ux/data/windowNameConnection*
                    (cd $TEMPDIR/tine20/Tinebase/js/ux/data; rm -rf $(ls | grep -v windowNameConnection))
                    (cd $TEMPDIR/tine20/Tinebase/js/ux;  rm -rf $(ls | grep -v Printer | grep -v data))
                    (cd $TEMPDIR/tine20/Tinebase/js;     rm -rf $(ls | grep -v ${CLIENTBUILDFILTER} | grep -v Locale | grep -v ux | grep -v "\-lang\-"))
                    (cd $TEMPDIR/tine20/Tinebase/css;    rm -rf $(ls | grep -v ${CLIENTBUILDFILTER}))
                    
                    # cleanup ExtJS
                    (cd $TEMPDIR/tine20/library/ExtJS/adapter; rm -rf $(ls | grep -v ext))
                    (cd $TEMPDIR/tine20/library/ExtJS/src;     rm -rf $(ls | grep -v debug.js))
                    (cd $TEMPDIR/tine20/library/ExtJS;         rm -rf $(ls | grep -v adapter | grep -v ext-all-debug.js | grep -v ext-all.js | grep -v resources | grep -v src))
                    
                    # cleanup OpenLayers
                    (cd $TEMPDIR/tine20/library/OpenLayers;    rm -rf $(ls | grep -v img | grep -v license.txt | grep -v OpenLayers.js | grep -v theme))

                    # cleanup qCal
                    (cd $TEMPDIR/tine20/library/qCal;  rm -rf docs tests)

                    # cleanup jsb2tk
                    (cd $TEMPDIR/tine20/library/jsb2tk;  rm -rf JSBuilder2 tests)
                    
                    # save langStats
                    (mv $TEMPDIR/tine20/langstatistics.json $TEMPDIR/tine20/Tinebase/translations/langstatistics.json)
                    
                    # remove composer dev requires
                    composer install --no-dev -d $TEMPDIR/tine20
                    
                    rm -rf $TEMPDIR/tine20/vendor/zendframework/zendframework1
                    rm -rf $TEMPDIR/tine20/vendor/phpdocumentor
                    rm -rf $TEMPDIR/tine20/vendor/ezyang/htmlpurifier/{art,benchmarks,extras,maintenance,smoketests}
                    
                    find $TEMPDIR/tine20/vendor -name .gitignore -type f -print0 | xargs -0 rm -rf
                    find $TEMPDIR/tine20/vendor -name .git       -type d -print0 | xargs -0 rm -rf
                    find $TEMPDIR/tine20/vendor -name docs       -type d -print0 | xargs -0 rm -rf
                    find $TEMPDIR/tine20/vendor -name examples   -type d -print0 | xargs -0 rm -rf
                    find $TEMPDIR/tine20/vendor -name tests      -type d -print0 | xargs -0 rm -rf
                    
                    composer dumpautoload -d $TEMPDIR/tine20
                    
                    rm -rf $TEMPDIR/tine20/composer.*
                    
                    echo -n "building "
                    local FILES="Addressbook Admin Setup Tinebase Zend images library vendor docs fonts themes" 
                    local FILES="$FILES config.inc.php.dist index.php langHelper.php setup.php tine20.php bootstrap.php worker.php status.php"
                    local FILES="$FILES CREDITS LICENSE PRIVACY README RELEASENOTES chrome_web_app.json"
                    
                    (cd $TEMPDIR/tine20; tar cjf ../../packages/tine20/$RELEASE/tine20-${UCFILE}_$RELEASE.tar.bz2 $FILES)
                    (cd $TEMPDIR/tine20; zip -qr ../../packages/tine20/$RELEASE/tine20-${UCFILE}_$RELEASE.zip     $FILES)
                    
                    echo ""
                    ;;
                    
                *)
                    echo " $FILE"
                    echo -n "  cleanup "
                    (cd $TEMPDIR/tine20/$FILE/js;  rm -rf $(ls | grep -v ${CLIENTBUILDFILTER} | grep -v "\-lang\-" | grep -v "empty\.js"))
                    (cd $TEMPDIR/tine20/$FILE/css; rm -rf $(ls | grep -v ${CLIENTBUILDFILTER}))
                    echo "building "
                    (cd $TEMPDIR/tine20; tar cjf ../../packages/tine20/$RELEASE/tine20-${UCFILE}_$RELEASE.tar.bz2 $FILE)
                    (cd $TEMPDIR/tine20; zip -qr ../../packages/tine20/$RELEASE/tine20-${UCFILE}_$RELEASE.zip     $FILE)
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
    
    for ARCHIVENAME in activesync calendar tinebase crm felamimail filemanager projects sales tasks timetracker; do
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

function setupComposer()
{
    wget -O $MISCPACKAGESDIR/composer.phar https://getcomposer.org/composer.phar
    chmod ugo+x $MISCPACKAGESDIR/composer.phar
    (cd $MISCPACKAGESDIR; ln -sf composer.phar composer)

    composer install -d $TEMPDIR/tine20
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
    php -d include_path=".:$TEMPDIR/tine20:$TEMPDIR/tine20/library"  -f $TEMPDIR/tine20/langHelper.php -- --package=translations.tar.gz
    mv $TEMPDIR/tine20/translations.tar.gz $PACKAGEDIR
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

function prepareDebianPackaging()
{
    PACKAGEDIR="$BASEDIR/packages/debian/$RELEASE"
    rm -rf $PACKAGEDIR
    
    # Replace all matches of - with .
    DEBIANVERSION=${RELEASE//-/.}

    mkdir -p "$PACKAGEDIR/tine20-$DEBIANVERSION"
    
    echo -n "preparing debian packaging directory in $PACKAGEDIR/tine20-$DEBIANVERSION ... "
    
    tar -C $PACKAGEDIR/tine20-$DEBIANVERSION -xf $BASEDIR/packages/tine20/$RELEASE/tine20-allinone_$RELEASE.tar.bz2
    tar -C $PACKAGEDIR/tine20-$DEBIANVERSION -xf $BASEDIR/packages/tine20/$RELEASE/tine20-courses_$RELEASE.tar.bz2
    tar -C $PACKAGEDIR/tine20-$DEBIANVERSION -xf $BASEDIR/packages/tine20/$RELEASE/tine20-humanresources_$RELEASE.tar.bz2
    cp $BASEDIR/packages/tine20/$RELEASE/tine20-allinone_$RELEASE.tar.bz2 $PACKAGEDIR/tine20_$DEBIANVERSION.orig.tar.bz2
    cp $BASEDIR/packages/tine20/$RELEASE/tine20-courses_$RELEASE.tar.bz2 $PACKAGEDIR/tine20_$DEBIANVERSION.orig-Courses.tar.bz2
    cp $BASEDIR/packages/tine20/$RELEASE/tine20-humanresources_$RELEASE.tar.bz2 $PACKAGEDIR/tine20_$DEBIANVERSION.orig-HumanResources.tar.bz2
    cp -r $BASEDIR/temp/debian $PACKAGEDIR/tine20-$DEBIANVERSION

    echo "done"
}

function prepareUniventionPackaging()
{
    PACKAGEDIR="$BASEDIR/packages/univention/$RELEASE"
    rm -rf $PACKAGEDIR
    
    # Replace all matches of - with .
    DEBIANVERSION=${RELEASE//-/.}

    mkdir -p "$PACKAGEDIR/tine20-$DEBIANVERSION"
    
    echo -n "preparing univention packaging directory in $PACKAGEDIR/tine20-$DEBIANVERSION ... "
    
    tar -C $PACKAGEDIR/tine20-$DEBIANVERSION -xf $BASEDIR/packages/tine20/$RELEASE/tine20-allinone_$RELEASE.tar.bz2
    tar -C $PACKAGEDIR/tine20-$DEBIANVERSION -xf $BASEDIR/packages/tine20/$RELEASE/tine20-courses_$RELEASE.tar.bz2
    tar -C $PACKAGEDIR/tine20-$DEBIANVERSION -xf $BASEDIR/packages/tine20/$RELEASE/tine20-humanresources_$RELEASE.tar.bz2
    cp $BASEDIR/packages/tine20/$RELEASE/tine20-allinone_$RELEASE.tar.bz2 $PACKAGEDIR/tine20_$DEBIANVERSION.orig.tar.bz2
    cp $BASEDIR/packages/tine20/$RELEASE/tine20-courses_$RELEASE.tar.bz2 $PACKAGEDIR/tine20_$DEBIANVERSION.orig-Courses.tar.bz2
    cp $BASEDIR/packages/tine20/$RELEASE/tine20-humanresources_$RELEASE.tar.bz2 $PACKAGEDIR/tine20_$DEBIANVERSION.orig-HumanResources.tar.bz2
    cp -r $BASEDIR/temp/Univention/* $PACKAGEDIR/tine20-$DEBIANVERSION

    echo "done"
}

function prepareFedoraPackaging()
{
    PACKAGEDIR="$BASEDIR/packages/fedora/$RELEASE"
    rm -rf $PACKAGEDIR
    
    # Replace all matches of - with .
    RPMVERSION=${RELEASE//-/.}

    mkdir -p "$PACKAGEDIR/tine20-$RPMVERSION"
    
    echo -n "preparing fedora packaging directory in $PACKAGEDIR/tine20-$RPMVERSION ... "
    
    cp -r $BASEDIR/temp/fedora/* $PACKAGEDIR/tine20-$RPMVERSION/
    
    cp $BASEDIR/packages/tine20/$RELEASE/tine20-allinone_$RELEASE.tar.bz2 $PACKAGEDIR/tine20-$RPMVERSION/SOURCES/
    cp $BASEDIR/packages/tine20/$RELEASE/tine20-courses_$RELEASE.tar.bz2 $PACKAGEDIR/tine20-$RPMVERSION/SOURCES/
    cp $BASEDIR/packages/tine20/$RELEASE/tine20-humanresources_$RELEASE.tar.bz2 $PACKAGEDIR/tine20-$RPMVERSION/SOURCES/

    echo "done"
}

getOptions "$@"

createDirectories
checkout "$GITURL" "$GITBRANCH"
setupComposer
setupPackageDir
activateReleaseMode
buildLangStats
buildClient
createArchives
createSpecialArchives
packageTranslations
buildChecksum
prepareDebianPackaging
prepareFedoraPackaging
prepareUniventionPackaging
