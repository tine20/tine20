phpstan_analyse() {
    cd $TINE20ROOT
    mkdir -p ci/phpstan
    cp ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/ci/phpstan/bootstrap.php ci/phpstan/
    

    if test "${CI_PROJECT_NAME}" == "tine20"; then
        dir=tine20
    else
        dir=tine20/vendor/$(cat ${CI_PROJECT_DIR}/composer.json | jq -r '.name')/lib;
    fi

    # fix: phpstan fails if custom apps are symlinked. They need to be analysed in the vendor dir.
    #    exclude symlinks
    find $TINE20ROOT/tine20 -maxdepth 1 -type l -exec echo "        - '{}'" \; >> excludes;
    #    unexclude vendor/metaways
    find $TINE20ROOT/tine20/vendor -mindepth 1 -maxdepth 1 -type d -exec echo "        - '{}'" \; >> excludes;
    sed -i '/tine20\/vendor\*/r excludes' $TINE20ROOT/phpstan.neon;
    sed -i '/tine20\/vendor\/metaways/d' $TINE20ROOT/phpstan.neon;
    rm excludes

    $TINE20ROOT/tine20/vendor/bin/phpstan --version
    echo "analyse target: $dir"
    set -o pipefail
    php -d memory_limit=2G $TINE20ROOT/tine20/vendor/bin/phpstan analyse --autoload-file=$TINE20ROOT/tine20/vendor/autoload.php --error-format=gitlab --no-progress -vvv $dir | tee ${CI_PROJECT_DIR}/code-quality-report.json
}