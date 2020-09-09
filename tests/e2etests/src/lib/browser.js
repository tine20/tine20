const puppeteer = require('puppeteer');
const expect = require('expect-puppeteer');
require('dotenv').config();

const fs = require('fs');
const mkdirp = require('mkdirp');
const path = require('path');
const uuid = require('uuid/v1');

module.exports = {
    download: async function (page, selector, option = {}) {
        const downloadPath = path.resolve(__dirname, 'download', uuid());
        mkdirp(downloadPath);
        console.log('Downloading file to:', downloadPath);
        await page._client.send('Page.setDownloadBehavior', {behavior: 'allow', downloadPath: downloadPath});
        await expect(page).toClick(selector, option);
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

    getEditDialog: async function (btnText, win) {
        await expect(win || page).toMatchElement('.x-btn-text', {text: btnText});
        await page.waitFor(100); // wait for btn to get active
        await expect(win || page).toClick('.x-btn-text', {text: btnText});
        let popupWindow = await this.getNewWindow();
        await popupWindow.waitForSelector('.ext-el-mask');
        await popupWindow.waitFor(() => !document.querySelector('.ext-el-mask'));
        await popupWindow.screenshot({path: 'screenshots/test.png'});
        return popupWindow;
    },

    getElement: async function (type, page, text) {
        return page.$x("//" + type + "[contains(., '" + text + "')]");
    },

    getCurrentUser: async function (page) {
        return page.evaluate(() => Tine.Tinebase.registry.get('currentAccount'));
    },

    reloadRegistry: async function (page) {
        page.evaluate(() => Tine.Tinebase.common.reload({
            clearCache: true
        }));
        await page.waitFor(1000);
        await page.waitForSelector('.x-btn-text.tine-grid-row-action-icon.renderer_accountUserIcon', 20000);
    },

    /**
     * TODO make this work / see tests/e2etests/src/test/Felamimail/grid.test.js:9 ('grid adopts to folder selected')
     *
     * @param page
     * @param selector
     * @param visible
     * @returns {Promise<unknown>}
     */
    checkDisplayOfElement: async function (page, selector, visible) {
        // TODO allow to pass selector to querySelector
        const el_display = await page.evaluate((selector) => document.querySelector(selector).style.display);
        if (visible && el_display === 'none') {
            return Promise.reject('Error: ' + selector + ' still visible');
        } else if (!visible && el_display !== 'none') {
            return Promise.reject('Error: ' + selector + ' still invisible');
        }

        return Promise.resolve();
    },

    /**
     * set tine20 preference and reload registry afterwards
     *
     * @param appName
     * @param preference
     * @param value
     * @returns {Promise<void>}
     */
    setPreference: async function (page, appName, preference, value) {
        console.log('setting preference ' + preference + ' of app '
            + appName + ' to "' + value + '"');

        await page.click('.x-btn-text.tine-grid-row-action-icon.renderer_accountUserIcon');
        await page.waitFor(2000);
        const frame = await expect(page).toMatchElement('.x-menu.x-menu-floating.x-layer', {visible: true});
        await expect(frame).toClick('.x-menu-item-icon.action_adminMode');
        const preferencePopup = await this.getNewWindow();
        await preferencePopup.waitFor(() => document.querySelector('.ext-el-mask'));
        await preferencePopup.waitFor(() => !document.querySelector('.ext-el-mask'));
        await preferencePopup.waitForSelector('.x-tree-node');
        await expect(preferencePopup).toClick('span', {text: appName});
        await preferencePopup.waitFor(1000);

        // change setting to YES
        await expect(preferencePopup).toMatchElement('input[name=' + preference + ']');
        await expect(preferencePopup).toFill('input[name=' + preference + ']', value);
        await preferencePopup.waitFor(500);
        await preferencePopup.keyboard.press('Enter');
        await preferencePopup.waitFor(500);
        await expect(preferencePopup).toClick('button', {text: 'Ok'});
        await page.waitFor(1000);

        await this.reloadRegistry(page);
        await page.waitForSelector('.x-tab-strip-closable.x-tab-with-icon.tine-mainscreen-apptabspanel-menu-tabel', {timeout: 0});
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
        await page.setDefaultTimeout(15000);
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
        await page.waitForFunction('document.activeElement === document.querySelector("input[name=username]")')
        await expect(page).toFill('input[name=username]', process.env.TEST_USERNAME);
        await expect(page).toFill('input[name=password]', process.env.TEST_PASSWORD);
        await expect(page).toClick('button', {text: 'Anmelden'});
        try {
            await page.waitForSelector('.x-tab-strip-closable.x-tab-with-icon.tine-mainscreen-apptabspanel-menu-tabel', {timeout: 0});
        } catch (e) {
            console.log('login failed!');
            console.log(app);
            console.error(e);
        }

        if (app) {
            await expect(page).toClick('span', {text: process.env.TEST_BRANDING_TITLE});
            await expect(page).toClick('.x-menu-item-text', {text: app});
        }
        if (module) {
            await expect(page).toClick('.tine-mainscreen-centerpanel-west span', {text: module});
        }
    },
};
