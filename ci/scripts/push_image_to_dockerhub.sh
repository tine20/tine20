#!/bin/sh
set -e
NAME=$1
DOCKERHUB_NAME=$2

docker login -u "${DOCKERHUB_USER}" -p "${DOCKERHUB_TOKEN}" "docker.io"

FROM_IMAGE="${REGISTRY}/${NAME}-commit:${IMAGE_TAG}"
DESTINATION_IMAGE="docker.io/tinegroupware/${DOCKERHUB_NAME}:${DOCKERHUB_TAG}"

docker pull "${FROM_IMAGE}"
docker tag "${FROM_IMAGE}" "${DESTINATION_IMAGE}"
docker push "${DESTINATION_IMAGE}"
