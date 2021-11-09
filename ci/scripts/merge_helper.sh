#!/bin/bash
# by PS, 2015-03-09
# 
# example: MergeUpwards 2014.11-develop 2015.07 gerrit
#  -> merges 2014.11-develop into 2015.07 and pushes 2015.07 to gerrit repo



# checkout/push/pull
MergeUpwards () {
    MYBASEPATH=${CI_BUILDS_DIR:-$(dirname $0)}
    srcBranch=$1
    dstBranch=$2
    remote=$3
    # optional param
    remoteDstBranch=$4

    echo -e "\e[1m\e[32m***\e[0m merge $srcBranch -> $dstBranch ... "
    
    git checkout $srcBranch
    git pull

    git checkout $dstBranch
    if [[ $remoteDstBranch ]]; then
        git pull $remote $remoteDstBranch
    else
        git pull $remote $dstBranch
    fi

    git merge --no-edit --rerere-autoupdate $srcBranch
    RETVAL=$?
    if [ $RETVAL -ne 0 ]; then

        php $MYBASEPATH/repairMerge.php $srcBranch $dstBranch
        RETVAL=$?
        if [ $RETVAL -ne 0 ]; then
            echo "git merge $srcBranch failed!"
            exit $RETVAL
        fi
    fi

    echo "press p to push / q to quit / s to skip"

    while true; do
        if [[ $(printenv|grep MERGENONINTERACTIVE) ]]; then
            key=p
        else
            read -t 1 -n 1 key
        fi

        if [[ $key = p ]]; then

            if [[ $remoteDstBranch ]]; then
                echo "pushing $remote $dstBranch:$remoteDstBranch ..."
                git push $remote $dstBranch:$remoteDstBranch
            else
                echo "pushing $remote $dstBranch ..."
                git push $remote $dstBranch
            fi
            break
        fi

        if [[ $key = q ]]; then
            exit
        fi

        if [[ $key = s ]]; then
            echo "kipping $remote $dstBranch"
            break
        fi
    done

    echo -e ""
}

# TODO allow multiple apps
UpdateCustomApp () {
    branch=$1
    app=$2
    echo -e "updating custom app $app"
    git checkout ${branch}
    git pull
    # install is required before update APP as install updates other "dev-master" APPs, too :(
    # TODO file bug report https://github.com/composer/composer/issues
    composer install
    echo -e "composer update --ignore-platform-reqs ${app}"
    composer update --ignore-platform-reqs ${app} || exit 1
    git commit -a -m "$(printf "updated custom app ${app}\n\nexecute composer:\ncomposer update --ignore-platform-reqs ${app}")" && git push customers ${branch}
}

update_custom_app () {
    if ! UpdateCustomApp $1 $2; then
        "${CI_BUILDS_DIR}"/"${CI_PROJECT_NAMESPACE}"/tine20/ci/scripts/send_matrix_message.sh "$MATRIX_ROOM" "Auto updating customapp $2 failed in $1 $CI_PIPELINE_NAME $CI_JOB_URL."
        exit 1
    fi
}

merge_upwards () {
    if ! MergeUpwards $1 $2 customers; then
        "${CI_BUILDS_DIR}"/"${CI_PROJECT_NAMESPACE}"/tine20/ci/scripts/send_matrix_message.sh "$MATRIX_ROOM" "Auto merging $1 into $2 failed in $CI_PIPELINE_NAME $CI_JOB_URL."
        exit 1
    fi
}
