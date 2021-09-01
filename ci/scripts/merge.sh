cd $CI_BUILDS_DIR/${CI_PROJECT_NAMESPACE}/tine20/tine20

if ! $CI_BUILDS_DIR/tine20/buildscripts/githelpers/merge/auto_merge.sh; then
    ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/ci/scripts/send_matrix_message.sh $MATRIX_ROOM "Auto merge failed in $CI_JOB_URL."
    exit 1
fi