const expect = require('expect-puppeteer');
const lib = require('../../lib/browser');
require('dotenv').config();

beforeAll(async () => {

    await lib.getBrowser('Kalender');
});

describe('ColorPicker', () => {
    let shared = 'test' + Math.round(Math.random() * 10000000);
    let privat = 'test' + Math.round(Math.random() * 10000000);
    test('add calendar', async () => {
        try {
            await page.waitForSelector('.t-app-calendar .tine-mainscreen-centerpanel-west-treecards .x-panel-collapsed .x-tool.x-tool-toggle');
            console.log('tree expand');
            await page.click('.t-app-calendar .tine-mainscreen-centerpanel-west-treecards .x-panel-collapsed .x-tool.x-tool-toggle');
        } catch (e) {
            console.log('tree also expand');
        }

        await page.waitFor(1000); //wait to expand tree
        await addTestCalendar(page,'Gemeinsame Kalender', shared);
        await page.waitFor(1000);  //wait to expand tree
        await addTestCalendar(page,'Meine Kalender', privat);
    });
    test('change color on private calendar', async () => {
        await expect(page).toMatchElement('span', {text: privat});
        await changeColor(page, privat,'008080');
        await changeColor(page, privat,'008080', true);
    });
    test('change color on shared calendar', async () => {
        await expect(page).toMatchElement('span', {text: shared});
        await changeColor(page, shared,'008080');
        await changeColor(page, shared,'008080', true);
    })
});

describe.skip('keyFields', () => {
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

describe.skip('changeViews', () => {
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


async function addTestCalendar(page, root, calName) {
    await expect(page).toClick('span', {text:root, button:'right'});
    await expect(page).toMatchElement('.x-menu.x-menu-floating.x-layer', {visible: true});
    await expect(page).toClick('span', {text:'Kalender hinzufügen'});
    await page.waitFor('.x-window.x-window-plain.x-window-dlg');
    await page.type('.ext-mb-text', calName);
    await page.keyboard.press('Enter');
}

async function changeColor(page, calName, color, colorPicker = false) {
    await expect(page).toClick('span', {text: calName, button: 'right'});
    await expect(page).toMatchElement('.x-menu.x-menu-floating.x-layer', {visible: true});
    let picker = await page.$x('//span[contains(@class,"x-menu-item-text") and contains(.,"Kalender Farbe einstellen")]');
    await picker[0].hover();
    await page.waitForSelector('.x-color-palette');
    if(colorPicker) {
        await expect(page).toClick('.color-picker', {visible: true});
        await page.waitForSelector('.hu-color-picker.light');
        //@todo change color!
        await expect(page).toClick('.x-window.x-resizable-pinned button', {text:'Ok', visible: true});
    }else {
        await expect(page).toClick('.color-' + color, {visible: true});
    }
}