#!/bin/sh
set -e
# build a docker image with cache and cache invalidators (see dockerimage readme.md)

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
