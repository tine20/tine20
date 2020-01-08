const timeout = process.env.SLOWMO ? 30000 : 30000;
const expect = require('expect-puppeteer');
const lib = require('../../lib/browser');
const help = require('../../lib/helper');
require('dotenv').config();

beforeAll(async () => {
    expect.setDefaultOptions({timeout: 1000});
    await lib.getBrowser('Crm');
    await page.screenshot({path: 'screenshots/7_crm/1_crm_leadtabellen.png'});
});

describe('Test mainpage', () => {

});

describe('mainScreen' , () => {

});

describe('Edit Contact', () => {
    test('open EditDialog', async () => {
        var [button] = await help.getElement('button', page, 'Lead hinzufügen');
        await button.click();
        //console.log('Klick Button');
        newPage = await lib.getNewWindow();
        await newPage.setViewport({
            width: 1366,
            height: 768,
        });
        await newPage.waitFor(3000);
        await newPage.type('input[name=lead_name]', 'Lead');
        await newPage.screenshot({path: 'screenshots/7_crm/2_crm_lead_neu.png'});
        await newPage.waitFor(2000);
        await newPage.click('.x-menu-item-icon.contactIcon');
        await newPage.waitFor(2000);
        await expect(newPage).toMatchElement('.x-combo-list-item', {text: 'Partner'});
        await newPage.screenshot({path: 'screenshots/7_crm/3_crm_lead_rolle.png'});
        await newPage.click('input[name=leadstate_id]');
        await newPage.click('.x-form-field-wrap.x-form-field-trigger-wrap.x-trigger-wrap-focus');
        await newPage.waitFor(500);
        await newPage.screenshot({path: 'screenshots/7_crm/4_crm_lead_status.png'});

        let produkte = await newPage.$$('#linkPanelBottom .x-tab-strip-text');
        await produkte[1].click();
        await newPage.click('#linkPanelBottom .x-panel-tbar.x-panel-tbar-noheader.x-panel-tbar-noborder');
        await newPage.keyboard.press('ArrowDown');
        await newPage.waitFor(500);
        await newPage.screenshot({path: 'screenshots/7_crm/6_crm_lead_produkte_zuweisen.png'});
        await expect(newPage).toClick('.x-combo-list-item', {text: 'Getränke'}); // need DemoDaten!
        await newPage.waitFor(500);
        await newPage.screenshot({path: 'screenshots/7_crm/5_crm_lead_produkte_zugewiesen.png'});


        //console.log('Get Popup');
    });

/*
    test('notification', async () => {
        await expect(newPage).toClick('span', {text: 'Alarm', clickCount: 1});
        await newPage.click('.new-row .x-form-trigger.x-form-arrow-trigger');
        await newPage.waitFor(500);

        /*let combolist = await newPage.$('.x-combo-list[visibility=visible]');
        await combolist.hover('.x-combo-list-item', {text: '1 Tag davor'});
        await newPage.waitFor(1000);
        await newPage.screenshot({path: 'screenshots/5_aufgaben/3_aufgaben_alarm.png'});
    })
    */

});

afterAll(async () => {
    browser.close();
});
