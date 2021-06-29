#!/bin/bash
set -e

CI_COMMIT_REF_NAME_ESCAPED=$(echo ${CI_COMMIT_REF_NAME} | sed sI/I-Ig)
MAJOR_COMMIT_REF_NAME_ESCAPED=$(echo ${MAJOR_COMMIT_REF_NAME} | sed sI/I-Ig)

CACHE_IMAGE="${REGISTRY}/packages:${CI_COMMIT_REF_NAME_ESCAPED}-${PHP_VERSION}"
MAJOR_CACHE_IMAGE="${REGISTRY}/packages:${MAJOR_COMMIT_REF_NAME_ESCAPED}-${PHP_VERSION}"

if echo "$CI_COMMIT_TAG" | grep '/'; then
  echo "Error: CI_COMMIT_TAG must not contain a /"
  exit 1
fi

# config via env
export PHP_VERSION=${PHP_VERSION}
export BASE_IMAGE="${REGISTRY}/base-commit:${IMAGE_TAG}"
export DEPENDENCY_IMAGE="${REGISTRY}/dependency-commit:${IMAGE_TAG}"
export SOURCE_IMAGE="${REGISTRY}/source-commit:${IMAGE_TAG}"
export BUILD_IMAGE="${REGISTRY}/build-commit:${IMAGE_TAG}"
export BUILT_IMAGE="${REGISTRY}/build-commit:${IMAGE_TAG}"
export VERSION=${CI_COMMIT_TAG:-nightly}
export RELEASE=$(echo "${VERSION}" | sed sI-I~Ig)
export REVISION=0
export CODENAME="${CODENAME}"

cd ${CI_BUILDS_DIR}/tine20/tine20
# create archives
./ci/dockerimage/make.sh -o "${CI_BUILDS_DIR}/tine20/tine20/packages.tar" -c "${CACHE_IMAGE}" -c "${MAJOR_CACHE_IMAGE}" packages

# add current.map
echo "currentPackage ${RELEASE}/tine20-allinone_${RELEASE}.zip" >> current.map
tar -rf "${CI_BUILDS_DIR}/tine20/tine20/packages.tar" current.map

#push packages to gitlab
# gitlab 13.9 package version only allows semantic version not a tag. gitlab 14 allows our tag format, using package name as version instade
curl \
	--header "JOB-TOKEN: ${CI_JOB_TOKEN}" \
	--upload-file "${CI_BUILDS_DIR}/tine20/tine20/packages.tar" \
	"${CI_API_V4_URL}/projects/${CI_BUILDS_DIR}/tine20/tine20/packages/generic/${VERSION}/1.0.0/all.tar"
echo "published packages to ${CI_API_V4_URL}/projects/${CI_BUILDS_DIR}/tine20/tine20/packages/generic/${VERSION}/1.0.0/all.tar"

tar -xf "${CI_BUILDS_DIR}/tine20/tine20/packages.tar"

cd ${CI_BUILDS_DIR}/tine20/tine20/${RELEASE}/

for f in *; do
	curl \
	--header "JOB-TOKEN: ${CI_JOB_TOKEN}" \
	--upload-file "$f" \
	"${CI_API_V4_URL}/projects/${CI_PROJECT_ID}/packages/generic/${VERSION}/1.0.0/$(echo "$f" | sed sI~I-Ig)"
done