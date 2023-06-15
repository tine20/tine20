const expect = require('expect-puppeteer');
const lib = require('../../lib/browser');

require('dotenv').config();

beforeAll(async () => {
    await lib.getBrowser('Crm');
    await page.mouse.move(0, 0);
    await page.waitForTimeout(1000);
    await page.screenshot({path: 'screenshots/Crm/1_crm_leadtabellen.png'});
});

describe('Edit Lead', () => {
    let editDialog
    test('open EditDialog', async () => {
        editDialog = await lib.getEditDialog('Lead hinzufügen');

        await editDialog.setViewport({
            width: 1366,
            height: 768,
        });
        await editDialog.waitForTimeout(1000); //wait for resize viewport
        await editDialog.type('input[name=lead_name]', 'Lead');
        await editDialog.screenshot({path: 'screenshots/Crm/2_crm_lead_neu.png'});
        await editDialog.click('.x-menu-item-icon.contactIcon');
        await expect(editDialog).toMatchElement('.x-combo-list-item', {text: 'Partner'});
        await editDialog.screenshot({path: 'screenshots/Crm/3_crm_lead_rolle.png'});
        await editDialog.click('input[name=leadstate_id]', {count : 2, delay: 500});
        await expect(editDialog).toMatchElement('.x-combo-list-item', {text: 'akzeptiert'});
        await editDialog.screenshot({path: 'screenshots/Crm/4_crm_lead_status.png'});
        await editDialog.keyboard.press('Escape');
    });

    test('add product', async () => {
        let product = await editDialog.$$('#linkPanelBottom .x-tab-strip-text');
        await product[1].click();
        await editDialog.click('#linkPanelBottom .x-panel-tbar.x-panel-tbar-noheader.x-panel-tbar-noborder');
        await editDialog.keyboard.press('ArrowDown');
        await editDialog.type('.x-form-text.x-form-field.x-form-focus', 'Getränke');
        await editDialog.waitForTimeout(1000);
        await expect(editDialog).toMatchElement('.x-combo-list-item', {text: 'Getränke'});
        await editDialog.waitForTimeout(1000);
        await editDialog.screenshot({path: 'screenshots/Crm/6_crm_lead_produkte_zuweisen.png'});
        await expect(editDialog).toClick('.x-combo-list-item', {text: 'Getränke'}); // need DemoDaten!
        await editDialog.waitForSelector('.x-grid3-row.x-grid3-row-first.x-grid3-row-last');
        await editDialog.screenshot({path: 'screenshots/Crm/5_crm_lead_produkte_zugewiesen.png'});
    });

    test('add task', async () => {
        await expect(editDialog).toClick('span', {text: 'Aufgaben'});
        await editDialog.type('.new-row .x-form-text.x-form-field.x-form-empty-field', 'Papier kaufen');
        await editDialog.keyboard.press('Enter');
        await expect(editDialog).toMatchElement('.x-grid3-cell-inner.x-grid3-col-summary', {text: 'Papier kaufen'});
        await editDialog.screenshot({path: 'screenshots/Crm/aufgaben.png'});
    });

    test('show grid rows', async () => {
        await editDialog.click('.ext-ux-grid-gridviewmenuplugin-menuBtn.x-grid3-hd-btn')
        await editDialog.waitForSelector('.x-menu-list');
        await editDialog.screenshot({path: 'screenshots/Crm/7_crm_lead_spalten.png'});
    })

});

afterAll(async () => {
    browser.close();
});
