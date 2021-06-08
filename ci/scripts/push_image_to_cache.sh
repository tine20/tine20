#!/bin/sh
set -e

NAME=$1

$CI_PROJECT_DIR/ci/scripts/rename_remote_image.sh $REGISTRY_USER $REGISTRY_PASSWORD $REGISTRY $NAME-commit $CI_PIPELINE_ID-$PHP_VERSION $NAME $(echo $CI_COMMIT_REF_NAME | sed sI/I-Ig)-$PHP_VERSION
