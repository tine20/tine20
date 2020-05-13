const expect = require('expect-puppeteer');
const lib = require('../../lib/browser');

require('dotenv').config();

beforeAll(async () => {
    await lib.getBrowser('Aufgaben');
    await page.screenshot({path: 'screenshots/5_aufgaben/1_aufgaben_uebersicht.png'});
});

describe('Edit Contact', () => {
    let popupWindow;
    test('open EditDialog', async () => {

        popupWindow = await lib.getEditDialog('Aufgabe hinzufügen');
        await popupWindow.type('input[name=summary]', 'Bewerbungsunterlagen sondieren');
        await popupWindow.screenshot({path: 'screenshots/5_aufgaben/2_aufgaben_neue_aufgabe.png'});
    });

    test('notification', async () => {
        await expect(popupWindow).toClick('span', {text: 'Alarm', clickCount: 1});
        await popupWindow.click('.new-row .x-form-trigger.x-form-arrow-trigger');
        await popupWindow.waitFor(500);
        await popupWindow.screenshot({path: 'screenshots/5_aufgaben/3_aufgaben_alarm.png'});
    })
});

afterAll(async () => {
    browser.close();
});
