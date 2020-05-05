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

    sed -i -e "s/'buildtype', 'DEVELOPMENT'/'buildtype', '$BUILDTYPE'/" ${TINE20ROOT}/tine20/Tinebase/Core.php
    sed -i -e "s/'buildtype', 'DEVELOPMENT'/'buildtype', '$BUILDTYPE'/" ${TINE20ROOT}/tine20/Setup/Core.php

    sed -i -e "s#'TINE20_CODENAME', *Tinebase_Helper::getDevelopmentRevision()#'TINE20_CODENAME',      '$CODENAME'#" ${TINE20ROOT}/tine20/Tinebase/Core.php
    sed -i -e "s#'TINE20SETUP_CODENAME', *Tinebase_Helper::getDevelopmentRevision()#'TINE20SETUP_CODENAME',      '$CODENAME'#" ${TINE20ROOT}/tine20/Setup/Core.php
    sed -i -e "s/'TINE20_PACKAGESTRING', *'none'/'TINE20_PACKAGESTRING', '$RELEASE'/" ${TINE20ROOT}/tine20/Tinebase/Core.php
    sed -i -e "s/'TINE20SETUP_PACKAGESTRING', *'none'/'TINE20SETUP_PACKAGESTRING', '$RELEASE'/" ${TINE20ROOT}/tine20/Setup/Core.php
    sed -i -e "s/'TINE20_RELEASETIME', *'none'/'TINE20_RELEASETIME', '$DATETIME'/" ${TINE20ROOT}/tine20/Tinebase/Core.php
    sed -i -e "s/'TINE20SETUP_RELEASETIME', *'none'/'TINE20SETUP_RELEASETIME', '$DATETIME'/" ${TINE20ROOT}/tine20/Setup/Core.php
    sed -i -e "s/Tinebase_Helper::getDevelopmentRevision()/Tinebase_Helper::getCodename()/" ${TINE20ROOT}/tine20/build.xml

    if [ -x ${TINE20ROOT}/tine20/Tinebase/License/BusinessEdition.php ]
    then
        sed -i -e "s/= 500;/= 5;/" ${TINE20ROOT}/tine20/Tinebase/License/BusinessEdition.php
    fi

    sed -i -e "s/Tine.clientVersion.buildRevision[^;]*/Tine.clientVersion.buildRevision = '$REVISION'/" ${TINE20ROOT}/tine20/Tinebase/js/tineInit.js
    sed -i -e "s/Tine.clientVersion.codeName[^;]*/Tine.clientVersion.codeName = '$CODENAME'/" ${TINE20ROOT}/tine20/Tinebase/js/tineInit.js
    sed -i -e "s/Tine.clientVersion.packageString[^;]*/Tine.clientVersion.packageString = '$RELEASE'/" ${TINE20ROOT}/tine20/Tinebase/js/tineInit.js
    sed -i -e "s/Tine.clientVersion.releaseTime[^;]*/Tine.clientVersion.releaseTime = '$DATETIME'/" ${TINE20ROOT}/tine20/Tinebase/js/tineInit.js
}

function buildLangStats()
{
    echo -n "building lang stats ... "
    php -f ${TINE20ROOT}/tine20/langHelper.php -- --statistics
    echo "done"
}

function buildClient()
{
    cd ${TINE20ROOT}/tine20/ && ls vendor/bin && vendor/bin/phing build
}

function removeComposerDevDependencies() {
    cd ${TINE20ROOT}/tine20 && composer install --no-dev --no-ansi --no-progress --no-suggest
}

