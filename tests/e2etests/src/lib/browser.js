const puppeteer = require('puppeteer');
require('dotenv').config();

module.exports = {
    getNewWindow: function () {
        return new Promise((fulfill) => browser.once('targetcreated', (target) => fulfill(target.page())));
    },
    getBrowser: async function (modul) {
        browser = await puppeteer.launch({
            // set this to false for dev/debugging
            headless:process.env.TEST_MODE != 'debug',
            //ignoreDefaultArgs: ['--enable-automation'],
            //slowMo: 250,
            //defaultViewport: {width: 1366, height: 768},
            args: ['--lang=de-DE,de']
        });
        page = await browser.newPage();
        await page.setViewport({
            width: 1366,
            height: 768,
        });
        await page.goto(process.env.TEST_URL, {waitUntil: 'domcontentloaded'});
        await expect(page).toMatchElement('title', {text: 'Tine'});

        // rendering might take longer than 500ms -> toFill has no timeout option
        await page.waitForSelector('input[name=username]');
        await expect(page).toMatchElement('title', { text: process.env.TEST_BRANDING_TITLE });

        // rendering might take longer than 500ms -> toFill has no timeout option
        await expect(page).toMatchElement('input[name=username]');

        await expect(page).toFill('input[name=username]', process.env.TEST_USERNAME);
        await expect(page).toFill('input[name=password]', process.env.TEST_PASSWORD);
        await expect(page).toClick('button', { text: 'Anmelden' });


        await page.waitForNavigation();
        await expect(page).toClick('span', {text: process.env.TEST_BRANDING_TITLE});
        await page.waitFor(1000);
        await expect(page).toClick('.x-menu-item-text', {text: modul});
    }
};
