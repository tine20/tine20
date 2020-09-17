const expect = require('expect-puppeteer');
const lib = require('../../lib/browser');
require('dotenv').config();

beforeAll(async () => {

    await lib.getBrowser('Kalender');
});

describe('keyFields', () => {
    describe('calendar', () => {
        let popupWindow;
        test('events keyFields', async () => {
            popupWindow = await lib.getEditDialog('Termin hinzufügen');
            let rollTd = await popupWindow.evaluate(() => document.querySelector(
                '.x-grid3-hd.x-grid3-cell.x-grid3-td-role.x-grid3-cell-first').style.display);

            if(rollTd == 'none') {
                await popupWindow.click('.ext-ux-grid-gridviewmenuplugin-menuBtn.x-grid3-hd-btn');
                await expect(popupWindow).toClick('span', {text: 'Rolle'});
            }

            await popupWindow.waitForSelector('.x-grid3-cell-inner.x-grid3-col-role');
            let element = await expect(popupWindow).toMatchElement('.x-grid3-row.x-grid3-row-first .x-grid3-cell-inner.x-grid3-col-role', {text: 'Erforderlich'});
            await element.click();
            await expect(popupWindow).toMatchElement('.x-combo-list-item ', {text: 'Freiwillig'});
            await popupWindow.keyboard.press('Escape');
        });
        test('event status field', async () => {
            await popupWindow.waitForSelector('.x-grid3-cell-inner.x-grid3-col-status');
            await expect(popupWindow).toMatchElement('.x-grid3-row.x-grid3-row-first .x-grid3-cell-inner.x-grid3-col-status .tine-keyfield-icon');
            await popupWindow.close();
        });
    })
});

describe('changeViews', () => {
    // .tine-mainscreen-centerpanel-center
    test('day View', async () => {
        // NOTE: if we enter the tests here we need to wait till loadmask shows and hides
        // await page.waitFor(() => document.querySelector('.x-mask-loading.cal-ms-panel-mask'));
        // await page.waitFor(() => !document.querySelector('.x-mask-loading.cal-ms-panel-mask'));

        await expect(page).toClick('button', {text: 'Tag'});
        await page.waitFor(() => document.querySelector('.x-mask-loading.cal-ms-panel-mask'));
        await page.waitFor(() => !document.querySelector('.x-mask-loading.cal-ms-panel-mask'));
        
        await page.waitForSelector('.x-panel.cal-ms-panel:not(.x-hide-display) .cal-daysviewpanel-daysheader');

        await page.waitFor(() => {
            return document.querySelector('.x-panel.cal-ms-panel:not(.x-hide-display) .cal-daysviewpanel-daysheader').childNodes.length === 1
        });
    });
    
    test('week View', async () => {
        await expect(page).toClick('button', {text: 'Woche'});
        await page.waitFor(() => document.querySelector('.x-mask-loading.cal-ms-panel-mask'));
        await page.waitFor(() => !document.querySelector('.x-mask-loading.cal-ms-panel-mask'));

        await page.waitFor(() => {
            return document.querySelector('.x-panel.cal-ms-panel:not(.x-hide-display) .cal-daysviewpanel-daysheader').childNodes.length === 7
        });
    });

    test('week View custom days', async () => {
        await lib.clickSlitButton(page, "Woche");

        await expect(page).toClick('button', {text: 'Sa'});
        await expect(page).toClick('.cal-wkperiod-config-menu button', {text: 'OK'});

        await page.waitFor(() => document.querySelector('.x-mask-loading.cal-ms-panel-mask'));
        await page.waitFor(() => !document.querySelector('.x-mask-loading.cal-ms-panel-mask'));
        await page.waitFor(() => {
            return document.querySelector('.x-panel.cal-ms-panel:not(.x-hide-display) .cal-daysviewpanel-daysheader').childNodes.length === 5
        });
    });
});

afterAll(async () => {
    browser.close();
});
