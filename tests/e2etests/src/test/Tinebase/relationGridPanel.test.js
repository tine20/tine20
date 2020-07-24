const expect = require('expect-puppeteer');
const lib = require('../../lib/browser');

require('dotenv').config();

beforeAll(async () => {
    await lib.getBrowser('Adressbuch', 'Kontakte');
});

describe('Mainpage', () => {

    test('openEditDialog', async () => {
        let popupWindow = await lib.getEditDialog('Kontakt hinzufügen');
        await expect(popupWindow).toClick('span', {text: new RegExp("Verknüpfungen.*")});
        let arrows = await popupWindow.$$('.x-panel.x-wdgt-pickergrid.x-grid-panel .x-form-trigger.x-form-arrow-trigger');
        await arrows[0].click();
        //await popupWindow.waitForSelector('.x-layer.x-combo-list',{visible: true});
        await popupWindow.waitFor(2000);
        await expect(popupWindow).toClick('.x-combo-list-item ', {text: 'Dateimanager'});
        await popupWindow.waitFor(2000);
        await popupWindow.click('.x-form-trigger.undefined');
        await popupWindow.waitForSelector('.x-panel.x-panel-noborder.x-grid-panel');
        await popupWindow.waitFor(3000);
        await popupWindow.click('.x-window-bwrap .x-grid3-cell-inner.x-grid3-col-name',{clickCount:2});
        await popupWindow.waitFor(3000);
        await popupWindow.click('.x-window-bwrap .x-grid3-cell-inner.x-grid3-col-name');
        await popupWindow.waitFor(3000);
        await expect(popupWindow).toClick('.x-window.x-window-plain.x-resizable-pinned button',{text: 'Ok'});
        await popupWindow.waitFor(3000);
        await popupWindow.click('.x-grid3-cell-inner.x-grid3-col-related_record',{clickCount:2});
        let mainWindow = await lib.getNewWindow();
        await mainWindow.waitForSelector('.x-tab-strip-closable.x-tab-with-icon.tine-mainscreen-apptabspanel-menu-tabel', {timeout: 0});

    });
});

afterAll(async () => {
    browser.close();
});
