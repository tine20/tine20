packaging_build_packages() {
    version=$1
    release=$2

    CI_COMMIT_REF_NAME_ESCAPED=$(echo ${CI_COMMIT_REF_NAME} | sed sI/I-Ig)
    MAJOR_COMMIT_REF_NAME_ESCAPED=$(echo ${MAJOR_COMMIT_REF_NAME} | sed sI/I-Ig)

    CACHE_IMAGE="${REGISTRY}/packages:${CI_COMMIT_REF_NAME_ESCAPED}-${PHP_VERSION}${IMAGE_TAG_PLATFORM_POSTFIX}"
    MAJOR_CACHE_IMAGE="${REGISTRY}/packages:${MAJOR_COMMIT_REF_NAME_ESCAPED}-${PHP_VERSION}${IMAGE_TAG_PLATFORM_POSTFIX}"

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
    export BUILT_IMAGE="${REGISTRY}/built-commit:${IMAGE_TAG}"
    export REVISION=0
    export CODENAME="${CODENAME}"
    export VERSION=$version
    export RELEASE=$release

    cd ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20
    # create archives
    if ! ./ci/dockerimage/make.sh -o "${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/packages.tar" -c "${CACHE_IMAGE}" -c "${MAJOR_CACHE_IMAGE}" packages; then
        return 1
    fi

    # add current.map
    if ! echo "$version" | grep "nightly"; then
        echo "currentPackage ${RELEASE}/tine20-allinone_${RELEASE}.tar.bz2" >> current.map
        tar -rf "${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/packages.tar" current.map
    else
        echo "nightly, do not set curren.map"
    fi
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
        "${CI_API_V4_URL}/projects/${CI_PROJECT_ID}/packages/generic/${customer}/${version}/$f"
    done
}

packaging_gitlab_set_ci_id_link() {
    version=$1
    customer=$(repo_get_customer_for_branch ${MAJOR_COMMIT_REF_NAME})

    if ! curl \
        --header "JOB-TOKEN: ${CI_JOB_TOKEN}" \
        -XPUT --data "${version}" \
        "${CI_API_V4_URL}/projects/${CI_PROJECT_ID}/packages/generic/${customer}/links/${CI_PIPELINE_ID}"
    then
        return 1
    fi
}

packaging_gitlab_get_version_for_pipeline_id() {
    customer=$1

    if ! curl \
        --fail \
        --header "JOB-TOKEN: ${CI_JOB_TOKEN}" \
        "${CI_API_V4_URL}/projects/${CI_PROJECT_ID}/packages/generic/${customer}/links/${CI_PIPELINE_ID}"
    then
        return 1
    fi
}

packaging_gitlab_set_current_link() {
    customer=$(repo_get_customer_for_branch ${MAJOR_COMMIT_REF_NAME})
    version=${CI_COMMIT_TAG:-$(packaging_gitlab_get_version_for_pipeline_id ${customer})}

    if echo "$version" | grep "nightly"; then
        echo "skip setting current link for nightly packages: $version"
        return 0
    fi

    curl \
        --header "JOB-TOKEN: ${CI_JOB_TOKEN}" \
        -XPUT --data "${version}" \
        "${CI_API_V4_URL}/projects/${CI_PROJECT_ID}/packages/generic/${customer}/links/current"

    matrix_send_message $MATRIX_ROOM "🟢 Package for ${version} is ready."
    if [ "${MAJOR_COMMIT_REF_NAME}" == "${CI_DEFAULT_BRANCH}" ]; then
        matrix_send_message "!gGPNgDOyMWwSPjFFXa:matrix.org" 'We just released the new version "${CODENAME}" ${version} 🎉\nCheck https://www.tine-groupware.de/ and https://packages.tine20.com/maintenance for more information and the downloads.\nYou can also pull the image from dockerhub: https://hub.docker.com/r/tinegroupware/tine'
    fi
}

packaging_push_package_to_github() {
    if [ "$MAJOR_COMMIT_REF_NAME" != "main" ]; then
        echo "skip pushing to github: $MAJOR_COMMIT_REF_NAME"
        return 0
    fi

    customer=$(repo_get_customer_for_branch ${MAJOR_COMMIT_REF_NAME})
    version=${CI_COMMIT_TAG:-$(packaging_gitlab_get_version_for_pipeline_id ${customer})}
    release=$(echo ${version} | sed sI-I~Ig)

    cd ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/
    asset_name="tine-$(date '+%Y.%m.%d')-$(git rev-parse --short HEAD)-nightly"

    curl "${CI_API_V4_URL}/projects/${CI_PROJECT_ID}/packages/generic/${customer}/${version}/tine20-allinone_${release}.tar.bz2" -o "${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/tine20-allinone_${release}.tar.bz2"

    release_json=$(github_create_release "$GITHUB_RELEASE_REPO_OWNER" "$GITHUB_RELEASE_REPO" "$version" "$GITHUB_RELEASE_USER" "$GITHUB_RELEASE_TOKEN")
    if [ "$?" != "0" ]; then
        echo "$release_json"
        return 1
    fi

    echo "$release"

    github_release_add_asset "$release_json" "$asset_name" "${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/tine20-allinone_${release}.tar.bz2" "$GITHUB_RELEASE_USER" "$GITHUB_RELEASE_TOKEN"

   matrix_send_message $MATRIX_ROOM "🟢 Packages for ${version} have been released to github."
}

packaging_push_to_vpackages() {
    customer=$(repo_get_customer_for_branch ${MAJOR_COMMIT_REF_NAME})
    version=${CI_COMMIT_TAG:-$(packaging_gitlab_get_version_for_pipeline_id ${customer})}
    release=$(echo ${version} | sed sI-I~Ig)

    echo "publishing ${release} (${version}) for ${customer} from ${CI_API_V4_URL}/projects/${CI_PROJECT_ID}/packages/generic/${customer}/${version}/all.tar"

    if ! ssh ${VPACKAGES_SSH_URL} -o StrictHostKeyChecking=no -C  "sudo -u www-data curl ${CI_API_V4_URL}/projects/${CI_PROJECT_ID}/packages/generic/${customer}/${version}/all.tar -o /tmp/${release}-source-${customer}.tar"; then
        echo "Failed to download packages to vpackages"
        return 1
    fi

    if ! ssh ${VPACKAGES_SSH_URL} -o StrictHostKeyChecking=no -C  "sudo -u www-data /srv/packages.tine20.com/www/scripts/importTine20Repo.sh /tmp/${release}-source-${customer}.tar; sudo -u www-data rm -f /tmp/${release}-source-${customer}.tar"; then
        echo "Failed to import package to repo"
        return 1
    fi
}

packaging() {
    version=${CI_COMMIT_TAG:-nightly-$(date '+%Y.%m.%d.%H.%M')}
    release=${version}

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

    echo "pushing packages to gitlab ..."
    if ! packaging_push_packages_to_gitlab $version $release; then
        echo "Failed to push to gitlab."
        return 1
    fi

    echo "setting ci pipeline id link"
    if ! packaging_gitlab_set_ci_id_link $version; then
        echo "Failed to set ci pipeline id link."
        return 1
    fi
}



