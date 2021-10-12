#!/bin/sh  
set -e
NAME=$1

docker login -u "${CI_REGISTRY_USER}" -p "${CI_REGISTRY_PASSWORD}" "${CI_REGISTRY}"

FROM_IMAGE="${REGISTRY}/${NAME}-commit:${IMAGE_TAG}"
DESTINATION_IMAGE="${CI_REGISTRY}/${CI_PROJECT_NAMESPACE}/tine20/${NAME}:$(echo $CI_COMMIT_REF_NAME | sed sI/I-Ig)-${PHP_VERSION}"

docker pull "${FROM_IMAGE}"
docker tag "${FROM_IMAGE}" "${DESTINATION_IMAGE}"
docker push "${DESTINATION_IMAGE}"
