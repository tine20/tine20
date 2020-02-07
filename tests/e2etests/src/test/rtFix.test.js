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

afterAll(async () => {
    browser.close();
});