function cleanup()
{
    echo "cleanup tine20 files"
    CLIENTBUILDFILTER="FAT"

    for FILE in `ls ${TINE20ROOT}/tine20`; do
        UCFILE=`echo ${FILE} | tr '[A-Z]' '[a-z]'`

        # tine20 app needs translations OR Setup dir
        if [ -d "$TEMPDIR/tine20/$FILE/translations" ] || [ -d "$TEMPDIR/tine20/$FILE/Setup" ]; then
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
                    # TODO remove unminified js/vue files from pollClient dir?
                    (cd ${TINE20ROOT}/tine20/$FILE/js;  rm -rf $(ls | grep -v ${CLIENTBUILDFILTER} | grep -v "\-lang\-" | grep -v pollClient))
                    (cd ${TINE20ROOT}/tine20/$FILE/css; rm -rf $(ls | grep -v ${CLIENTBUILDFILTER} | grep -v print.css))
                    ;;

                Tinebase)
                    echo " $FILE"
                    echo -n "  cleanup "
                    (cd ${TINE20ROOT}/tine20/Addressbook/js;  rm -rf $(ls | grep -v ${CLIENTBUILDFILTER} | grep -v "\-lang\-"))
                    (cd ${TINE20ROOT}/tine20/Addressbook/css; rm -rf $(ls | grep -v ${CLIENTBUILDFILTER} | grep -v print.css))
                    (cd ${TINE20ROOT}/tine20/Admin/js;        rm -rf $(ls | grep -v ${CLIENTBUILDFILTER} | grep -v "\-lang\-"))
                    (cd ${TINE20ROOT}/tine20/Admin/css;       rm -rf $(ls | grep -v ${CLIENTBUILDFILTER} | grep -v print.css))
                    (cd ${TINE20ROOT}/tine20/Setup/js;        rm -rf $(ls | grep -v ${CLIENTBUILDFILTER} | grep -v "\-lang\-"))
                    (cd ${TINE20ROOT}/tine20/Setup/css;       rm -rf $(ls | grep -v ${CLIENTBUILDFILTER} | grep -v print.css))

                    # Tinebase/js/ux/Printer/print.css
                    (cd ${TINE20ROOT}/tine20/Tinebase/js/ux/Printer; rm -rf $(ls | grep -v print.css))
                    # Tinebase/js/ux/data/windowNameConnection*
                    (cd ${TINE20ROOT}/tine20/Tinebase/js/ux/data; rm -rf $(ls | grep -v windowNameConnection))
                    (cd ${TINE20ROOT}/tine20/Tinebase/js/ux;  rm -rf $(ls | grep -v Printer | grep -v data))
                    (cd ${TINE20ROOT}/tine20/Tinebase/js;     rm -rf $(ls | grep -v ${CLIENTBUILDFILTER} | grep -v Locale | grep -v ux | grep -v "\-lang\-" | grep -v node_modules))
                    (cd ${TINE20ROOT}/tine20/Tinebase/css;    rm -rf $(ls | grep -v ${CLIENTBUILDFILTER} | grep -v print.css | grep -v widgets))
                    (cd ${TINE20ROOT}/tine20/Tinebase/css/widgets;  rm -rf $(ls | grep -v ${CLIENTBUILDFILTER} | grep -v print.css))

                    # cleanup ExtJS
                    (cd ${TINE20ROOT}/tine20/library/ExtJS/adapter; rm -rf $(ls | grep -v ext))
                    (cd ${TINE20ROOT}/tine20/library/ExtJS/src;     rm -rf $(ls | grep -v debug.js))
                    (cd ${TINE20ROOT}/tine20/library/ExtJS;         rm -rf $(ls | grep -v adapter | grep -v ext-all-debug.js | grep -v ext-all.js | grep -v resources | grep -v src))

                    # cleanup OpenLayers
                    (cd ${TINE20ROOT}/tine20/library/OpenLayers;    rm -rf $(ls | grep -v img | grep -v license.txt | grep -v OpenLayers.js | grep -v theme))

                    # cleanup qCal
                    (cd ${TINE20ROOT}/tine20/library/qCal;  rm -rf docs tests)

                    # save langStats
                    (mv ${TINE20ROOT}/tine20/langstatistics.json ${TINE20ROOT}/tine20/Tinebase/translations/langstatistics.json)

                    # remove composer dev requires (--no-scripts to prevent post-install-cmds like "git submodule --init")
                    composer install --ignore-platform-reqs --no-dev --no-scripts -d ${TINE20ROOT}/tine20

                    rm -rf ${TINE20ROOT}/tine20/Tinebase/js/node_modules
                    rm -rf ${TINE20ROOT}/tine20/vendor/phpdocumentor
                    rm -rf ${TINE20ROOT}/tine20/vendor/ezyang/htmlpurifier/{art,benchmarks,extras,maintenance,smoketests}

                    find ${TINE20ROOT}/tine20/vendor -name .gitignore -type f -print0 | xargs -0 rm -rf
                    find ${TINE20ROOT}/tine20/vendor -name .git       -type d -print0 | xargs -0 rm -rf
                    find ${TINE20ROOT}/tine20/vendor -name docs       -type d -print0 | xargs -0 rm -rf
                    find ${TINE20ROOT}/tine20/vendor -name examples   -type d -print0 | xargs -0 rm -rf
                    find ${TINE20ROOT}/tine20/vendor -name tests      -type d -print0 | xargs -0 rm -rf

                    composer dumpautoload -d ${TINE20ROOT}/tine20

                    rm -rf ${TINE20ROOT}/tine20/composer.*
                    ;;
                *)
                    echo " $FILE"

                    echo -n "  cleanup "
                    if [ -d "${TINE20ROOT}/tine20/$FILE/js" ]; then
                        (cd ${TINE20ROOT}/tine20/$FILE/js;  rm -rf $(ls | grep -v ${CLIENTBUILDFILTER} | grep -v "\-lang\-" | grep -v "empty\.js"))
                    fi
                    if [ -d "${TINE20ROOT}/tine20/$FILE/css" ]; then
                        (cd ${TINE20ROOT}/tine20/$FILE/css; rm -rf $(ls | grep -v ${CLIENTBUILDFILTER} | grep -v print.css))
                    fi
                    ;;
            esac
        else
            echo " $FILE"
            # delete all files which the original build script dose not copy, because docker can not loop and copy

            local FILES="images|library|vendor|docs|fonts|CREDITS|LICENSE|PRIVACY|README|RELEASENOTES|init_plugins.php|favicon.ico"
            local FILES="$FILES|config.inc.php.dist|index.php|langHelper.php|setup.php|tine20.php|bootstrap.php|worker.php|status.php"

            if ! [[ "$FILE" =~ $(echo ^\($FILES\)$) ]]; then
                rm -rf $FILE
            fi
        fi
    done
}
