#!/bin/sh
set -e

NAME=$1

${CI_BUILDS_DIR}/tine20/tine20/ci/scripts/rename_remote_image.sh $REGISTRY_USER $REGISTRY_PASSWORD $REGISTRY $NAME-commit ${IMAGE_TAG} $NAME $(echo $CI_COMMIT_REF_NAME | sed sI/I-Ig)-$PHP_VERSION
