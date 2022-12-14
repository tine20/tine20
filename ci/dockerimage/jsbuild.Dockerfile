ARG SOURCE_IMAGE=source
ARG JSDEPENDENCY_IMAGE=jsdependency

FROM ${SOURCE_IMAGE} AS source-copy
FROM ${JSDEPENDENCY_IMAGE} AS jsdependency-copy 

#  -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -
FROM node:12.22-alpine as jsbuild
ARG TINE20ROOT=/usr/share
WORKDIR ${TINE20ROOT}/tine20/Tinebase/js

COPY --from=jsdependency-copy ${TINE20ROOT}/tine20/Tinebase/js/node_modules ${TINE20ROOT}/tine20/Tinebase/js/node_modules
COPY --from=source-copy ${TINE20ROOT}/tine20 ${TINE20ROOT}/tine20

RUN export BUILD_DATE=$(date -u "+%F %T") && BUILD_REVISION=${REVISION} node --max_old_space_size=4096 ./node_modules/webpack/bin/webpack.js --progress --config webpack.docker.js