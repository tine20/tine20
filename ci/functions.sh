# defines function to be used by the ci

function docker_login() {
      if [ ! -z "${REGISTRY_USER}" ] && [ ! -z "${REGISTRY_PASSWORD}" ]; then
        echo docker login ...
        docker login "${REGISTRY}" --username "${REGISTRY_USER}" --password "${REGISTRY_PASSWORD}"
    else
        echo no registry credentials.
    fi
}

function build_or_reuse_image() {
    TARGET=$1
    REUSE=$2
    CI_COMMIT_REF_NAME_ESCAPED=$(echo ${CI_COMMIT_REF_NAME} | sed sI/I-Ig)
    MAJOR_COMMIT_REF_NAME_ESCAPED=$(echo ${MAJOR_COMMIT_REF_NAME} | sed sI/I-Ig)
 
    if [ "${REUSE}" = "false" ]; then
        echo "building image ..."
        build_image $TARGET
        return 0
    fi

    echo "reusing image ..."
    # todo curl head, dose not work with aws ecr
    if curl -s -f --user $REGISTRY_USER:$REGISTRY_PASSWORD -H "accept: application/vnd.docker.distribution.manifest.v2+json" "https://${REGISTRY}/v2/${TARGET}/manifests/${CI_COMMIT_REF_NAME_ESCAPED}-${PHP_VERSION}" > /dev/null; then
        echo "using branch image ..."

        $CI_PROJECT_DIR/ci/scripts/rename_remote_image.sh $REGISTRY_USER $REGISTRY_PASSWORD $REGISTRY ${TARGET} ${CI_COMMIT_REF_NAME_ESCAPED}-${PHP_VERSION} ${TARGET}-commit ${CI_PIPELINE_ID}-${PHP_VERSION}
        return 0
    fi

    echo "can not reuse branch image, trying major branch image ..."
    # todo curl head, dose not work with aws ecr
    if curl -s -f --user $REGISTRY_USER:$REGISTRY_PASSWORD -H "accept: application/vnd.docker.distribution.manifest.v2+json" "https://${REGISTRY}/v2/${TARGET}/manifests/${MAJOR_COMMIT_REF_NAME_ESCAPED}-${PHP_VERSION}" > /dev/null; then
        echo "using major branch image ..."
        
	$CI_PROJECT_DIR/ci/scripts/rename_remote_image.sh $REGISTRY_USER $REGISTRY_PASSWORD $REGISTRY ${TARGET} ${MAJOR_COMMIT_REF_NAME_ESCAPED}-${PHP_VERSION} ${TARGET}-commit ${CI_PIPELINE_ID}-${PHP_VERSION}
        return 0
    fi
    
    echo "can not reuse major branch image. building image ..."
    build_image $TARGET
}

# build a docker image with cache and cache invalidators (see dockerimage readme.md)
function build_image() {
    TARGET=$1
    CI_COMMIT_REF_NAME_ESCAPED=$(echo ${CI_COMMIT_REF_NAME} | sed sI/I-Ig)
    MAJOR_COMMIT_REF_NAME_ESCAPED=$(echo ${MAJOR_COMMIT_REF_NAME} | sed sI/I-Ig)

    IMAGE="${REGISTRY}/${TARGET}-commit:${CI_PIPELINE_ID}-${PHP_VERSION}"
    CACHE_IMAGE="${REGISTRY}/${TARGET}:${CI_COMMIT_REF_NAME_ESCAPED}-${PHP_VERSION}"
    MAJOR_CACHE_IMAGE="${REGISTRY}/${TARGET}:${MAJOR_COMMIT_REF_NAME_ESCAPED}-${PHP_VERSION}"

    # config via env
    export PHP_VERSION=${PHP_VERSION}
    export BASE_IMAGE="${REGISTRY}/base-commit:${CI_PIPELINE_ID}-${PHP_VERSION}"
    export DEPENDENCY_IMAGE="${REGISTRY}/dependency-commit:${CI_PIPELINE_ID}-${PHP_VERSION}"
    export SOURCE_IMAGE="${REGISTRY}/source-commit:${CI_PIPELINE_ID}-${PHP_VERSION}"
    export BUILD_IMAGE="${REGISTRY}/build-commit:${CI_PIPELINE_ID}-${PHP_VERSION}"
    export BUILT_IMAGE="${REGISTRY}/build-commit:${CI_PIPELINE_ID}-${PHP_VERSION}"

    ./ci/dockerimage/make.sh -u -p -i "${IMAGE}" -c "${CACHE_IMAGE}" -c "${MAJOR_CACHE_IMAGE}" "${TARGET}"
}
