#!/bin/sh
set -e

user=$1
password=$2
registry=$3
old_repo=$4
old_tag=$5
new_repo=$6
new_tag=$7

if ! curl -s -f --user $user:$password -H "accept: application/vnd.docker.distribution.manifest.v2+json" https://$registry/v2/$old_repo/manifests/$old_tag > /dev/null; then
	curl -s --user $user:$password -H "accept: application/vnd.docker.distribution.manifest.v2+json" https://$registry/v2/$old_repo/manifests/$old_tag;
	exit 1;
fi

manifest=$(curl -s -f -X GET --user $user:$password -H "accept: application/vnd.docker.distribution.manifest.v2+json"  https://$registry/v2/$old_repo/manifests/$old_tag)

for digest in $(echo $manifest | jq -r '.layers[].digest'); do
	curl -s -f -X POST --user $user:$password "https://$registry/v2/$new_repo/blobs/uploads/?mount=$digest&from=$old_repo"
done

curl -s -f -X POST --user $user:$password "https://$registry/v2/$new_repo/blobs/uploads/?mount=$(echo $manifest | jq -r '.config.digest')&from=$old_repo"
curl -s -f -X PUT --user $user:$password -H "Content-Type: application/vnd.docker.distribution.manifest.v2+json" --data "$manifest" https://$registry/v2/$new_repo/manifests/$new_tag
