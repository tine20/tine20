docker_build_image() {
    local target=$1;
    local hash=$2

    echo "building image: target: ${target}; tag: ${hash}";

    if [[ "$DOCKER_IMAGE_CACHE" == "false" ]] || ! docker_registry_image_exists ${target} ${hash}; then
        echo "building image ...";

        local LAYER_CACHE_IMAGE="${REGISTRY}/${TARGET}:$(echo ${CI_COMMIT_REF_NAME} | sed sI/I-Ig)-${PHP_VERSION}"
        local MAJOR_LAYER_CACHE_IMAGE="${REGISTRY}/${TARGET}:$(echo ${MAJOR_COMMIT_REF_NAME} | sed sI/I-Ig)-${PHP_VERSION}"

        cd ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20;

        cp $DOCKER_GIT_CONFIG ./ci/dockerimage/.gitconfig
        ./ci/dockerimage/make.sh -u -p -i "${REGISTRY}/${target}:${hash}" -c "${LAYER_CACHE_IMAGE}" -c "${MAJOR_LAYER_CACHE_IMAGE}" ${target};
    else
        echo "image exists ...";
    fi;
}