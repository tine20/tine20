const expect = require('expect-puppeteer');
const lib = require('../lib/browser');
const help = require('../lib/helper');
require('dotenv').config();

beforeAll(async () => {
    expect.setDefaultOptions({timeout: 1000});
    await lib.getBrowser('Kalender');
});

describe('keyFields', () => {
    describe('calendar', () => {
        let newPage;
        test('events keyFields', async () => {
            await expect(page).toClick('button', {text: 'Termin hinzufügen'});
            newPage = await lib.getNewWindow();
            await newPage.waitFor(5000);
            await newPage.click('.ext-ux-grid-gridviewmenuplugin-menuBtn.x-grid3-hd-btn');
            await expect(newPage).toClick('span' , {text: 'Rolle'});
            await newPage.waitForSelector('.x-grid3-cell-inner.x-grid3-col-role');
            await newPage.waitFor(5000);
            let element = await expect(newPage).toMatchElement('.x-grid3-row.x-grid3-row-first .x-grid3-cell-inner.x-grid3-col-role', {text: 'Erforderlich'});
            await element.click();
            await expect(newPage).toMatchElement('.x-combo-list-item ', {text: 'Freiwillig'});
            await newPage.keyboard.press('Escape');
        });
        test('event status field', async () => {
            await newPage.waitForSelector('.x-grid3-cell-inner.x-grid3-col-status');
            await expect(newPage).toMatchElement('.x-grid3-row.x-grid3-row-first .x-grid3-cell-inner.x-grid3-col-status .tine-keyfield-icon');
            await newPage.close();
        });
    })
});

describe('internal contacts', () => {
    test.skip('change internal contact', async () => {

        let currentUser = await help.getCurrenUser(page);

        await page.waitFor(5000);

        await expect(page).toClick('span', {text: process.env.TEST_BRANDING_TITLE});
        await expect(page).toClick('.x-menu-item-text', {text: 'Adressbuch'});

        await page.waitFor(1000);

        await expect(page).toClick('.tine-mainscreen-centerpanel-west span', {text: 'Kontakte'});

        await expect(page).toClick('span' , {text: 'Gemeinsame Adressbücher', button: 'right'});
        await expect(page).toClick('span' , {text: 'Adressbuch hinzufügen'});
        await expect(page).toFill('.ext-mb-input', 'test');
        await page.keyboard.press('Enter');

        await page.waitFor(5000);
        await expect(page).toClick('span', {text: process.env.TEST_BRANDING_TITLE});
        await expect(page).toClick('.x-menu-item-text', {text: 'Admin'});
        await expect(page).toClick('.x-grid3-col-accountLoginName', {text: 'sclever', clickCount: 2});
        newPage = await lib.getNewWindow();
        await newPage.waitFor(5000);
        let elementValue = await newPage.evaluate(() => document.querySelector("input[name=container_id]").value);
        await newPage.click('input[name=container_id]');
        await newPage.click('.x-form-field-wrap.x-form-field-trigger-wrap.x-trigger-wrap-focus .x-form-trigger.x-form-arrow-trigger');
        await expect(newPage).toClick('.x-combo-list-item', {text: 'test'}); // @todo need container!
        await expect(newPage).toClick('button', {text: 'Ok'});
        await page.waitFor(1000);
        try {
            // if for own user!
            await expect(page).toMatchElement('.x-window.x-window-plain.x-window-dlg button', {timeout: 1000});
            await expect(page).toClick('.x-window.x-window-plain.x-window-dlg button', {text: 'Ja'});
            await page.waitFor(10000);
        } catch (e) {
            
        }
        await page.waitFor(1000);
        await expect(page).toClick('.x-grid3-col-accountLoginName', {text: 'sclever', clickCount: 2});
        newPage = await lib.getNewWindow();
        await newPage.waitFor(5000);
        let elementValue2 = await newPage.evaluate(() => document.querySelector("input[name=container_id]").value);
        if(elementValue == elementValue2)
        {
            throw new Error('container change failed!');
        }
        await newPage.close();
    })
});

afterAll(async () => {
    browser.close();
});
