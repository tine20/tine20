const expect = require('expect-puppeteer');
const lib = require('../../lib/browser');

require('dotenv').config();

beforeAll(async () => {
    //expect.setDefaultOptions({timeout: 1000});
    await lib.getBrowser('Adressbuch', 'Kontakte');
});

describe('internal contacts', () => {
  // needs fix in user->addressbook.container
    test.skip('change internal contact', async () => {
        if (await page.$('#Addressbook_Contact_Tree.x-panel-collapsed .x-tool.x-tool-toggle') !== null) {
            await page.click('#Addressbook_Contact_Tree.x-panel-collapsed .x-tool.x-tool-toggle')
        }
        await expect(page).toClick('span' , {text: 'Gemeinsame Adressbücher', button: 'right'});
        await expect(page).toClick('span' , {text: 'Adressbuch hinzufügen'});
        await expect(page).toFill('.ext-mb-input', 'test');
        await page.keyboard.press('Enter');

        await page.waitForSelector('.x-window-plain', {hidden: true});

        await expect(page).toClick('span', {text: process.env.TEST_BRANDING_TITLE});
        await expect(page).toClick('.x-menu-item-text', {text: 'Admin'});
        await expect(page).toClick('.x-grid3-col-accountLoginName', {text: 'sclever', clickCount: 2});

        let popupWindow = await lib.getNewWindow();
        await popupWindow.waitForSelector('.ext-el-mask');
        let elementValue = await popupWindow.evaluate(() => document.querySelector("input[name=container_id]").value);
        await popupWindow.click('input[name=container_id]');
        await popupWindow.click('.x-form-field-wrap.x-form-field-trigger-wrap.x-trigger-wrap-focus .x-form-trigger.x-form-arrow-trigger');
        await expect(popupWindow).toClick('.x-combo-list-item', {text: 'test'});
        await expect(popupWindow).toClick('button', {text: 'Ok'});
        await page.waitFor(2000); // need wait to close editDialog
        await expect(page).toClick('.x-grid3-col-accountLoginName', {text: 'sclever', clickCount: 2});
        popupWindow = await lib.getNewWindow();
        await popupWindow.waitForSelector('.ext-el-mask');
        let elementValue2 = await popupWindow.evaluate(() => document.querySelector("input[name=container_id]").value);
        if (elementValue == elementValue2) {
            throw new Error('container change failed!');
        }
        await popupWindow.close();
    },)
});

afterAll(async () => {
    browser.close();
});
