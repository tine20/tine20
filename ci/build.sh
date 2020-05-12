function login() {
  docker login $REGISTRY --username $REGISTRY_USER --password $REGISTRY_PASSWORD
}

function build_and_push() {
  NAME=$1

  docker build \
    --target $NAME \
    --tag $REGISTRY/$NAME:commit$CI_COMMIT_SHA-$PHP_IMAGE_TAG \
    --file ci/dockerimage/Dockerfile \
    --build-arg BUILDKIT_INLINE_CACHE=1 \
    --build-arg PHP_IMAGE=$REGISTRY/php \
    --build-arg PHP_IMAGE_TAG=$PHP_IMAGE_TAG \
    --build-arg BASE_IMAGE=$REGISTRY/base:commit$CI_COMMIT_SHA-$PHP_IMAGE_TAG \
    --build-arg DEPENDENCY_IMAGE=$REGISTRY/dependency:commit$CI_COMMIT_SHA-$PHP_IMAGE_TAG \
    --build-arg SOURCE_IMAGE=$REGISTRY/source:commit$CI_COMMIT_SHA-$PHP_IMAGE_TAG \
    --build-arg BUILD_IMAGE=$REGISTRY/build:commit$CI_COMMIT_SHA-$PHP_IMAGE_TAG \
    --build-arg BUILT_IMAGE=$REGISTRY/built:commit$CI_COMMIT_SHA-$PHP_IMAGE_TAG \
    --build-arg NPM_INSTALL_COMMAND="$NPM_INSTALL_COMMAND" \
    --build-arg NODE_TLS_REJECT_UNAUTHORIZED=0 \
    .

  echo "docker: built $1 image"

  # use --quiet, when docker v 20.03 is avaliable
  docker push $REGISTRY/$NAME:commit$CI_COMMIT_SHA-$PHP_IMAGE_TAG
}

function pull_tag_push() {
  NAME=$1
  SOURCE_TAG=$(echo $2 | sed sI/I-Ig)
  DESTINATION_TAG=$(echo $3 | sed sI/I-Ig)

  # use --quiet
  docker pull $REGISTRY/$NAME:$SOURCE_TAG
  docker tag $REGISTRY/$NAME:$SOURCE_TAG $REGISTRY/$NAME:$DESTINATION_TAG
  docker push $REGISTRY/$NAME:$DESTINATION_TAG
}