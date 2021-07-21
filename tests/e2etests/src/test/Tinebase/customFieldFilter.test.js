const expect = require('expect-puppeteer');
const lib = require('../../lib/browser');

require('dotenv').config();

beforeAll(async () => {
    await lib.getBrowser('Admin', 'Zusatzfelder');
});

describe.skip('Mainpage', () => {
    test('create customFieldKeyField', async () => {
        let editDialog = await lib.getEditDialog('Zusatzfeld hinzufügen');
        await editDialog.click('input[name=application_id]');
        await expect(editDialog).toClick('.x-combo-list-item', {text:'Crm',visible:true});
        await editDialog.click('input[name=type]');
        await expect(editDialog).toClick('.x-combo-list-item', {text:'Schlüsselfeld',visible:true});
        await expect(editDialog).toClick('button', {text:'Datenspeicher konfigurieren'});
        await editDialog.waitFor(3000);
        let input = await editDialog.$$('.x-form-text.x-form-field.x-form-empty-field')
        await input[0].type('keyField1Id');
        await input[1].type('keyField2Value');
        await editDialog.keyboard.press('Enter');
        await expect(editDialog).toClick('button', {text: 'OK'});
        await editDialog.type('input[name=name]', 'testcustomField-keyField');
        await editDialog.type('input[name=label]', 'testcustomField-keyField');
        await expect(editDialog).toClick('button', {text: 'Ok'});
        await page.waitFor(1000); //wait to create customField
        await lib.reloadRegistry(page);
        await page.waitForSelector('.x-tab-strip-closable.x-tab-with-icon.tine-mainscreen-apptabspanel-menu-tabel', {timeout: 0});
        await page.waitForTimeout(1000);
    });

    test.skip('filter null customField-keyField', async () => {
        await expect(page).toClick('span', {text: process.env.TEST_BRANDING_TITLE});
        await expect(page).toClick('.x-menu-item-text', {text: 'Crm'});
        try {
            await expect(page).toClick('.t-app-crm button', {text: 'Details anzeigen'});
        } catch (e) {

        }
        let arrowtrigger = await page.$$('.t-app-crm .x-form-arrow-trigger');
        await arrowtrigger[0].click();
        await expect(page).toClick('.x-combo-list-item', {text:'testcustomField-keyField',visible:true});
        await expect(page).toClick('button', {text:'Suche starten',visible:true});
        if(expect(page).toMatchElement('.x-window-header-text', {text:'Programmabbruch'})) {
            throw new Error('customField-keyField don´t load with null keyField values');
        }
    });
});

afterAll(async () => {
    browser.close();
});
