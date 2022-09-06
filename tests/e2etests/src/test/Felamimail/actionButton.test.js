const expect = require('expect-puppeteer');
const lib = require('../../lib/browser');
require('dotenv').config();
let subject;

beforeAll(async () => {
    await lib.getBrowser('E-Mail');
    await page.waitForSelector('a span',{text: "Posteingang"});
    await expect(page).toClick('a span',{text: "Posteingang"});
    await page.waitForTimeout(2000);
});

beforeEach(async () => {
    let popupWindow = await lib.getEditDialog('Verfassen');
    let currentUser = await lib.getCurrentUser(popupWindow);
    // add recipient
    let inputFields = await popupWindow.$$('input');
    await inputFields[2].type(currentUser.accountEmailAddress);
    await popupWindow.waitForSelector('.search-item.x-combo-selected');
    await popupWindow.click('.search-item.x-combo-selected');
    await popupWindow.waitForTimeout(1000); //wait for new mail line!
    await popupWindow.click('input[name=subject]');
    await popupWindow.waitForTimeout(1000); //musst wait for input!
    subject = 'test '+ Math.round(Math.random() * 10000000);
    await expect(popupWindow).toFill('input[name=subject]', subject);

    // send message
    await expect(popupWindow).toClick('button', {text: 'Senden'});

    await page.waitForTimeout(2000); //wait to close editDialog
    await page.click('.t-app-felamimail .x-btn-image.x-tbar-loading');
    try{
        await expect(page).toMatchElement('.x-grid3-cell-inner.x-grid3-col-subject', {text: subject, timeout: 2000});
    } catch(e){
        await page.click('.t-app-felamimail .x-btn-image.x-tbar-loading');
    }
    await expect(page).toMatchElement('.x-grid3-cell-inner.x-grid3-col-subject', {text: subject});
})

// skip... is to unstable
describe('test action button of felamimail (grid)', () => {
    test('delete email', async () => {
        await expect(page).toClick('.x-grid3-cell-inner.x-grid3-col-subject', {text: subject});
        await page.click(('.t-app-felamimail .x-toolbar-left-row .x-btn-image.action_delete'));

        await page.waitForSelector('a span',{text: "Mülleimer"});
        await expect(page).toClick('a span',{text: "Mülleimer"});
        await page.waitForTimeout(2000);
        await page.click('.t-app-felamimail .x-btn-image.x-tbar-loading');
        try{
            await expect(page).toMatchElement('.x-grid3-cell-inner.x-grid3-col-subject', {text: subject, timeout: 2000});
        } catch {
            await page.click('.t-app-felamimail .x-btn-image.x-tbar-loading');
        }
        await expect(page).toMatchElement('.x-grid3-cell-inner.x-grid3-col-subject', {text:  subject});

        await page.waitForSelector('a span',{text: "Posteingang"});
        await expect(page).toClick('a span',{text: "Posteingang"});
        await page.waitForTimeout(3000);

    })
    test('reply mail', async () => {
        await expect(page).toClick('.x-grid3-cell-inner.x-grid3-col-subject', {text: subject});
        const newWindowPromis = lib.getNewWindow();
        await page.click(('.t-app-felamimail .x-toolbar-left-row .x-btn-image.action_email_reply'));

        await sendMail('reply',newWindowPromis);

        await expect(page).toClick('.x-grid3-cell-inner.x-grid3-col-subject', {text: 'reply'});
    })
    test('all reply mail', async () => {
        await expect(page).toClick('.x-grid3-cell-inner.x-grid3-col-subject', {text: subject});
        const newWindowPromis = lib.getNewWindow();
        await page.click(('.t-app-felamimail .x-toolbar-left-row  .x-btn-image.action_email_replyAll'));

        await sendMail('replyAll',newWindowPromis, true);

        await expect(page).toClick('.x-grid3-cell-inner.x-grid3-col-subject', {text: 'replyAll'});
    })
    test('forward email', async () => {
        await expect(page).toClick('.x-grid3-cell-inner.x-grid3-col-subject', {text: subject});
        const newWindowPromis = lib.getNewWindow();
        await page.click(('.t-app-felamimail .x-toolbar-left-row .x-btn-image.action_email_forward'));

        await sendMail('forward',newWindowPromis, true);

        await expect(page).toClick('.x-grid3-cell-inner.x-grid3-col-subject', {text: 'forward'});
    })
});

afterAll(async () => {
    browser.close();
});

async function sendMail(subject, newWindowPromis, currentUser= false) {
    let popupWindow = await newWindowPromis;
    try {
        await popupWindow.waitForSelector('.ext-el-mask', {timeout: 5000});
    } catch {}
    await popupWindow.waitForFunction(() => !document.querySelector('.ext-el-mask'));
    await popupWindow.waitForTimeout(2000); //musst wait for input!

    if(currentUser) {
        currentUser = await lib.getCurrentUser(popupWindow);
        // add recipient
        let inputFields = await popupWindow.$$('input');
        await inputFields[2].type(currentUser.accountEmailAddress);
        await popupWindow.waitForSelector('.search-item.x-combo-selected');
        await popupWindow.click('.search-item.x-combo-selected');
        await popupWindow.waitForTimeout(1000); //wait for new mail line!
    }

    await popupWindow.click('input[name=subject]');
    await popupWindow.waitForTimeout(1000);
    await expect(popupWindow).toFill('input[name=subject]', subject);

    // send message
    await expect(popupWindow).toClick('button', {text: 'Senden'});

    await page.waitForTimeout(2000); //wait to close editDialog
    await page.click('.t-app-felamimail .x-btn-image.x-tbar-loading');
    try{
        await expect(page).toMatchElement('.x-grid3-cell-inner.x-grid3-col-subject', {text: subject, timeout: 2000});
    } catch {
        await page.click('.t-app-felamimail .x-btn-image.x-tbar-loading');
    }
    await expect(page).toMatchElement('.x-grid3-cell-inner.x-grid3-col-subject', {text: subject});
}
