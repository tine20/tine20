#!/bin/bash
set -e

errors=$(curl --header "JOB-TOKEN: $CI_JOB_TOKEN" "$CI_API_V4_URL/projects/$CI_PROJECT_ID/pipelines/$CI_PIPELINE_ID/test_report" | jq -c '.test_suites[] | select((.error_count>0) or .failed_count>0)| {name,error_count,failed_count}')

ref=$(curl --header "JOB-TOKEN: $CI_JOB_TOKEN" "$CI_API_V4_URL/projects/$CI_PROJECT_ID/pipelines/$CI_PIPELINE_ID/" | jq -r .ref)

message="🔴 pipeline $CI_PIPELINE_NAME #$CI_PIPELINE_ID for $ref failed with:"

IFS=$'\n' 
for error in $errors; do
	n=$(echo $error | jq -r '.name')
	e=$(echo $error | jq -r '.error_count')
	f=$(echo $error | jq -r '.failed_count')
	message="$message"'\n'"+ $n  --  errors: $e failures: $f"
done

message="$message"'\n'"$CI_PIPELINE_URL"

echo $message

${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/ci/scripts/send_matrix_message.sh $MATRIX_ROOM "$message"
