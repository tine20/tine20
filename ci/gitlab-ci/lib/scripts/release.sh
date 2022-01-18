release_tag_main_if_needed() {
    if [ "$RELEASE_CE_TO_GITHUB" != "true" ]; then
        echo "'RELEASE_CE_TO_GITHUB=$RELEASE_CE_TO_GITHUB' => do not tag main."
        return
    fi

    last_release_tag=$(github_get_latest_release_tag_name "$GITHUB_RELEASE_REPO_OWNER" "$GITHUB_RELEASE_REPO")
    if [ $? != 0 ]; then
        return 1
    fi

    git fetch origin main || return 1

    commit_diff_count=$(git rev-list "$last_release_tag..origin/main" --count)
    if [ $? != 0 ]; then
        return 1
    fi

    echo "origin/main and $last_release_tag differ in $commit_diff_count commits"

    if [ $commit_diff_count = 0 ]; then
        echo "No difference, no new tag is created."
        return 0
    fi

    tag="$(date '+%Y.%m.%d.')$commit_diff_count"
    echo "tagging origin/main as $tag"

    if ! git tag $tag; then
        if [ "$(git rev-parse "$tag")" != "$(git rev-parse origin/main)" ]; then
            echo "tag $tag already exits, but it is ponting to a different commit."
            return 1
        fi

        echo "Tag $tag already exits, for this commit. Using it..."
    fi

    # "tag push" triggers tag pipeline which publishes the release
    git push origin $tag || return 1
    git push github $tag
}