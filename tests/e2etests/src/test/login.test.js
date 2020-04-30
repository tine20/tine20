const expect = require('expect-puppeteer');
const lib = require('../lib/browser');
require('dotenv').config();

describe('login', () => {
    test('login test', async () => {
        await lib.getBrowser();

        await expect(page).toClick('button', { text: 'Abmelden' });
        await expect(page).toClick('button', { text: 'Ja' });

        await page.waitFor('.tb-login-big-label');

    })
});

afterAll(async () => {
    browser.close();
});
