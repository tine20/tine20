docker_layer_cache_populat_with_hash_image() {
    target=$1;
    hash=$2;

    docker_registry_rename_remote $REGISTRY_USER $REGISTRY_PASSWORD $REGISTRY $target $hash $target $(echo $CI_COMMIT_REF_NAME | sed sI/I-Ig)-$PHP_VERSION;
}