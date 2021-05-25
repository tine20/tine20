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
    if docker pull "${REGISTRY}/${TARGET}:${CI_COMMIT_REF_NAME_ESCAPED}-${PHP_VERSION}"; then
        echo "using branch image ..."
        docker tag "${REGISTRY}/${TARGET}:${CI_COMMIT_REF_NAME_ESCAPED}-${PHP_VERSION}" "${REGISTRY}/${TARGET}-commit:${CI_PIPELINE_ID}-${PHP_VERSION}"
        docker push "${REGISTRY}/${TARGET}-commit:${CI_PIPELINE_ID}-${PHP_VERSION}"
        return 0
    fi

    echo "can not reuse branch image, trying major branch image ..."
    # todo curl head, dose not work with aws ecr
    if docker pull "${REGISTRY}/${TARGET}:${MAJOR_COMMIT_REF_NAME_ESCAPED}-${PHP_VERSION}"; then
        echo "using major branch image ..."
        docker tag "${REGISTRY}/${TARGET}:${MAJOR_COMMIT_REF_NAME_ESCAPED}-${PHP_VERSION}" "${REGISTRY}/${TARGET}-commit:${CI_PIPELINE_ID}-${PHP_VERSION}"
        docker push "${REGISTRY}/${TARGET}-commit:${CI_PIPELINE_ID}-${PHP_VERSION}"
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

    apk add bash util-linux

    ./ci/dockerimage/make.sh -u -p -i "${IMAGE}" -c "${CACHE_IMAGE}" -c "${MAJOR_CACHE_IMAGE}" "${TARGET}"
}

# push image to build registry(ecr)
function push_image() {
    TARGET=$1

    docker push "${REGISTRY}/${TARGET}-commit:${CI_PIPELINE_ID}-${PHP_VERSION}"
}

function tag_major_as_commit_image() {
    NAME=$1

    tag_image "${REGISTRY}" "${NAME}" "${MAJOR_COMMIT_REF_NAME}-${PHP_VERSION}" "${REGISTRY}" "${NAME}-commit" "${CI_PIPELINE_ID}-${PHP_VERSION}"
}

# impl for all tag functions
function tag_image() {
  FROM_REG=$1
  FROM_NAME=$2
  FROM_TAG=$(echo $3 | sed sI/I-Ig)
  DEST_REG=$4
  DEST_NAME=$5
  DEST_TAG=$(echo $6 | sed sI/I-Ig)

  FROM_IMAGE="${FROM_REG}/${FROM_NAME}:${FROM_TAG}"
  DESTINATION_IMAGE="${DEST_REG}/${DEST_NAME}:${DEST_TAG}"

  docker pull "${FROM_IMAGE}"
  docker tag "${FROM_IMAGE}" "${DESTINATION_IMAGE}"
  docker push "${DESTINATION_IMAGE}"
}
