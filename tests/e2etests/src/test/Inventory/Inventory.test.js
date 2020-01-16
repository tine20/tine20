const timeout = process.env.SLOWMO ? 30000 : 30000;
const expect = require('expect-puppeteer');
const lib = require('../../lib/browser');
const help = require('../../lib/helper');
require('dotenv').config();

beforeAll(async () => {
    expect.setDefaultOptions({timeout: 1000});
    await lib.getBrowser('Inventarisierung');
    await page.screenshot({path: 'screenshots/9_inventar/1_inventar_uebersicht.png'});
});

describe('mainScreen' , () => {
    test.skip('import', async () => {
        await expect(page).toClick('.t-app-inventory button', {text: 'Einträge importieren'});
        newPage = await lib.getNewWindow();
        await newPage.waitForXPath('//button');
        await newPage.screenshot({path: 'screenshots/9_inventar/5_inventar_import.png'});
        await newPage.keyboard.press('Escape')
    })
});

describe('Edit Contact', () => {
    let newPage;
    test('open EditDialog', async () => {
        await expect(page).toClick('button', {text: 'Inventar Gegenstand hinzufügen'});
        //console.log('Klick Button');
        newPage = await lib.getNewWindow();
        await newPage.waitFor(2000); // @todo waitfor selector...
        await newPage.screenshot({path: 'screenshots/9_inventar/2_inventar_gegenstand_neu.png'});
        await newPage.click('input[name=status]');
        await newPage.waitFor(1000);
        await newPage.click('.x-form-field-wrap.x-form-field-trigger-wrap.x-trigger-wrap-focus');
        await newPage.screenshot({path: 'screenshots/9_inventar/3_inventar_gegenstand_status.png'});
        //console.log('Get Popup');
    });

    test('accounting', async () => {
        await expect(newPage).toClick('span', {text: 'Buchhaltung'});
        await newPage.screenshot({path: 'screenshots/9_inventar/4_inventar_gegenstand_buchhaltung.png'});

    })

 /*   test('notification', async () => {
        await expect(newPage).toClick('span', {text: 'Alarm', clickCount: 1});
        await newPage.click('.new-row .x-form-trigger.x-form-arrow-trigger');
        await newPage.waitFor(500);

        /*let combolist = await newPage.$('.x-combo-list[visibility=visible]');
        await combolist.hover('.x-combo-list-item', {text: '1 Tag davor'});
        await newPage.waitFor(1000);
        await newPage.screenshot({path: 'screenshots/5_aufgaben/3_aufgaben_alarm.png'});
    })  */
});

afterAll(async () => {
    browser.close();
});
