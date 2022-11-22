#!/bin/sh
set -e

TARGET=$1
REUSE=$2
CI_COMMIT_REF_NAME_ESCAPED=$(echo ${CI_COMMIT_REF_NAME} | sed sI/I-Ig)
MAJOR_COMMIT_REF_NAME_ESCAPED=$(echo ${MAJOR_COMMIT_REF_NAME} | sed sI/I-Ig)

if [ "${REUSE}" = "false" ]; then
    echo "building image ..."
    ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/ci/scripts/build_image.sh $TARGET
    exit 0
fi

echo "reusing image ..."
if curl -s -f --user $REGISTRY_USER:$REGISTRY_PASSWORD -H "accept: application/vnd.docker.distribution.manifest.v2+json" "https://${REGISTRY}/v2/${TARGET}/manifests/${CI_COMMIT_REF_NAME_ESCAPED}-${PHP_VERSION}${IMAGE_TAG_PLATFORM_POSTFIX}" > /dev/null; then
    echo "using branch image ..."


    exit 0    ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/ci/scripts/rename_remote_image.sh $REGISTRY_USER $REGISTRY_PASSWORD $REGISTRY ${TARGET} ${CI_COMMIT_REF_NAME_ESCAPED}-${PHP_VERSION}${IMAGE_TAG_PLATFORM_POSTFIX} ${TARGET}-commit ${IMAGE_TAG}
fi
echo "can not reuse branch image, trying major branch image ..."

if curl -s -f --user $REGISTRY_USER:$REGISTRY_PASSWORD -H "accept: application/vnd.docker.distribution.manifest.v2+json" "https://${REGISTRY}/v2/${TARGET}/manifests/${MAJOR_COMMIT_REF_NAME_ESCAPED}-${PHP_VERSION}${IMAGE_TAG_PLATFORM_POSTFIX}" > /dev/null; then
    echo "using major branch image ..."
    
    ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/ci/scripts/rename_remote_image.sh $REGISTRY_USER $REGISTRY_PASSWORD $REGISTRY ${TARGET} ${MAJOR_COMMIT_REF_NAME_ESCAPED}-${PHP_VERSION}${IMAGE_TAG_PLATFORM_POSTFIX} ${TARGET}-commit ${IMAGE_TAG}
    exit 0
fi
echo "can not reuse major branch image. building image ..."

${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/ci/scripts/build_image.sh $TARGET