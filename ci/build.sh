# defines function to be used by the ci

function login() {
  docker login "${REGISTRY}" --username "${REGISTRY_USER}" --password "${REGISTRY_PASSWORD}"
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
    if docker pull "${REGISTRY}/${TARGET}:${CI_COMMIT_REF_NAME_ESCAPED}-${PHP_IMAGE_TAG}"; then
        echo "using branch image ..."
        docker tag "${REGISTRY}/${TARGET}:${CI_COMMIT_REF_NAME_ESCAPED}-${PHP_IMAGE_TAG}" "${REGISTRY}/${TARGET}-commit:${CI_PIPELINE_ID}-${PHP_IMAGE_TAG}"
        return 0
    fi

    echo "can not reuse branch image, trying major branch image ..."
    # todo curl head, dose not work with aws ecr
    if docker pull "${REGISTRY}/${TARGET}:${MAJOR_COMMIT_REF_NAME_ESCAPED}-${PHP_IMAGE_TAG}"; then
        echo "using major branch image ..."
        docker tag "${REGISTRY}/${TARGET}:${MAJOR_COMMIT_REF_NAME_ESCAPED}-${PHP_IMAGE_TAG}" "${REGISTRY}/${TARGET}-commit:${CI_PIPELINE_ID}-${PHP_IMAGE_TAG}"
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

    echo "docker build ${TARGET} image"

    docker pull "${REGISTRY}/${TARGET}:${CI_COMMIT_REF_NAME_ESCAPED}-${PHP_IMAGE_TAG}" || echo "no cache image for ${TARGET}"
    docker pull "${REGISTRY}/${TARGET}:${MAJOR_COMMIT_REF_NAME_ESCAPED}-${PHP_IMAGE_TAG}" || echo "no major cache image for ${TARGET}"

    ALPINE_PHP_REPOSITORY_VERSION=v3.12
    if [ ${PHP_IMAGE_TAG} = "7.4-fpm-alpine" ] ; then
        ALPINE_PHP_REPOSITORY_VERSION=edge
    fi

    docker build ${DOCKER_ADDITIONAL_BUILD_ARGS} \
        --target "${TARGET}" \
        --tag "${REGISTRY}/${TARGET}-commit:${CI_PIPELINE_ID}-${PHP_IMAGE_TAG}" \
        --file "ci/dockerimage/${TARGET}.Dockerfile" \
        --build-arg "BUILDKIT_INLINE_CACHE=1" \
        --build-arg "BASE_IMAGE=${REGISTRY}/base-commit:${CI_PIPELINE_ID}-${PHP_IMAGE_TAG}" \
        --build-arg "BASE_CACHE_INVALIDATOR=base-cache-invalidator-commit" \
        --build-arg "DEPENDENCY_IMAGE=${REGISTRY}/dependency-commit:${CI_PIPELINE_ID}-${PHP_IMAGE_TAG}" \
        --build-arg "DEPENDENCY_CACHE_INVALIDATOR=dependency-cache-invalidator-commit" \
        --build-arg "SOURCE_IMAGE=${REGISTRY}/source-commit:${CI_PIPELINE_ID}-${PHP_IMAGE_TAG}" \
        --build-arg "SOURCE_ICON_SET_PROVIDER=source-icon-set-provider-commit" \
        --build-arg "BUILD_IMAGE=${REGISTRY}/build-commit:${CI_PIPELINE_ID}-${PHP_IMAGE_TAG}" \
        --build-arg "BUILT_IMAGE=${REGISTRY}/build-commit:${CI_PIPELINE_ID}-${PHP_IMAGE_TAG}" \
        --build-arg "DEV_CACHE_INVALIDATOR=dev-cache-invalidator-commit" \
        --build-arg "NODE_TLS_REJECT_UNAUTHORIZED=0" \
        --build-arg NPM_INSTALL_COMMAND="${NPM_INSTALL_COMMAND}" \
        --build-arg "CUSTOM_APP_VENDOR=${CUSTOM_APP_VENDOR}" \
        --build-arg "CUSTOM_APP_NAME=${CUSTOM_APP_NAME}" \
        --build-arg "CUSTOM_APP_GIT_URL=${CUSTOM_APP_GIT_URL}" \
        --build-arg "CUSTOM_APP_VERSION=${CUSTOM_APP_VERSION}" \
        --build-arg GERRIT_URL="${GERRIT_URL}" \
        --build-arg GERRIT_USER="${GERRIT_USER}" \
        --build-arg GERRIT_PASSWORD="${GERRIT_PASSWORD}" \
        --build-arg ALPINE_PHP_REPOSITORY_VERSION=ALPINE_PHP_REPOSITORY_VERSION \
        --cache-from "${REGISTRY}/${TARGET}:${CI_COMMIT_REF_NAME_ESCAPED}-${PHP_IMAGE_TAG}" \
        --cache-from "${REGISTRY}/${TARGET}:${MAJOR_COMMIT_REF_NAME_ESCAPED}-${PHP_IMAGE_TAG}" \
        .
}

