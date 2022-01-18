git_repo_clone () {
    git clone ${CI_REPOSITORY_URL} --branch ${CI_COMMIT_REF_NAME} --depth 1 --single-branch ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20
}