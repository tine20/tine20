FROM node:15.5.1-buster-slim

RUN apt-get update &&\
    apt-get install -y gconf-service libasound2 libatk1.0-0 libatk-bridge2.0-0 libc6 libcairo2 libcups2 libdbus-1-3 \
                       libexpat1 libfontconfig1 libgcc1 libgconf-2-4 libgdk-pixbuf2.0-0 libglib2.0-0 libgtk-3-0 \
                       libnspr4 libpango-1.0-0 libpangocairo-1.0-0 libstdc++6 libx11-6 libx11-xcb1 libxcb1 \
                       libxcomposite1 libxcursor1 libxdamage1 libxext6 libxfixes3 libxi6 libxrandr2 libxrender1 \
                       libxss1 libxtst6 ca-certificates fonts-liberation libappindicator1 libnss3 lsb-release \
                       xdg-utils libgbm-dev wget jq curl php-cli php-mbstring git unzip && \
    rm -rf /var/lib/apt/lists/*

RUN curl -sS https://getcomposer.org/installer -o composer-setup.php
RUN php composer-setup.php --install-dir=/usr/local/bin --filename=composer

COPY tests/e2etests/package.json /root/package.json
COPY tests/e2etests/package-lock.json /root/package-lock.json

WORKDIR /root

RUN npm install

WORKDIR /e2etests
