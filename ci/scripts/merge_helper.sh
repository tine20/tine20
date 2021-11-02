
#!/bin/bash
source ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/scripts/git/merge_helper.sh

update_custom_app () {
    if ! UpdateCustomApp $1 $2; then
        ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/ci/scripts/send_matrix_message.sh $MATRIX_ROOM "Auto updateing customapp $2 failed in $1 $CI_PIPELINE_NAME $CI_JOB_URL."
        exit 1
    fi
}

merge_upwards () {
    if ! MergeUpwards $1 $2 customers; then
        ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/ci/scripts/send_matrix_message.sh $MATRIX_ROOM "Auto mergeing $1 into $2 failed in $CI_PIPELINE_NAME $CI_JOB_URL."
        exit 1
    fi
}