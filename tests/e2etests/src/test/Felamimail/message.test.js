const expect = require('expect-puppeteer');
const lib = require('../../lib/browser');
require('dotenv').config();

beforeAll(async () => {
    await lib.getBrowser('E-Mail');
    await page.waitForSelector('a span',{text: "Posteingang"});
    await expect(page).toClick('a span',{text: "Posteingang"});
    await page.waitForTimeout(2000);
});

describe('message', () => {
    let popupWindow;
    test('compose message with uploaded attachment', async () => {
        popupWindow = await lib.getEditDialog('Verfassen');
        let currentUser = await lib.getCurrentUser(popupWindow);
        // add recipient
        await popupWindow.waitForTimeout(2000);
        let inputFields = await popupWindow.$$('input');
        await inputFields[2].type(currentUser.accountEmailAddress);
        await popupWindow.waitForSelector('.search-item.x-combo-selected');
        await popupWindow.click('.search-item.x-combo-selected');
        await popupWindow.waitForTimeout(500); //wait for new mail line!
        await popupWindow.click('input[name=subject]');
        await popupWindow.waitForTimeout(500); //musst wait for input!
        await expect(popupWindow).toFill('input[name=subject]', 'message with attachment');

        const fileToUpload = 'src/test/Felamimail/attachment.txt';
        await expect(popupWindow).toClick('.x-btn-text', {text: 'Datei hinzufügen'});
        const filePickerWindow = await lib.getNewWindow();
        await expect(filePickerWindow).toClick('span',{text: 'Mein Gerät'});
        await filePickerWindow.waitForSelector('input[type=file]');
        const inputUploadHandle = await filePickerWindow.$('input[type=file]');
        await inputUploadHandle.uploadFile(fileToUpload);


        await popupWindow.waitForTimeout(2000);
        await expect(popupWindow).toMatchElement('.x-grid3-cell-inner.x-grid3-col-name', {text:'attachment.txt'});
        await popupWindow.waitForTimeout(2000); //musst wait for upload complete!
        
        // send message
        await expect(popupWindow).toClick('button', {text: 'Senden'});
    });

    // test('compose message with filemanager attachment', async () => {
    //     await expect(page).toClick('span', {text: process.env.TEST_BRANDING_TITLE});
    //     await expect(page).toClick('.x-menu-item-text', {text: 'Dateimanager'});
    //    
    //     // @TODO
    // });
    
    let newMail;
    test('fetch messages', async () => {
        await page.waitForTimeout(2000); //wait to close editDialog

        for(let i = 0; i < 10; i++) {
            await page.click('.t-app-felamimail .x-btn-image.x-tbar-loading');
            await page.waitForTimeout(500);
            try{
                await expect(page).toMatchElement('.x-grid3-cell-inner.x-grid3-col-subject', {text: 'message with attachment', timeout: 2000});
                break;
            } catch(e){
            }
        }

        await page.waitForTimeout(500);
        newMail = await expect(page).toMatchElement('.x-grid3-cell-inner.x-grid3-col-subject', {text: 'message with attachment'});
        await page.waitForTimeout(500);
        await newMail.click();
    });

    test('details panel', async () => {
        await page.waitForSelector('.preview-panel-felamimail');
    });

    test('contextMenu', async () => {
        await newMail.click({button: 'right'});
        await page.screenshot({path: 'screenshots/EMail/17_email_kontextmenu_email.png'});
        await page.keyboard.press('Escape')
    })

    let attachment;
    test.skip('download attachments', async () => {
        newMail.click({clickCount: 2});
        popupWindow = await lib.getNewWindow();
        //await popupWindow.waitForSelector('.ext-el-mask');
        await popupWindow.waitForFunction(() => !document.querySelector('.ext-el-mask'));
        await popupWindow.waitForSelector('.tinebase-download-link');
        attachment = await popupWindow.$$('.tinebase-download-link');
        await attachment[1].hover();
        await attachment[1].click('tinebase-download-link-wait');

        let file = await lib.download(popupWindow, '.x-menu-item-text', {text:'Herunterladen'});

        if(!file.includes('attachment')) {
            throw new Error('download of attachments failed!');
        }
    });

    test.skip('file attachment', async () => {
        await popupWindow.waitForSelector('.tinebase-download-link');
        attachment = await popupWindow.$$('.tinebase-download-link');
        await attachment[1].hover();
        await attachment[1].click('tinebase-download-link-wait');
        await expect(popupWindow).toClick('.x-menu-item-text',
            {text: new RegExp('Datei.*'), visible: true});
        await popupWindow.waitForSelector('.x-grid3-row.x-grid3-row-first');
        await popupWindow.click('.x-grid3-row.x-grid3-row-first');
        await expect(popupWindow).toClick('button', {text: 'Ok'});
        await popupWindow.close();
    });

    test.skip('attachment file in filemanager', async () => {
        await expect(page).toClick('span', {text: process.env.TEST_BRANDING_TITLE});
        await expect(page).toClick('.x-menu-item-text', {text: 'Dateimanager'});
        await page.waitForSelector('.x-grid3-cell-inner.x-grid3-col-name', {text: 'Persönliche Dateien von ' + process.env.TEST_USER});
        await expect(page).toClick('.x-grid3-cell-inner.x-grid3-col-name', {text: 'Persönliche Dateien von ' + process.env.TEST_USER, clickCount: 2});
        await page.waitForSelector('.x-grid3-cell-inner.x-grid3-col-name', {text: 'attachment.txt'});
    });
});

afterAll(async () => {
    browser.close();
});
