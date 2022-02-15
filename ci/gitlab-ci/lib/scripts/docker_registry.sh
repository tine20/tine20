docker_registry_image_exists() {
    local image=$1;
    local tag=$2;
    curl -s -f --user $REGISTRY_USER:$REGISTRY_PASSWORD -H "accept: application/vnd.docker.distribution.manifest.v2+json" "https://${REGISTRY}/v2/${image}/manifests/${tag}" > /dev/null;
}

docker_registry_rename_remote() {
    local user=$1;
    local password=$2;
    local registry=$3;
    local old_repo=$4;
    local old_tag=$5;
    local new_repo=$6;
    local new_tag=$7;

    echo "$0 retagging $registry $old_repo/$old_tag to $new_repo/$new_tag";

    if ! curl -s -f --user $user:$password -H "accept: application/vnd.docker.distribution.manifest.v2+json" https://$registry/v2/$old_repo/manifests/$old_tag > /dev/null; then
        curl -s --user $user:$password -H "accept: application/vnd.docker.distribution.manifest.v2+json" https://$registry/v2/$old_repo/manifests/$old_tag;
        exit 1;
    fi;

    manifest=$(curl -s -f -X GET --user $user:$password -H "accept: application/vnd.docker.distribution.manifest.v2+json"  https://$registry/v2/$old_repo/manifests/$old_tag);

    for digest in $(echo $manifest | jq -r '.layers[].digest'); do
        curl -s -f -X POST --user $user:$password "https://$registry/v2/$new_repo/blobs/uploads/?mount=$digest&from=$old_repo";
    done;

    curl -s -f -X POST --user $user:$password "https://$registry/v2/$new_repo/blobs/uploads/?mount=$(echo $manifest | jq -r '.config.digest')&from=$old_repo";
    curl -s -f -X PUT --user $user:$password -H "Content-Type: application/vnd.docker.distribution.manifest.v2+json" --data "$manifest" https://$registry/v2/$new_repo/manifests/$new_tag;
}

docker_registry_use_hash_image_as_commit_image () {
    source=$1;
    target=$2;
    hash=$3;

    docker_registry_rename_remote $REGISTRY_USER $REGISTRY_PASSWORD $REGISTRY $source $hash ${target}-commit ${IMAGE_TAG};
}

docker_registry_login () {
    registry="$1"
    username="$2"
    password="$3"

    for i in {1..6}; do
        if docker login "$registry" --username "$username" --password "$password"; then
            return 0
        fi

        echo "($i) docker login failed, retrying it in 5 second ..."
        curl https://${REGISTRY}/fail-${CI_PIPELINE_ID}-${CI_JOB_ID} # create a marker in the log if login fails
        sleep 5
    done

    echo "docker login failed, aborting ..."
    return 1
}