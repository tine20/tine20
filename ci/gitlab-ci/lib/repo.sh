repo_get_customer_for_branch () {
    branch=$1

    cd ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20
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