github_get_release_by_tag() {
    owner=$1
    repo=$2
    tag=$3

    curl -s \
        -H "accept: application/vnd.github.v3+json" \
        "https://api.github.com/repos/$owner/$repo/releases/tags/$tag"
}

github_create_release() {
    owner=$1
    repo=$2
    tag=$3
    user=$4
    token=$5

    body="$(github_create_release_body $owner $repo $tag | jq -Rs .)"

    curl -s \
        -X POST \
        -u "$user:$token" \
        -H "accept: application/vnd.github.v3+json" \
        "https://api.github.com/repos/$owner/$repo/releases" \
        -d '{"tag_name":"'"$tag"'", "body":'"$body"'}'
}

github_create_release_body() {
    owner=$1
    repo=$2
    tag=$3

    previous_tag=$(github_get_latest_release_tag_name "$owner" "$repo")

    git fetch origin "$previous_tag"

    if ! git log "$tag...$previous_tag"; then
        git fetch origin --unshallow --quiet
    fi

    repo_release_notes "$tag" "$previous_tag"
}

github_release_add_asset() {
    release_json=$1
    name=$2
    path_to_asset=$3
    user=$4
    token=$5

    upload_url=$(echo $release_json | jq -r '.upload_url')
    upload_url="${upload_url%\{*}"

    curl -s \
        -X POST \
        -u "$user:$token" \
        -T "$path_to_asset" \
        -H "accept: application/vnd.github.v3+json" \
        -H "content-type: $(file -b --mime-type $path_to_asset)" \
        "$upload_url?name=$name.tar.gz"
}

github_get_latest_release_tag_name() {
    owner=$1
    repo=$2

    curl https://api.github.com/repos/$1/$2/releases | jq -r '.[0].tag_name'
}