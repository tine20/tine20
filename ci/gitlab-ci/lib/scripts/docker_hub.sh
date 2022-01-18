docker_hub_deploy () {
    set -e
    name=$1
    dockerhubname=$2
    dockerhubtag=$3

    docker login -u "${DOCKERHUB_USER}" -p "${DOCKERHUB_TOKEN}" "docker.io"

    FROM_IMAGE="${REGISTRY}/${name}-commit:${IMAGE_TAG}"
    DESTINATION_IMAGE="docker.io/tinegroupware/${dockerhubname}:${dockerhubtag}"

    docker pull "${FROM_IMAGE}"
    docker tag "${FROM_IMAGE}" "${DESTINATION_IMAGE}"
    docker push "${DESTINATION_IMAGE}"
}

docker_hub_deploy_with_tag () {
    # deploy to nightly if tag not set
    if [ -z "$CI_COMMIT_TAG" ]; then
        echo "deploying nightly"
        docker_hub_deploy $1 $2 $3
        return
    fi

    # do not deploy if customer is not tine20.com
    if [ "$(repo_get_customer_for_branch $MAJOR_COMMIT_REF_NAME)" != "tine20.com" ]; then
        echo "nothing to deploy"
        return
    fi

    docker_hub_deploy $1 $2 $CI_COMMIT_TAG
    docker_hub_deploy $1 $2 $(echo $CI_COMMIT_TAG | cut -d '.' -f 1)
}