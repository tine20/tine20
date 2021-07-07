#/bin/sh
set -e

branch=$1

cd ${CI_BUILDS_DIR}/tine20/tine20
if echo "${branch}" | grep -Eq '(pu/|feat/|change/)'; then
	exit 1
fi

if ! echo "${branch}" | grep -q '/'; then
	if ! echo "${branch}" | grep -Eq '20..\.11'; then
    		exit 1
	fi

	echo tine20.org
	exit 0
else
	if [ $(echo "${branch}" | awk -F"/" '{print NF-1}') != 1 ]; then
    		exit 1
	fi

	echo "${branch}" | cut -d '/' -f0
	exit 0
fi
