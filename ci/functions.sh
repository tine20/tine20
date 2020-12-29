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
        return 0
    fi

    echo "can not reuse branch image, trying major branch image ..."
    # todo curl head, dose not work with aws ecr
    if docker pull "${REGISTRY}/${TARGET}:${MAJOR_COMMIT_REF_NAME_ESCAPED}-${PHP_VERSION}"; then
        echo "using major branch image ..."
        docker tag "${REGISTRY}/${TARGET}:${MAJOR_COMMIT_REF_NAME_ESCAPED}-${PHP_VERSION}" "${REGISTRY}/${TARGET}-commit:${CI_PIPELINE_ID}-${PHP_VERSION}"
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

    docker pull "${REGISTRY}/${TARGET}:${CI_COMMIT_REF_NAME_ESCAPED}-${PHP_VERSION}" || echo "no cache image for ${TARGET}"
    docker pull "${REGISTRY}/${TARGET}:${MAJOR_COMMIT_REF_NAME_ESCAPED}-${PHP_VERSION}" || echo "no major cache image for ${TARGET}"

    case "${PHP_VERSION}" in
        7.3)
            ALPINE_PHP_REPOSITORY_BRANCH=v3.12
            ALPINE_PHP_REPOSITORY_REPOSITORY=main
            ALPINE_PHP_PACKAGE=php7
            ;;
        7.4)
            ALPINE_PHP_REPOSITORY_BRANCH=edge
            ALPINE_PHP_REPOSITORY_REPOSITORY=main
            ALPINE_PHP_PACKAGE=php7
            ;;
        8.0)
            ALPINE_PHP_REPOSITORY_BRANCH=edge
            ALPINE_PHP_REPOSITORY_REPOSITORY=community
            ALPINE_PHP_PACKAGE=php8
            ;;
        *)
            echo "Unsupported php version: ${PHP_VERSION}!"
            exit 1
            ;;
    esac

    docker build ${DOCKER_ADDITIONAL_BUILD_ARGS} \
        --target "${TARGET}" \
        --tag "${REGISTRY}/${TARGET}-commit:${CI_PIPELINE_ID}-${PHP_VERSION}" \
        --file "ci/dockerimage/${TARGET}.Dockerfile" \
        --build-arg "BUILDKIT_INLINE_CACHE=1" \
        --build-arg "BASE_IMAGE=${REGISTRY}/base-commit:${CI_PIPELINE_ID}-${PHP_VERSION}" \
        --build-arg "BASE_CACHE_INVALIDATOR=base-cache-invalidator-commit" \
        --build-arg "DEPENDENCY_IMAGE=${REGISTRY}/dependency-commit:${CI_PIPELINE_ID}-${PHP_VERSION}" \
        --build-arg "DEPENDENCY_CACHE_INVALIDATOR=dependency-cache-invalidator-commit" \
        --build-arg "SOURCE_IMAGE=${REGISTRY}/source-commit:${CI_PIPELINE_ID}-${PHP_VERSION}" \
        --build-arg "SOURCE_ICON_SET_PROVIDER=source-icon-set-provider-commit" \
        --build-arg "BUILD_IMAGE=${REGISTRY}/build-commit:${CI_PIPELINE_ID}-${PHP_VERSION}" \
        --build-arg "BUILT_IMAGE=${REGISTRY}/build-commit:${CI_PIPELINE_ID}-${PHP_VERSION}" \
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
        --build-arg "ALPINE_PHP_REPOSITORY_BRANCH=${ALPINE_PHP_REPOSITORY_BRANCH}" \
        --build-arg "ALPINE_PHP_REPOSITORY_REPOSITORY=${ALPINE_PHP_REPOSITORY_REPOSITORY}" \
        --build-arg "ALPINE_PHP_PACKAGE=${ALPINE_PHP_PACKAGE}" \
        --cache-from "${REGISTRY}/${TARGET}:${CI_COMMIT_REF_NAME_ESCAPED}-${PHP_VERSION}" \
        --cache-from "${REGISTRY}/${TARGET}:${MAJOR_COMMIT_REF_NAME_ESCAPED}-${PHP_VERSION}" \
        .
}