# If no layer changed during the build used the cache image.
# If no layer changed during the build the content has not changed. But docker uses a new image sha. The old image is
# identical. By using it we keep the same image sha if nothing has changed.
function use_cached_image_when_nothing_changed() {
    TARGET=$1
    CI_COMMIT_REF_NAME_ESCAPED=$(echo ${CI_COMMIT_REF_NAME} | sed sI/I-Ig)

    echo "docker inspect ${TARGET}"
    if docker pull -q "${REGISTRY}/${TARGET}:${CI_COMMIT_REF_NAME_ESCAPED}-${PHP_IMAGE_TAG}"; then
        NEW_LAYER=$(docker inspect --format "{{range .RootFS.Layers}}{{.}}{{end}}" "${REGISTRY}/${TARGET}-commit:${CI_PIPELINE_ID}-${PHP_IMAGE_TAG}")
        ORIGINAL_LAYER=$(docker inspect --format "{{range .RootFS.Layers}}{{.}}{{end}}" "${REGISTRY}/${TARGET}:${CI_COMMIT_REF_NAME_ESCAPED}-${PHP_IMAGE_TAG}")

        echo "docker inspect new image:"
        docker inspect "${REGISTRY}/${TARGET}-commit:${CI_PIPELINE_ID}-${PHP_IMAGE_TAG}"
        echo "docker inspect original image:"
        docker inspect "${REGISTRY}/${TARGET}:${CI_COMMIT_REF_NAME_ESCAPED}-${PHP_IMAGE_TAG}"

        if test ${NEW_LAYER} = ${ORIGINAL_LAYER}; then
            docker tag "${REGISTRY}/${TARGET}:${CI_COMMIT_REF_NAME_ESCAPED}-${PHP_IMAGE_TAG}" "${REGISTRY}/${TARGET}-commit:${CI_PIPELINE_ID}-${PHP_IMAGE_TAG}"
            echo "Building ${TARGET} did not result in changes, using ${REGISTRY}/${TARGET}:${CI_COMMIT_REF_NAME_ESCAPED}-${PHP_IMAGE_TAG}"
        fi
    fi
}

# push image to build registry(ecr)
function push_image() {
    TARGET=$1

    docker push "${REGISTRY}/${TARGET}-commit:${CI_PIPELINE_ID}-${PHP_IMAGE_TAG}"
}

function tag_major_as_commit_image() {
    NAME=$1

    tag_image "${REGISTRY}" "${NAME}" "${MAJOR_COMMIT_REF_NAME}-${PHP_IMAGE_TAG}" "${REGISTRY}" "${NAME}-commit" "${CI_PIPELINE_ID}-${PHP_IMAGE_TAG}"
}

# renames a commit image name-commit:"${CI_PIPELINE_ID}-${PHP_IMAGE_TAG}" to name:"${CI_COMMIT_REF_NAME}-${PHP_IMAGE_TAG}" and pushes it
function docker_populate_cache() {
    NAME=$1

    tag_image "${REGISTRY}" "${NAME}-commit" "${CI_PIPELINE_ID}-${PHP_IMAGE_TAG}" "${REGISTRY}" "${NAME}" "${CI_COMMIT_REF_NAME}-${PHP_IMAGE_TAG}" || true
}

# renames a commit image name-commit:"${CI_PIPELINE_ID}-${PHP_IMAGE_TAG}" name:"${CI_PIPELINE_ID}-${PHP_IMAGE_TAG}" and pushes it to docker hub
function tag_commit_as_gitlab_image() {
    NAME=$1

    docker login -u "${CI_REGISTRY_USER}" -p "${CI_REGISTRY_PASSWORD}" "${CI_REGISTRY}"
    tag_image "${REGISTRY}" "${NAME}-commit" "${CI_PIPELINE_ID}-${PHP_IMAGE_TAG}" "${CI_REGISTRY}/tine20/tine20" "$NAME" "${CI_COMMIT_REF_NAME}-${PHP_IMAGE_TAG}"
}

# renames a commit image name-commit:"${CI_PIPELINE_ID}-${PHP_IMAGE_TAG}" DOCKERHUB_NAME:DOCKERHUB_TAG
function tag_commit_as_dockerhub_image() {
    NAME=$1
    DOCKERHUB_NAME=$2

    docker login -u "${DOCKERHUB_USER}" -p "${DOCKERHUB_TOKEN}" "docker.io"
    tag_image "${REGISTRY}" "${NAME}-commit" "${CI_PIPELINE_ID}-${PHP_IMAGE_TAG}" "docker.io/tine20" "${DOCKERHUB_NAME}" "${DOCKERHUB_TAG}"
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