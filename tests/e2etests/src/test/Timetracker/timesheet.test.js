const expect = require('expect-puppeteer');
const lib = require('../../lib/browser');

require('dotenv').config();

beforeAll(async () => {
    //expect.setDefaultOptions({timeout: 3000});
    await lib.getBrowser('Zeiterfassung', 'Stundenzettel');
});

describe('Mainpage', () => {
    test('editDialog', async () => {
        let popupWindow = await lib.getEditDialog( 'Stundenzettel hinzufügen');
        await popupWindow.close();
    })
});

afterAll(async () => {
    browser.close();
});