# If no layer changed during the build used the cache image.
# If no layer changed during the build the content has not changed. But docker uses a new image sha. The old image is
# identical. By using it we keep the same image sha if nothing has changed.
function use_cached_image_when_nothing_changed() {
    TARGET=$1
    CI_COMMIT_REF_NAME_ESCAPED=$(echo ${CI_COMMIT_REF_NAME} | sed sI/I-Ig)

    echo "docker inspect ${TARGET}"
    if docker pull -q "${REGISTRY}/${TARGET}:${CI_COMMIT_REF_NAME_ESCAPED}-${PHP_VERSION}"; then
        NEW_LAYER=$(docker inspect --format "{{range .RootFS.Layers}}{{.}}{{end}}" "${REGISTRY}/${TARGET}-commit:${CI_PIPELINE_ID}-${PHP_VERSION}")
        ORIGINAL_LAYER=$(docker inspect --format "{{range .RootFS.Layers}}{{.}}{{end}}" "${REGISTRY}/${TARGET}:${CI_COMMIT_REF_NAME_ESCAPED}-${PHP_VERSION}")

        echo "docker inspect new image:"
        docker inspect "${REGISTRY}/${TARGET}-commit:${CI_PIPELINE_ID}-${PHP_VERSION}"
        echo "docker inspect original image:"
        docker inspect "${REGISTRY}/${TARGET}:${CI_COMMIT_REF_NAME_ESCAPED}-${PHP_VERSION}"

        if test ${NEW_LAYER} = ${ORIGINAL_LAYER}; then
            docker tag "${REGISTRY}/${TARGET}:${CI_COMMIT_REF_NAME_ESCAPED}-${PHP_VERSION}" "${REGISTRY}/${TARGET}-commit:${CI_PIPELINE_ID}-${PHP_VERSION}"
            echo "Building ${TARGET} did not result in changes, using ${REGISTRY}/${TARGET}:${CI_COMMIT_REF_NAME_ESCAPED}-${PHP_VERSION}"
        fi
    fi
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

# renames a commit image name-commit:"${CI_PIPELINE_ID}-${PHP_VERSION}" to name:"${CI_COMMIT_REF_NAME}-${PHP_VERSION}" and pushes it
function docker_populate_cache() {
    NAME=$1

    tag_image "${REGISTRY}" "${NAME}-commit" "${CI_PIPELINE_ID}-${PHP_VERSION}" "${REGISTRY}" "${NAME}" "${CI_COMMIT_REF_NAME}-${PHP_VERSION}" || true
}

# renames a commit image name-commit:"${CI_PIPELINE_ID}-${PHP_VERSION}" name:"${CI_PIPELINE_ID}-${PHP_VERSION}" and pushes it to docker hub
function tag_commit_as_gitlab_image() {
    NAME=$1

    docker login -u "${CI_REGISTRY_USER}" -p "${CI_REGISTRY_PASSWORD}" "${CI_REGISTRY}"
    tag_image "${REGISTRY}" "${NAME}-commit" "${CI_PIPELINE_ID}-${PHP_VERSION}" "${CI_REGISTRY}/tine20/tine20" "$NAME" "${CI_COMMIT_REF_NAME}-${PHP_VERSION}"
}

# renames a commit image name-commit:"${CI_PIPELINE_ID}-${PHP_VERSION}" DOCKERHUB_NAME:DOCKERHUB_TAG
function tag_commit_as_dockerhub_image() {
    NAME=$1
    DOCKERHUB_NAME=$2

    docker login -u "${DOCKERHUB_USER}" -p "${DOCKERHUB_TOKEN}" "docker.io"
    tag_image "${REGISTRY}" "${NAME}-commit" "${CI_PIPELINE_ID}-${PHP_VERSION}" "docker.io/tine20" "${DOCKERHUB_NAME}" "${DOCKERHUB_TAG}"
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

function docker_untag_image() {
	image=$1
	tag=$2

	digest=$(curl -X HEAD -I -v --user ${REGISTRY_USER}:${REGISTRY_PASSWORD} -H "Accept: application/vnd.docker.distribution.manifest.v2+json" https://${REGISTRY}/v2/${image}/manifests/${tag} | awk 'BEGIN {FS=": "}/^docker-content-digest/{print $2}' | tr -d '\r' )
	if [ -z ${digest} ]; then
		return 1
	fi

	docker pull ${REGISTRY}/cleanup-manifest:latest
	docker tag ${REGISTRY}/cleanup-manifest:latest ${REGISTRY}/${image}:${tag}

	curl -X DELETE -v --user ${REGISTRY_USER}:${REGISTRY_PASSWORD} https://${REGISTRY}/v2/${image}/manifests/${digest}
}