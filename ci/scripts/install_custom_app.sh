#!/bin/sh

echo $0 installing ...

if test -n "${CUSTOM_APP_NAME}"; then
	apk add jq;
	echo composer config "repositories.${CUSTOM_APP_VENDOR}/${CUSTOM_APP_NAME}" git "${CUSTOM_APP_GIT_URL}";
	composer config "repositories.${CUSTOM_APP_VENDOR}/${CUSTOM_APP_NAME}" git "${CUSTOM_APP_GIT_URL}";
	echo composer require "${CUSTOM_APP_VENDOR}/${CUSTOM_APP_NAME}" "${CUSTOM_APP_VERSION}";
	composer require "${CUSTOM_APP_VENDOR}/${CUSTOM_APP_NAME}" "${CUSTOM_APP_VERSION}";
fi

echo $0 ... done
