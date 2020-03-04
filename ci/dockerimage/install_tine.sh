#!/usr/bin/env bash

if [ "${TINE20_INSTALL,,}" != "true" ]; then exit; fi

/usr/local/bin/php /wait_for_db.php
cd /tine/tine20/ && php setup.php --install --config /etc/tine20/config.inc.php -- \
  adminLoginName="${TINE20_LOGIN_USERNAME:-tine20admin}" \
  adminPassword="${TINE20_LOGIN_PASSWORD:-tine20admin}" \
  adminEmailAddress="${TINE20_ADMIN_EMAIL:-tine20admin@example.org}" \
  acceptedTermsVersion=1000 \
  imap="${TINE20_EMAIL_IMAP}" \
  smtp="${TINE20_EMAIL_SMTP}" \
  sieve="${TINE20_EMAIL_SIEVE}" \
  authentication="${TINE20_AUTHENTICATION}" \
  accounts="${TINE20_ACCOUNTS}"