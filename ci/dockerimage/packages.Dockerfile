ARG PACKAGING_IMAGE=packaging

FROM ${PACKAGING_IMAGE} as packaging

FROM scratch as packages
ARG TINE20PACKAGES=/root/packages/source

COPY --from=packaging ${TINE20PACKAGES} /
