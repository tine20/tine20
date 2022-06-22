packaging_build_packages() {
    version=$1
    release=$2

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
    export REVISION=0
    export CODENAME="${CODENAME}"
    export VERSION=$version
    export RELEASE=$release

    cd ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20
    # create archives
    ./ci/dockerimage/make.sh -o "${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/packages.tar" -c "${CACHE_IMAGE}" -c "${MAJOR_CACHE_IMAGE}" packages

    # add current.map
    echo "currentPackage ${RELEASE}/tine20-allinone_${RELEASE}.tar.bz2" >> current.map
    tar -rf "${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/packages.tar" current.map
}

packaging_extract_all_package_tar() {
    cd "${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/"
    tar -xf "${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/packages.tar"
}

packaging_push_packages_to_gitlab() {
    version=$1
    release=$2

    customer=$(repo_get_customer_for_branch ${MAJOR_COMMIT_REF_NAME})

    curl \
        --header "JOB-TOKEN: ${CI_JOB_TOKEN}" \
        --upload-file "${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/packages.tar" \
        "${CI_API_V4_URL}/projects/${CI_PROJECT_ID}/packages/generic/${customer}/${version}/all.tar"
    echo "published packages to ${CI_API_V4_URL}/projects/${CI_PROJECT_ID}/packages/generic/${customer}/${version}/all.tar"

    cd "${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/${release}/"

    for f in *; do
        curl \
        --header "JOB-TOKEN: ${CI_JOB_TOKEN}" \
        --upload-file "$f" \
        "${CI_API_V4_URL}/projects/${CI_PROJECT_ID}/packages/generic/${customer}/${version}/$(echo "$f" | sed sI~I-Ig)"
    done
}

packaging_gitlab_set_current_link() {
    version=$1

    curl \
        --header "JOB-TOKEN: ${CI_JOB_TOKEN}" \
        -XPUT --data "${version}" \
        "${CI_API_V4_URL}/projects/${CI_PROJECT_ID}/packages/generic/${customer}/links/current"
}

packaging_push_package_to_github() {
    version=$1
    release=$2

    cd ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/
    asset_name="tine-$(date '+%Y.%m.%d')-$(git rev-parse --short HEAD)-nightly"

    release_json=$(github_create_release "$GITHUB_RELEASE_REPO_OWNER" "$GITHUB_RELEASE_REPO" "$version" "$GITHUB_RELEASE_USER" "$GITHUB_RELEASE_TOKEN")
    if [ "$?" != "0" ]; then
        echo "$release_json"
        return 1
    fi

    echo "$release"

    github_release_add_asset "$release_json" "$asset_name" "${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/${release}/tine20-allinone_${release}.tar.bz2" "$GITHUB_RELEASE_USER" "$GITHUB_RELEASE_TOKEN"
}

packaging() {
    version=${CI_COMMIT_TAG:-nightly-$(date '+%Y.%m.%d.%H.%M')}
    release=$(echo "${version}" | sed sI-I~Ig)

    if ! repo_get_customer_for_branch ${MAJOR_COMMIT_REF_NAME}; then
        echo "No packages are build for major_commit_ref: $MAJOR_COMMIT_REF_NAME for version: $version"
        return 1
    fi

    echo "building packages ..."
    if ! packaging_build_packages $version $release; then
        echo "Failed to build packages."
        return 1
    fi

    if ! packaging_extract_all_package_tar; then
        echo "Failed to extract tar archive."
        return 1
    fi

    if [ "$MAJOR_COMMIT_REF_NAME" == "main" ]; then
        echo "pushing packages to github ..."
        if ! packaging_push_package_to_github $version $release; then
            echo "Failed to push to github."
            return 1
        fi
    else
        echo "pushing packages to gitlab ..."
        if ! packaging_push_packages_to_gitlab $version $release; then
            echo "Failed to push to gitlab."
            return 1
        fi

        echo "setting current link"
        if ! echo "$version" | grep "nightly"; then
            if ! packaging_gitlab_set_current_link $version; then
                echo "Failed to set current link."
                return 1
            fi
        fi
    fi

    ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/ci/scripts/send_matrix_message.sh $MATRIX_ROOM "🟢 Package build for ${version} finished."
}



