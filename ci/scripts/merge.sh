#! /bin/bash
source ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/ci/scripts/merge_helper.sh

cd ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/tine20

git config merge.pofile.name "merge po-files driver"
git config merge.pofile.driver "./scripts/git/merge-po-files %A %O %B"
git config merge.pofile.recursive "binary"

merge_upwards 2019.11 2020.11
merge_upwards 2020.11 2021.11
merge_upwards 2021.11 2022.11