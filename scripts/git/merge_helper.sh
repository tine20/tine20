#!/bin/bash
# by PS, 2015-03-09
# 
# example: MergeUpwards 2014.11-develop 2015.07 gerrit || exit 1
#  -> merges 2014.11-develop into 2015.07 and pushes 2015.07 to gerrit repo



# checkout/push/pull
MergeUpwards () {
    MYBASEPATH=$(dirname $0)
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

    # exit if branches are alredy merged, otherwise the composer.lock file from the previus merge request would rewritten.
    if [ $(git merge-base $srcBranch $dstBranch) =  $(git rev-parse $srcBranch) ]; then
        echo "$dstBranch is all ready up to date"
        return
    fi

    git merge --no-edit --rerere-autoupdate $srcBranch
    RETVAL=$?
    if [ $RETVAL -ne 0 ]; then

        php $MYBASEPATH/repairMerge.php $srcBranch $dstBranch
        RETVAL=$?
        if [ $RETVAL -ne 0 ]; then
            echo "git merge $srcBranch failed!"
            return $RETVAL
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
                if [[ $pushOptSkipCi ]]; then
                    git push -o ci.skip $remote $dstBranch:$remoteDstBranch
                else 
                    git push $remote $dstBranch:$remoteDstBranch
                fi
            else
                echo "pushing $remote $dstBranch ..."
                if [[ $pushOptSkipCi ]]; then
                    git push -o ci.skip $remote $dstBranch
                else 
                    git push $remote $dstBranch
                fi
            fi
            break
        fi

        if [[ $key = q ]]; then
            return
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
    composer update --ignore-platform-reqs ${app} || return 1
    git commit -a -m "$(printf "updated custom app ${app}\n\nexecute composer:\ncomposer update --ignore-platform-reqs ${app}")" && git push customers ${branch}
}

if [ "${BASH_SOURCE[0]}" -ef "$0" ]; then
    case $1 in
        MergeUpwards)
            if (( "$#" < 4 )); then
                echo "usage $0 MergeUpwards <srcBranch> <dstBranch> <remote> [<remoteDstBranch>]"
                exit 1
            fi

            MergeUpwards $2 $3 $4
            exit $?
            ;;
        UpdateCustomApp)
            if (( "$#" != 3 )); then
                echo "usage $0 UpdateCustomApp <branch> <app>"
                exit 1
            fi

            UpdateCustomApp $2 $3
            exit $?
            ;;
        *)
            echo "usage $0 <MergeUpwards|UpdateCustomApp> <args>"
            exit 1
            ;;
    esac
fi
