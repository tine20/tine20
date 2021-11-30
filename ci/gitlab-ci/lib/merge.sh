merge_merge_upwards () {
    if ! ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/scripts/git/merge_helper.sh MergeUpwards "$1" "$2" customers; then
        ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/ci/scripts/send_matrix_message.sh "$MATRIX_ROOM" "🔴 Auto merging $1 into $2 failed in $CI_PIPELINE_NAME $CI_JOB_URL."
        return 1
    fi
}

merge_update_custom_app () {
    ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/scripts/git/merge_helper.sh UpdateCustomApp "$1" "$2" || true
}