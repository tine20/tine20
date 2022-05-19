const expect = require('expect-puppeteer');
const lib = require('../../lib/browser');

require('dotenv').config();

beforeAll(async () => {
    await lib.getBrowser('Zeiterfassung', 'Stundenzettel');
});

describe('Create and delete time sheet', () => {
    const testDescription = 'test description ' + Math.round(Math.random() * 10000000);
    let popupWindow = null;

    test('Open dialog', async () => {
        popupWindow = await lib.getEditDialog('Stundenzettel hinzufügen');
        await expect(popupWindow).toMatchElement('span.x-tab-strip-text', {text: 'Stundenzettel'});
    });

    test('Select time account', async() => {
        await popupWindow.waitForSelector('[name="timeaccount_id"]');
        await expect(popupWindow).toFill('[name="timeaccount_id"]', 'test');
        await popupWindow.waitForSelector('.x-combo-list-item');
        await popupWindow.keyboard.press('Enter');
    });

    test('Enter description', async () => {
        await popupWindow.waitForSelector('[name="description"]');
        await expect(popupWindow).toFill('[name=description]', testDescription);
    });

    test('Enter start and end time', async() => {
        await popupWindow.waitForSelector('input[name="start_time"]');
        await expect(popupWindow).toFill('input[name="start_time"]', '08:00');

        await popupWindow.waitForSelector('input[name="end_time"]');

        // end time is added automatically, so we have to delete it first
        await expect(popupWindow).toClick('input[name="end_time"]');
        await popupWindow.keyboard.press('Backspace');
        await expect(popupWindow).toFill('input[name="end_time"]', '11:30');

        await expect(popupWindow).toMatchElement('input[name="duration"]', {value: '03:30'});
    });

    test('Confirm', async() => {
        await expect(popupWindow).toClick('button', {text: 'Ok'});
    });

    // FIXME make it work
    test.skip('Check values in the grid', async() => {
        await expect(page).toMatchElement('div.x-grid3-col-timeaccount_id', {text: '1 - Test Timeaccount 1'});
        await expect(page).toMatchElement('div.x-grid3-col-description', {text: testDescription});
        await expect(page).toMatchElement('div.x-grid3-col-duration', {text: '3 Stunden, 30 Minuten'});
    });

    // FIXME make it work
    test.skip('Delete and confirm', async() => {
        await expect(page).toClick('div.x-grid3-col-description', {text: testDescription});
        await page.keyboard.press('Delete');
        await page.waitForSelector('.x-btn-icon-small-left');
        await expect(page).toClick('button', {text: 'Ja'});
        await expect(page).not.toMatchElement('div.x-grid3-col-description', {text: testDescription});
    });
});

afterAll(async () => {
    browser.close();
});
