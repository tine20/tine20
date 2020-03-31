FROM docker:19.03.1
ARG AWS_ACCOUNT_ID
ARG AWS_REGION

RUN wget -O /usr/local/bin/docker-credential-ecr-login https://amazon-ecr-credential-helper-releases.s3.us-east-2.amazonaws.com/0.4.0/linux-amd64/docker-credential-ecr-login && \
    chmod +x /usr/local/bin/docker-credential-ecr-login && \
    mkdir ~/.docker/

RUN echo \{\"credHelpers\": \{\"${AWS_ACCOUNT_ID}.dkr.ecr.${AWS_REGION}.amazonaws.com\": \"ecr-login\"\}\} >> ~/.docker/config.json
