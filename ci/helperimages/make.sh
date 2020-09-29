#!/usr/bin/env bash

function make_docker() {
    docker build ${DOCKER_ADDITIONAL_BUILD_ARGS} \
    --tag ${AWS_ACCOUNT_ID}.dkr.ecr.${AWS_REGION}.amazonaws.com/tine20/docker:19.03.1 \
    --build-arg AWS_ACCOUNT_ID=${AWS_ACCOUNT_ID} \
    --build-arg AWS_REGION=${AWS_REGION} \
    --file docker.Dockerfile .
    docker push ${AWS_ACCOUNT_ID}.dkr.ecr.${AWS_REGION}.amazonaws.com/tine20/docker:19.03.1
}

function make_mysql() {
    docker build ${DOCKER_ADDITIONAL_BUILD_ARGS} \
    --tag ${AWS_ACCOUNT_ID}.dkr.ecr.${AWS_REGION}.amazonaws.com/tine20/mysql:8 \
    --file mysql.Dockerfile .
    docker push ${AWS_ACCOUNT_ID}.dkr.ecr.${AWS_REGION}.amazonaws.com/tine20/mysql:8
}

case $1 in
    docker)
        make_docker
        ;;
    mysql)
        make_mysql
        ;;
    *)
        echo \"$1\" not found
        ;;
esac