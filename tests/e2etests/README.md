# dependencies

nodejs and puppeteer. run this on an ubuntu system:

nodejs

    $ sudo apt install npm
    $ sudo npm i -g n
    $ sudo n lts
    
puppeteer deps

    $ sudo apt install gconf-service libasound2 libatk1.0-0 libatk-bridge2.0-0 libc6 libcairo2 libcups2 libdbus-1-3 libexpat1 libfontconfig1 libgcc1 libgconf-2-4 libgdk-pixbuf2.0-0 libglib2.0-0 libgtk-3-0 libnspr4 libpango-1.0-0 libpangocairo-1.0-0 libstdc++6 libx11-6 libx11-xcb1 libxcb1 libxcomposite1 libxcursor1 libxdamage1 libxext6 libxfixes3 libxi6 libxrandr2 libxrender1 libxss1 libxtst6 ca-certificates fonts-liberation libappindicator1 libnss3 lsb-release xdg-utils wget

see https://github.com/GoogleChrome/puppeteer/blob/master/docs/troubleshooting.md#chrome-headless-doesnt-launch-on-unix

# config

config via .env file or env variables

    export TEST_URL=http://localhost/tine20
    export TEST_USERNAME=test
    export TEST_PASSWORD=test
    export TEST_MODE=headless
    export TEST_BRANDING_TITLE="Tine 2.0"
    export TEST_WORKER=1
    export TEST_TIMEOUT=30000
    
TEST_WORKER doesn´t work

# run it

timeout command is needed, because node does not exist on some failures

    npm install
    npm test -> for all test
    npm test src/test/Addressbook/Addressbook.test.js -> all test in file Addressbook.test.js


