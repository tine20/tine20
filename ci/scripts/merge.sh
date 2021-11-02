#! /bin/bash
notify_and_exit () {
    ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/ci/scripts/send_matrix_message.sh $MATRIX_ROOM "Auto mergeing $1 into $2 failed in $CI_PIPELINE_NAME $CI_JOB_URL."
    exit 1
}

source ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/scripts/git/merge_helper.sh

cd ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/tine20

git config merge.pofile.name "merge po-files driver"
git config merge.pofile.driver "./scripts/git/merge-po-files %A %O %B"
git config merge.pofile.recursive "binary"

MergeUpwards 2019.11 2020.11 customers || notify_and_exit 2019.11 2020.11
MergeUpwards 2020.11 2021.11 customers || notify_and_exit 2020.11 2021.11
MergeUpwards 2021.11 2022.11 customers || notify_and_exit 2021.11 2022.11