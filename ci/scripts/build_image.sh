#!/bin/sh
set -e
# build a docker image with cache and cache invalidators (see dockerimage readme.md)

TARGET=$1
CI_COMMIT_REF_NAME_ESCAPED=$(echo ${CI_COMMIT_REF_NAME} | sed sI/I-Ig)
MAJOR_COMMIT_REF_NAME_ESCAPED=$(echo ${MAJOR_COMMIT_REF_NAME} | sed sI/I-Ig)

IMAGE="${REGISTRY}/${TARGET}-commit:${IMAGE_TAG}"
CACHE_IMAGE="${REGISTRY}/${TARGET}:${CI_COMMIT_REF_NAME_ESCAPED}-${PHP_VERSION}"
MAJOR_CACHE_IMAGE="${REGISTRY}/${TARGET}:${MAJOR_COMMIT_REF_NAME_ESCAPED}-${PHP_VERSION}"

# config via env
export PHP_VERSION=${PHP_VERSION}
export SOURCE_IMAGE="${REGISTRY}/source-commit:${IMAGE_TAG}"
export BUILD_IMAGE="${REGISTRY}/build-commit:${IMAGE_TAG}"
export BUILT_IMAGE="${REGISTRY}/build-commit:${IMAGE_TAG}"

export VERSION=${CI_COMMIT_TAG:-nightly}
export RELEASE=$(echo "${VERSION}" | sed sI-I~Ig)
export REVISION=0
export CODENAME="${CODENAME}"

cd ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20
./ci/dockerimage/make.sh -u -p -i "${IMAGE}" -c "${CACHE_IMAGE}" -c "${MAJOR_CACHE_IMAGE}" "${TARGET}"
