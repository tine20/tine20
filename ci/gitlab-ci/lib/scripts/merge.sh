merge_merge_upwards () {
    if ! ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/scripts/git/merge_helper.sh MergeUpwards "$1" "$2" "customers"; then
        ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/ci/scripts/send_matrix_message.sh "$MATRIX_ROOM" "🔴 Auto merging $1 into $2 failed in $CI_PIPELINE_NAME $CI_JOB_URL."
        return 1
    fi
}

merge_update_custom_app () {
    ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/scripts/git/merge_helper.sh UpdateCustomApp "$1" "$2" || true
}

merge_merge_mirror () {
    source_remote="$1"
    source_branch="$2"
    destination_remote="$3"
    destination_branch="$4"

    git fetch "$source_remote" "$source_branch" || return 1
    git fetch "$destination_remote" "$destination_branch" || return 1

    if git rev-parse --quiet --verify $destination_branch > /dev/null; then
        git checkout "$destination_branch"
        git reset --hard "$destination_remote/$destination_branch"
    else
        git checkout --track "$destination_remote/$destination_branch" || return 1
    fi
    
    echo "git mergeing $source_remote/$source_branch into $destination_remote/$destination_branch ..."

    if ! git merge "$source_remote/$source_branch"; then

        if ! php ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/scripts/git/repairMerge.php "$source_remote/$source_branch" "$destination_branch"; then
            echo "merging $source_remote/$source_branch into $destination_remote/$destination_branch failed"
            return 1
        fi
    fi
    
    git push "$destination_remote" "$destination_branch"
}

merge_trigger_next () {
    MERGE_MAP=${MERGE_MAP:-"{}"}
    
    if ! echo $MERGE_MAP | jq --arg ref $CI_COMMIT_REF_NAME -e '.[$ref]' > /dev/null; then
        echo "nothing to trigger"
        return
    fi

    for i in $(echo $MERGE_MAP | jq -c --arg ref $CI_COMMIT_REF_NAME '.[$ref][]'); do
        ref=$(echo $i | jq -r '.ref')
        var=$(echo $i | jq -r '.var')

        echo "trigger $ref with $var:"

        curl -X POST -F token=$MERGE_TRIGGER_TOKEN \
            -F ref=$ref \
            -F "variables[$var]=true" \
            -F "variables[DOCKER_BUILD_SOURCE]=true" \
            -F "variables[SEND_PIPELINE_STATUS]=true" \
            "$CI_API_V4_URL/projects/$CI_PROJECT_ID/trigger/pipeline" > /dev/null
    done
}