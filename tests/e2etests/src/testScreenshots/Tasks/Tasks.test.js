const expect = require('expect-puppeteer');
const lib = require('../../lib/browser');
const help = require('../../lib/helper');
require('dotenv').config();

beforeAll(async () => {
    expect.setDefaultOptions({timeout: 1000});
    await lib.getBrowser('Aufgaben');
    await page.waitFor(1000);
    await page.screenshot({path: 'screenshots/5_aufgaben/1_aufgaben_uebersicht.png'});
});

describe('Test mainpage', () => {

});

describe('mainScreen', () => {

});

describe('Edit Contact', () => {
    let newPage;
    test('open EditDialog', async () => {
        var [button] = await help.getElement('button', page, 'Aufgabe hinzufügen');
        await button.click();
        //console.log('Klick Button');
        newPage = await lib.getNewWindow();
        await newPage.waitFor('button', {text: 'Ok'});
        await newPage.waitFor(500);
        await newPage.type('input[name=summary]', 'Bewerbungsunterlagen sondieren');
        await newPage.screenshot({path: 'screenshots/5_aufgaben/2_aufgaben_neue_aufgabe.png'});
        //console.log('Get Popup');
    });

    test('notification', async () => {
        await expect(newPage).toClick('span', {text: 'Alarm', clickCount: 1});
        await newPage.click('.new-row .x-form-trigger.x-form-arrow-trigger');
        await newPage.waitFor(500);

        /*let combolist = await newPage.$('.x-combo-list[visibility=visible]');
        await combolist.hover('.x-combo-list-item', {text: '1 Tag davor'});
        await newPage.waitFor(1000);*/
        await newPage.screenshot({path: 'screenshots/5_aufgaben/3_aufgaben_alarm.png'});
    })
});

afterAll(async () => {
    browser.close();
});
