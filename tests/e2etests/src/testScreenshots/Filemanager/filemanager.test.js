const expect = require('expect-puppeteer');
const lib = require('../../lib/browser');

require('dotenv').config();

beforeAll(async () => {
    //expect.setDefaultOptions({timeout: 1000});
    await lib.getBrowser('Dateimanager');

});

describe('Test mainpage', () => {
    test('mainpage', async () => {
        try {
            await page.waitForTimeout(500);
            await page.click('.t-app-filemanager .tine-mainscreen-centerpanel-west-treecards .x-panel-collapsed .x-tool.x-tool-toggle');
            await page.waitForTimeout(500);

        } catch (e) {
            console.log('tree also expand');
        }
        await page.screenshot({path: 'screenshots/Dateimanager/1_dateimanager_baumstruktur.png'});
    });

    test('add folder', async () => {
        await expect(page).toClick('.t-app-filemanager .tine-mainscreen-centerpanel-west-treecards span', {text: 'Gemeinsame Ordner'});
        await page.waitForTimeout(2000);
        await expect(page).toClick('.t-app-filemanager button', {text: 'Ordner anlegen'});
        await page.type('.x-layer.x-editor.x-small-editor.x-grid-editor input', "Test");
        await page.waitForTimeout(1000);
        await page.screenshot({path: 'screenshots/Dateimanager/2_dateimanager_neuer_ordner.png'});
        await page.keyboard.press('Enter');
    })
});
/* skip... is to unstable
describe('Context menu', () => {
   test('test menu', async () => {
       await page.waitForTimeout(2000);
       await expect(page).toClick('.t-app-filemanager .tine-mainscreen-centerpanel-west-treecards span', {text: 'Test', button: 'right'});
       await page.hover('.x-menu-item-icon.action_rename');
       await page.screenshot({path: 'screenshots/Dateimanager/3_dateimanager_ordner_kontextmenu.png'});
       await page.keyboard.press('Escape');
   });
    test('rights', async () => {
        await page.waitForTimeout(2000);
        await expect(page).toClick('.t-app-filemanager .tine-mainscreen-centerpanel-west-treecards span', {text: 'Test', button: 'right'});
        await page.click('.x-menu-item-icon.action_edit_file');
        var newPage = await lib.getNewWindow();
        await newPage.waitForTimeout(2000);
        await expect(newPage).toClick('span', {text: 'Berechtigungen'});
        await newPage.screenshot({path: 'screenshots/Dateimanager/5_dateimanager_ordner_rechte.png'});
        [button] = await help.getElement('button', newPage, 'Abbrechen');
        await button.click();
    })
});
*/
describe('editDialog', () => {
    test.skip('edit file', async () => {
        await page.waitForTimeout(2000);
        await expect(page).toClick('.t-app-filemanager .tine-mainscreen-centerpanel-west-treecards span', {text: 'Vorlagen'});
        // @todo need folder and file !
        await page.waitForTimeout(2000);
        await page.screenshot({path: 'screenshots/Dateimanager/6_dateimanager_bearbeitungsmenu.png'});
        await page.click('.x-grid3-row.x-grid3-row-first.x-grid3-row-last');
        var [button] = await lib.getElement('button', page, 'Eigenschaften bearbeiten');
        await button.click();
        var newPage = await lib.getNewWindow();
        await newPage.waitForTimeout(2000);
        await newPage.screenshot({path: 'screenshots/Dateimanager/8_dateimanager_eigenschaften_datei.png'});
    })
});

afterAll(async () => {
    browser.close();
});
