const expect = require('expect-puppeteer');
const lib = require('../../lib/browser');
const help = require('../../lib/helper');
require('dotenv').config();

beforeAll(async () => {
    expect.setDefaultOptions({timeout: 1000});
    await lib.getBrowser('Dateimanager');

});

describe('Test mainpage', () => {
    test('mainpage', async () => {
        try {
            await page.waitFor(500);
            await page.click('.t-app-filemanager .tine-mainscreen-centerpanel-west-treecards .x-panel-collapsed .x-tool.x-tool-toggle');
            await page.waitFor(500);

        } catch (e) {
            console.log('tree also expand');
        }
        await page.screenshot({path: 'screenshots/6_dateimanager/1_dateimanager_baumstruktur.png'});
    });

    test('add folder', async () => {
        await expect(page).toClick('.t-app-filemanager .tine-mainscreen-centerpanel-west-treecards span', {text: 'Gemeinsame Ordner'});
        await page.waitFor(2000);
        await expect(page).toClick('.t-app-filemanager button', {text: 'Ordner anlegen'});
        await page.type('.ext-mb-fix-cursor input', 'Test');
        await page.waitFor(1000);
        await page.screenshot({path: 'screenshots/6_dateimanager/2_dateimanager_neuer_ordner.png'});
        await page.keyboard.press('Enter');
    })
});
/* skip... is to unstable
describe('Context menu', () => {
   test('test menu', async () => {
       await page.waitFor(2000);
       await expect(page).toClick('.t-app-filemanager .tine-mainscreen-centerpanel-west-treecards span', {text: 'Test', button: 'right'});
       await page.hover('.x-menu-item-icon.action_rename');
       await page.screenshot({path: 'screenshots/6_dateimanager/3_dateimanager_ordner_kontextmenu.png'});
       await page.keyboard.press('Escape');
   });
    test('rights', async () => {
        await page.waitFor(2000);
        await expect(page).toClick('.t-app-filemanager .tine-mainscreen-centerpanel-west-treecards span', {text: 'Test', button: 'right'});
        await page.click('.x-menu-item-icon.action_edit_file');
        var newPage = await lib.getNewWindow();
        await newPage.waitFor(2000);
        await expect(newPage).toClick('span', {text: 'Berechtigungen'});
        await newPage.screenshot({path: 'screenshots/6_dateimanager/5_dateimanager_ordner_rechte.png'});
        [button] = await help.getElement('button', newPage, 'Abbrechen');
        await button.click();
    })
});
*/
describe('editDialog', () => {
    test.skip('edit file', async () => {
        await page.waitFor(2000);
        await expect(page).toClick('.t-app-filemanager .tine-mainscreen-centerpanel-west-treecards span', {text: 'Vorlagen'});
        // @todo need folder and file !
        await page.waitFor(2000);
        await page.screenshot({path: 'screenshots/6_dateimanager/6_dateimanager_bearbeitungsmenu.png'});
        await page.click('.x-grid3-row.x-grid3-row-first.x-grid3-row-last');
        var [button] = await help.getElement('button', page, 'Eigenschaften bearbeiten');
        await button.click();
        var newPage = await lib.getNewWindow();
        await newPage.waitFor(2000);
        await newPage.screenshot({path: 'screenshots/6_dateimanager/8_dateimanager_eigenschaften_datei.png'});
    })
});

afterAll(async () => {
    browser.close();
});
