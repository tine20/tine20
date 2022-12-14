#  -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -
FROM node:12.22-alpine as jsdependency
ARG TINE20ROOT=/usr/share

RUN apk add git

COPY tine20/Tinebase/js/package.json ${TINE20ROOT}/tine20/Tinebase/js/package.json
COPY tine20/Tinebase/js/npm-shrinkwrap.json ${TINE20ROOT}/tine20/Tinebase/js/npm-shrinkwrap.json

WORKDIR ${TINE20ROOT}/tine20/Tinebase/js

RUN npm --prefix ${TINE20ROOT}/tine20/Tinebase/js/ install --no-optional