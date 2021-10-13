ARG DEPENDENCY_IMAGE=dependency

#  -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -
# .git changes with every commit and broke cachin form source downwards. So it needs to be excluded and only the result is used
FROM ${DEPENDENCY_IMAGE} as icon-set-provider
ARG TINE20ROOT=/usr/share
COPY .git ${TINE20ROOT}/.git
RUN apk add --no-cache git
RUN git submodule update --init

#  -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -
FROM ${DEPENDENCY_IMAGE} as source
ARG TINE20ROOT=/usr/share

COPY tine20 ${TINE20ROOT}/tine20/
COPY tests ${TINE20ROOT}/tests/
COPY scripts ${TINE20ROOT}/scripts/
COPY .git ${TINE20ROOT}/.git

RUN if [ "COMPOSER_LOCK_REWRITE" == "true" ]; then \
        php ${TINE20ROOT}/scripts/packaging/composer/composerLockRewrite.php ${TINE20ROOT}/tine20/composer.lock satis.default.svc.cluster.local; \
    fi
RUN cd ${TINE20ROOT}/tine20 && composer install --no-ansi --no-progress --no-suggest

COPY --from=icon-set-provider ${TINE20ROOT}/tine20/images/icon-set/ ${TINE20ROOT}/tine20/images/icon-set
