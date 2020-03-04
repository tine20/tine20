build_all() {
  stage_build_1
  stage_build_2
  stage_build_3
  stage_deploy_1
  stage_deploy_2
}

stage_build_1() {
  docker_build_image base $BUILD_TAG php $PHP_TAG
  job_build_dependency
  docker_push_image base $BUILD_TAG
  docker_push_image dependency $BUILD_TAG
}

stage_build_1_lazy() {
  if docker_pull dependency "$TINE_TAG-$PHP_TAG"; then
    if docker_pull base "$TINE_TAG-$PHP_TAG"; then
      docker_tag dependency "$TINE_TAG-$PHP_TAG" $BUILD_TAG
      docker_push_image dependency $BUILD_TAG
      docker_tag base "$TINE_TAG-$PHP_TAG" $BUILD_TAG
      docker_push_image base $BUILD_TAG
    else
      stage_build_1
    fi
  else
    stage_build_1
  fi
}

stage_build_2() {
  job_build_source
  job_build_test_source
  docker_push_image source $BUILD_TAG
  docker_push_image test-source $BUILD_TAG
}

stage_build_3() {
  docker_build_image build $BUILD_TAG $REGISTRY/source $BUILD_TAG
  job_build_built
  docker_build_image dev $BUILD_TAG $REGISTRY/base $BUILD_TAG
  job_build_test_built

  docker_push_image built $BUILD_TAG
  docker_push_image dev $BUILD_TAG
  docker_push_image test-built $BUILD_TAG
}

stage_deploy_1() {
  docker_tag base $BUILD_TAG "$TINE_TAG-$PHP_TAG"
  docker_tag dependency $BUILD_TAG "$TINE_TAG-$PHP_TAG"

  docker_push_image base "$TINE_TAG-$PHP_TAG"
  docker_push_image dependency "$TINE_TAG-$PHP_TAG"
}

stage_deploy_2() {
  docker_tag built $BUILD_TAG "$TINE_TAG-$PHP_TAG"
  docker_tag dev $BUILD_TAG "$TINE_TAG-$PHP_TAG"

  docker_push_image built "$TINE_TAG-$PHP_TAG"
  docker_push_image dev "$TINE_TAG-$PHP_TAG"
}

stage_delete_images() {
  docker_image_rm base $BUILD_TAG
  docker_image_rm dependency $BUILD_TAG
  docker_image_rm source $BUILD_TAG
  docker_image_rm test-source $BUILD_TAG
  docker_image_rm build $BUILD_TAG
  docker_image_rm dev $BUILD_TAG
  docker_image_rm built $BUILD_TAG
  docker_image_rm test-built $BUILD_TAG
}

job_build_test_source() {
  cp -r ../docs/config ./test-source/config

  docker_build_image test-source $BUILD_TAG $REGISTRY/source $BUILD_TAG
}

job_build_dependency() {
  cp -r ../tine20/library ./dependency/library
  cp ../tine20/composer.json ./dependency/composer.json
  cp ../tine20/composer.lock ./dependency/composer.lock
  cp ../tine20/Tinebase/js/package.json ./dependency/package.json
  cp ../tine20/Tinebase/js/npm-shrinkwrap.json ./dependency/npm-shrinkwrap.json

  docker_build_image dependency $BUILD_TAG $REGISTRY/base $BUILD_TAG
}

job_build_source() {
  mkdir ../ci/source/tine20
  cp -r ../.git ./source/tine20/
  cp -r ../scripts ./source/tine20/
  cp -r ../tests ./source/tine20/
  cp -r ../tine20 ./source/tine20/

  docker_build_image source $BUILD_TAG $REGISTRY/dependency $BUILD_TAG
}

job_build_built() {
  echo "$ docker build ./built -t $REGISTRY/built:$BUILD_TAG --build-arg SOURCE_IMAGE_BUILD=build --build-arg SOURCE_IMAGE_TAG_BUILD=$BUILD_TAG --build-arg SOURCE_IMAGE_BASE=$REGISTRY/base --build-arg SOURCE_IMAGE_TAG_BASE=$BUILD_TAG;"
  docker build ./built -t $REGISTRY/built:$BUILD_TAG --build-arg SOURCE_IMAGE_BUILD=$REGISTRY/build --build-arg SOURCE_IMAGE_TAG_BUILD=$BUILD_TAG --build-arg SOURCE_IMAGE_BASE=$REGISTRY/base --build-arg SOURCE_IMAGE_TAG_BASE=$BUILD_TAG;
}

job_build_test_built() {
  cp -r ../docs/config ./test-built/config

  echo "$ docker build ./test-built -t $REGISTRY/test-built:$BUILD_TAG --build-arg SOURCE_IMAGE_BUILD=build --build-arg SOURCE_IMAGE_TAG_BUILD=$BUILD_TAG --build-arg SOURCE_IMAGE_BUILT=$REGISTRY/built --build-arg SOURCE_IMAGE_TAG_BUILT=$BUILD_TAG;"
  docker build ./test-built -t $REGISTRY/test-built:$BUILD_TAG --build-arg SOURCE_IMAGE_BUILD=$REGISTRY/build --build-arg SOURCE_IMAGE_TAG_BUILD=$BUILD_TAG --build-arg SOURCE_IMAGE_BUILT=$REGISTRY/built --build-arg SOURCE_IMAGE_TAG_BUILT=$BUILD_TAG;
}

docker_build_image() {
  echo "$ docker build ./$1 -t $REGISTRY/$1:$2 --build-arg SOURCE_IMAGE=$3 --build-arg SOURCE_IMAGE_TAG=$4;"
  docker build ./$1 -t $REGISTRY/$1:$2 --build-arg SOURCE_IMAGE=$3 --build-arg SOURCE_IMAGE_TAG=$4;
}

docker_push_image() {
  if [ "$PUSH" = "true" ] ; then
    echo "$ docker push $REGISTRY/$1:$2"
    docker push $REGISTRY/$1:$2
  fi
}

docker_login() {
  if [ "$PUSH" = "true" ] ; then
    echo "$ docker login $REG --username **** --password ****"
    docker login $REG --username $REG_USER --password $REG_PASS
  fi
}


docker_pull() {
  echo "$ docker pull $REGISTRY/$1:$2"
  docker pull $REGISTRY/$1:$2
}

docker_tag() {
  echo "$ docker tag $REGISTRY/$1:$2 $REGISTRY/$1:$3"
  docker tag $REGISTRY/$1:$2 $REGISTRY/$1:$3
}

docker_image_rm() {
  echo "$ docker image rm $REGISTRY/$1:$2 || true"
  docker image rm $REGISTRY/$1:$2 || true
}

REG="${REG:-tine20-docker-registry.mws-hosting.net}"
REG_PREFIX="${REG_PREFIX:-docker/tine20}"
BUILD_TAG="${CI_PIPELINE_ID:-latest}"
PUSH="${PUSH:-false}"
REGISTRY="$REG/$REG_PREFIX"
TINE_TAG="${CI_COMMIT_REF_NAME:-unknown}"
TINE_TAG="$(echo $TINE_TAG | sed sI/I-Ig)"
PHP_TAG="${PHP_IMAGE_TAG:-unknown}"

echo $PUSH

cd `dirname "$0"`
$1