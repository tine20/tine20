const expect = require('expect-puppeteer');
const lib = require('../../lib/browser');

require('dotenv').config();

beforeAll(async () => {
    await lib.getBrowser('Dateimanager');
});

describe('filemanager', () => {
    describe('filemanager grid', () => {
        test('select home folder', async () => {
            try {
                await page.waitForSelector('t-app-filemanager .tine-mainscreen-centerpanel-west-treecards .x-panel-collapsed .x-tool.x-tool-toggle');
                await page.click('.t-app-filemanager .tine-mainscreen-centerpanel-west-treecards .x-panel-collapsed .x-tool.x-tool-toggle');
            } catch (e) {
                console.log('tree also expand');
            }

            await expect(page).toClick('.t-app-filemanager .tine-mainscreen-centerpanel-west-treecards li img.x-tree-elbow-plus');
            await page.waitForTimeout(2000);
            await expect(page).toClick('.t-app-filemanager .tine-mainscreen-centerpanel-west-treecards span', {text: 'Persönliche Dateien von ' + process.env.TEST_USER, timeout: 5000});
            await page.waitForTimeout(2000);
        });
        // gehen ins home verzeichnis
        describe('new folder', () => {
            let editDialog;
            test('create folder', async () => {
                const folder = 'Test' + Math.round(Math.random() * 10000000);
                await page.waitForTimeout(1000);
                await expect(page).toClick('.t-app-filemanager button', {text: 'Ordner anlegen',visibile:true});
                await page.type('.x-layer.x-editor.x-small-editor.x-grid-editor input', folder);
                await page.keyboard.press('Enter');
                await page.waitForTimeout(2000);
                await expect(page).toClick('.x-grid3-cell-inner.x-grid3-col-name' ,{text:folder});
            });
            test('open editDialog', async () => {
                editDialog = lib.getNewWindow();
                await expect(page).toClick('.t-app-filemanager button', {text: 'Eigenschaften bearbeiten',visibile:true});
                editDialog = await editDialog;
                await editDialog.waitForTimeout(5000);
                await expect(editDialog).toClick('span',{text: 'Berechtigungen'});
            });
            test('add user in grantsPanel', async () => {
                await editDialog.waitForTimeout(2000);
                await expect(editDialog).toClick('.x-form-cb-label', {text: 'Dieser Ordner hat eigene Berechtigungen'});
                await editDialog.waitForSelector('.x-toolbar.x-small-editor.x-column-layout-ct', {visible: true})
                let input = await editDialog.$$('.x-panel-tbar.x-panel-tbar-noheader');
                await input[1].click();
                await editDialog.keyboard.press('ArrowDown');
                await expect(editDialog).toClick('.x-combo-list-item', {text:'Users'});
            });
            test('give new user rights', async () => {
                await editDialog.waitForXPath('//div[contains(@class, "x-grid3-row ") and contains(., "Users")]');;
                await clickCheckBox(editDialog,'x-grid3-cc-add');
                await clickCheckBox(editDialog,'x-grid3-cc-edit');
                await clickCheckBox(editDialog,'x-grid3-cc-delete');
                await clickCheckBox(editDialog,'x-grid3-cc-download');
                await clickCheckBox(editDialog,'x-grid3-cc-publish');
            });
            test('save folder', async () => {
                await expect(editDialog).toClick('button', {text:'Ok'});
                await page.waitForTimeout(2000);
            });
            test('upload file', async () => {
                //@todo upload file!
            });
        })
    })
});

afterAll(async () => {
    browser.close();
});


async function clickCheckBox(page, checkbox) {
    await page.waitForTimeout(500);
    const elements = await page.$x('//div[contains(@class, "x-grid3-row") and contains(., "Users")] //div[contains(@class, "'+ checkbox +'")]');
    await elements[0].click();
}
