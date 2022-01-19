repo_get_customer_for_branch () {
    branch=$1

    if [ "${branch}" == "main" ]; then
        echo main
        return
    fi

    if echo "${branch}" | grep -Eq '(pu/|feat/|change/)'; then
        return 1
    fi

    if ! echo "${branch}" | grep -q '/'; then
        if ! echo "${branch}" | grep -Eq '20..\.11'; then
                return 1
        fi

        echo tine20.org
        return
    else
        if [ $(echo "${branch}" | awk -F"/" '{print NF-1}') != 1 ]; then
                return 1
        fi

        echo "${branch}" | cut -d '/' -f1
        return
    fi
}

repo_release_notes() {
    tag=$1
    previous_tag=$2

    echo '# Releasenotes'
    echo '# Changelog'
    ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/scripts/git/changelog.sh "$tag" "$previous_tag"
}