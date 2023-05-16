const expect = require('expect-puppeteer');
const lib = require('../../lib/browser');

require('dotenv').config();

beforeAll(async () => {
    await lib.getBrowser('Inventarisierung');
    await page.waitForTimeout(1000);
    await page.screenshot({path: 'screenshots/Inventarisierung/1_inventar_uebersicht.png'});
});

describe('mainScreen', () => {
    test('import', async () => {
        try {
            await expect(page).toClick('.t-app-inventory button', {text: 'Einträge importieren'});
        } catch (e) {
            await page.click('.x-btn-image.x-toolbar-more-icon');
            await page.click('.x-menu-item-icon action_import');
        }
        let newPage = await lib.getNewWindow();
        await newPage.waitForXPath('//button');
        await newPage.screenshot({path: 'screenshots/Inventarisierung/5_inventar_import.png'});
        await newPage.keyboard.press('Escape')
        await expect(newPage).toClick('button', {text: 'Abbrechen'});
    })
});

describe('Edit Inventory Item', () => {
    let newPage;
    test('open EditDialog', async () => {
        await expect(page).toClick('button', {text: 'Inventargegenstand hinzufügen'});
        newPage = await lib.getNewWindow();
        await newPage.waitForTimeout(5000); // @todo waitfor selector...
        await newPage.screenshot({path: 'screenshots/Inventarisierung/2_inventar_gegenstand_neu.png'});
        await newPage.click('input[name=status]');
        await newPage.waitForTimeout(1000);
        await newPage.click('.x-form-field-wrap.x-form-field-trigger-wrap.x-trigger-wrap-focus');
        await newPage.screenshot({path: 'screenshots/Inventarisierung/3_inventar_gegenstand_status.png'});
    });

    test('accounting', async () => {
        await expect(newPage).toClick('span', {text: 'Buchhaltung'});
        await newPage.screenshot({path: 'screenshots/Inventarisierung/4_inventar_gegenstand_buchhaltung.png'});

    })
});

afterAll(async () => {
    browser.close();
});
