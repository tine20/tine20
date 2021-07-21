#!/usr/bin/env bash
set -e

function make() {
    local registry=$1
    local version=$2
    local push=$3
    local image=${registry}docker:${version}

    docker build ${DOCKER_ADDITIONAL_BUILD_ARGS} \
    --tag ${image} \
    --build-arg AWS_ACCOUNT_ID=${AWS_ACCOUNT_ID} \
    --build-arg AWS_REGION=${AWS_REGION} \
    --file Dockerfile ../../..

    if [[ $push == true ]]; then
        docker push ${image}
    fi
}

cd "$(dirname "$0")"

registry=""
version=$(cat .version)
push=false

while getopts r:t:hp opt
do
    case $opt in
        t) version=$OPTARG;;
        r) registry=$OPTARG/;;
        p) push=true;;
        h)
            echo "-r registry"
            echo "-t tag"
            echo "-h help"
            exit 1
            ;;
    esac
done

shift $(($OPTIND - 1))

case ${1:-docker} in
    docker) make "$registry" "$version" "$push";;
    *) echo "$0: unknown task -- '${1}'"
esac