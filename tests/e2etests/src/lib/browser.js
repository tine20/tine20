const puppeteer = require('puppeteer');
const expect = require('expect-puppeteer');
require('dotenv').config();


const fs = require('fs');
const mkdirp = require('mkdirp');
const path = require('path');
const uuid = require('uuid/v1');

module.exports = {
    download: async function (page, selector, option= {}) {
        const downloadPath = path.resolve(__dirname, 'download', uuid());
        mkdirp(downloadPath);
        console.log('Downloading file to:', downloadPath);
        await page._client.send('Page.setDownloadBehavior', { behavior: 'allow', downloadPath: downloadPath });
        await expect(page).toClick(selector,option);
        let filename = await this.waitForFileToDownload(downloadPath);
        return path.resolve(downloadPath, filename);
    },

     waitForFileToDownload: async function (downloadPath) {
        console.log('Waiting to download file...');
        let filename;
        while (!filename || filename.endsWith('.crdownload')) {
            filename = fs.readdirSync(downloadPath)[0];
            await page.waitFor(500);
        }
        return filename;
    },

    getNewWindow: async function () {
        return new Promise((fulfill) => browser.once('targetcreated', (target) => fulfill(target.page())));
    },
    getEditDialog: async function(btnText) {
        await expect(page).toClick('.x-btn-text', {text: btnText});
        let popupWindow = await this.getNewWindow();
        await popupWindow.waitForSelector('.ext-el-mask');
        await popupWindow.waitFor(() => !document.querySelector('.ext-el-mask'));
        await popupWindow.screenshot({path:'screenshots/test.png'});
        return popupWindow;
    },
    getElement: async function (type, page, text) {
        return page.$x("//" + type + "[contains(., '" + text + "')]");
    },
    getCurrentUser: async function (page) {
        return page.evaluate(() => Tine.Tinebase.registry.get('currentAccount'));
    },
    getBrowser: async function (app, module) {

        expect.setDefaultOptions({timeout: 5000});

        browser = await puppeteer.launch({
            // set this to false for dev/debugging
            headless: process.env.TEST_MODE != 'debug',
            //ignoreDefaultArgs: ['--enable-automation'],
            //slowMo: 250,
            //defaultViewport: {width: 1366, height: 768},
            args: ['--lang=de-DE,de']
        });
        page = await browser.newPage();
        page.setDefaultTimeout(10000);
        await page.setViewport({
            width: 1366,
            height: 768,
        });
        await page.goto(process.env.TEST_URL, {waitUntil: 'domcontentloaded'});
        await expect(page).toMatchElement('title', {text: process.env.TEST_BRANDING_TITLE});

        if (process.env.TEST_MODE !== 'headless' && process.env.TEST_BROWSER_LANGUAGE !== 'de') {
            console.log('switching to german');
            await page.waitForSelector('input[name=locale]');
            await page.click('input[name=locale]');
            await expect(page).toClick('.x-combo-list-item', {text: 'Deutsch [de]'});
            // wait for reload
            await page.waitFor(500);
            await page.waitForSelector('input[name=locale]');
        }

        await page.waitForSelector('input[name=username]');
        await expect(page).toMatchElement('title', {text: process.env.TEST_BRANDING_TITLE});
        await expect(page).toMatchElement('input[name=username]');
        await expect(page).toFill('input[name=username]', process.env.TEST_USERNAME);
        await expect(page).toFill('input[name=password]', process.env.TEST_PASSWORD);
        await expect(page).toClick('button', {text: 'Anmelden'});
        await page.waitForSelector('.x-tab-strip-closable.x-tab-with-icon.tine-mainscreen-apptabspanel-menu-tabel', {timeout: 0});

        if(app) {
            await expect(page).toClick('span', {text: process.env.TEST_BRANDING_TITLE});
            await expect(page).toClick('.x-menu-item-text', {text: app});
        }
        if (module) {
            await expect(page).toClick('.tine-mainscreen-centerpanel-west span', {text: module});
        }
    },
};
