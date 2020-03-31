const expect = require('expect-puppeteer');
const lib = require('../../lib/browser');
require('dotenv').config();

beforeAll(async () => {
    await lib.getBrowser('E-Mail');
});

describe('message', () => {
    let popupWindow;
    test('compose message with attachment', async () => {
        popupWindow = await lib.getEditDialog('Verfassen');
        let currentUser = await lib.getCurrentUser(popupWindow);
        // add recipient
        let inputFields = await popupWindow.$$('input');
        await inputFields[2].type(currentUser.accountEmailAddress);
        await popupWindow.waitForSelector('.search-item.x-combo-selected');
        await popupWindow.click('.search-item.x-combo-selected');
        await popupWindow.waitFor(500); //wait for new mail line!
        await popupWindow.click('input[name=subject]');
        await popupWindow.waitFor(500); //musst wait for input!
        await expect(popupWindow).toFill('input[name=subject]', 'message with attachment');

        await expect(popupWindow).toClick('button',{text: 'Datei hinzufügen'});
        let fileToUpload = 'src/test/Felamimail/attachment.txt';
        await popupWindow.waitForSelector('input[type=file]');
        const inputUploadHandle = await popupWindow.$('input[type=file]');
        await inputUploadHandle.uploadFile(fileToUpload);

        await expect(popupWindow).toMatchElement('.x-grid3-cell-inner.x-grid3-col-name', {text:'attachment.txt'});

        // send message
        await expect(popupWindow).toClick('button', {text: 'Senden'});
    });

    let newMail;
    test('fetch messages', async () => {
        await page.click('.t-app-felamimail .x-btn-image.x-tbar-loading');
        try{
          await page.waitForSelector('.flag_unread',{timeout: 10000});
        } catch(e){
          await page.click('.t-app-felamimail .x-btn-image.x-tbar-loading');
          await page.waitForSelector('.flag_unread',{timeout: 10000});
        }
        newMail = await expect(page).toMatchElement('.x-grid3-cell-inner.x-grid3-col-subject', {text: 'message with attachment'});
        await newMail.click();
    });

    test('details panel', async () => {
        await page.waitForSelector('.preview-panel-felamimail');
    });

    let attachement;
    test('download attachments', async () => {
        newMail.click({clickCount: 2});
        popupWindow = await lib.getNewWindow();
        //await popupWindow.waitForSelector('.ext-el-mask');
        await popupWindow.waitFor(() => !document.querySelector('.ext-el-mask'));
        await popupWindow.waitForSelector('.tinebase-download-link');
        attachement = await popupWindow.$$('.tinebase-download-link');
        await attachement[1].hover();
        await attachement[1].click('tinebase-download-link-wait');

        let file = await lib.download(popupWindow, '.x-menu-item-text', {text:'Herunterladen'});

        if(!file.includes('attachment')) {
            throw new Error('download of attachments failed!');
        }
    });

    test('file attachment', async () => {
        await popupWindow.waitForSelector('.tinebase-download-link');
        attachement = await popupWindow.$$('.tinebase-download-link');
        await attachement[1].hover();
        await attachement[1].click('tinebase-download-link-wait');
        await expect(popupWindow).toClick('.x-menu-item-text',
            {text: new RegExp('Datei.*'), visible: true});
        await popupWindow.waitForSelector('.x-grid3-row.x-grid3-row-first');
        await popupWindow.click('.x-grid3-row.x-grid3-row-first');
        await expect(popupWindow).toClick('button', {text: 'Ok'});
    });

    test('attachment file in filemanager', async () => {
        await expect(page).toClick('span', {text: process.env.TEST_BRANDING_TITLE});
        await expect(page).toClick('.x-menu-item-text', {text: 'Dateimanager'});
        await page.waitForSelector('.x-grid3-cell-inner.x-grid3-col-name', {text: 'Tine 2.0 Admin Account\'s personal files'});
        await expect(page).toClick('.x-grid3-cell-inner.x-grid3-col-name', {text: 'Tine 2.0 Admin Account\'s personal files', clickCount: 2});
        await page.waitForSelector('.x-grid3-cell-inner.x-grid3-col-name', {text: 'attachment.txt'});
    });
});

describe('email note preference', () => {
    test('open Felamimail settings and set note=yes', async () => {
        await expect(page).toClick('span', {text: process.env.TEST_BRANDING_TITLE});
        await expect(page).toClick('.x-menu-item-text', {text: 'E-Mail'});
        await page.waitFor(2000);
        await lib.setPreference(page,'E-Mail', 'autoAttachNote', 'ja');
    });
    test('open compose dialog and check button pressed', async () => {
        await page.waitFor(2000);
        let popupWindow = await lib.getEditDialog('Verfassen');
        await popupWindow.waitForSelector('.x-btn.x-btn-text-icon.x-btn-pressed');
        await popupWindow.close();
    });
    test('open Felamimail settings and set note=no', async () => {
        await page.waitFor(2000);
        await lib.setPreference(page,'E-Mail', 'autoAttachNote', 'nein');
    });
    test('open editDialog and check button unpressed', async () => {
        await page.waitFor(2000);
        let popupWindow = await lib.getEditDialog('Verfassen');
        if (await popupWindow.$('.x-btn.x-btn-text-icon.x-btn-pressed') !== null) return Promise.reject('Error: The button is pressed');
        await popupWindow.close();
    });
});

afterAll(async () => {
    browser.close();
});
