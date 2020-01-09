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
  docker_pull dependency "$TINE_TAG-$PHP_TAG"
  docker_tag dependency "$TINE_TAG-$PHP_TAG" $BUILD_TAG
  docker_push_image dependency $BUILD_TAG
}

stage_build_2() {
  job_build_source
  docker_build_image test-source $BUILD_TAG $REGISTRY/source $BUILD_TAG
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
  sed -i "s~^FROM .* as build~FROM $REGISTRY/build:$BUILD_TAG as build~g" ./built/Dockerfile
  sed -i "s~^FROM .* as built~FROM $REGISTRY/base:$BUILD_TAG as built~g" ./built/Dockerfile

  echo "docker build ./built -t $REGISTRY/built:$BUILD_TAG;"
  docker build ./built -t $REGISTRY/built:$BUILD_TAG;
}

job_build_test_built() {
  sed -i "s~^FROM .* as build~FROM $REGISTRY/build:$BUILD_TAG as build~g" ./test-built/Dockerfile
  sed -i "s~^FROM .* as test~FROM $REGISTRY/built:$BUILD_TAG as test~g" ./test-built/Dockerfile

  echo "docker build ./test-built -t $REGISTRY/test-built:$BUILD_TAG;"
  docker build ./test-built -t $REGISTRY/test-built:$BUILD_TAG;
}

docker_build_image() {
  sed -i "s~^FROM.*~FROM $3:$4~g" ./$1/Dockerfile

  echo "docker build ./$1 -t $REGISTRY/$1:$2;"
  docker build ./$1 -t $REGISTRY/$1:$2;
}

docker_push_image() {
  if [ "$PUSH" = "true" ] ; then
    echo "docker push $REGISTRY/$1:$2"
    docker push $REGISTRY/$1:$2
  fi
}

docker_login() {
  if [ "$PUSH" = "true" ] ; then
    echo "docker login $REG --username **** --password ****"
    docker login $REG --username $REG_USER --password $REG_PASS
  fi
}


docker_pull() {
  echo "docker pull $REGISTRY/$1:$2"
  docker pull $REGISTRY/$1:$2
}

docker_tag() {
  echo "docker tag $REGISTRY/$1:$2 $REGISTRY/$1:$3"
  docker tag $REGISTRY/$1:$2 $REGISTRY/$1:$3
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