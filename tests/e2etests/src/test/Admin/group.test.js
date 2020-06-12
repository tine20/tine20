const expect = require('expect-puppeteer');
const lib = require('../../lib/browser');

require('dotenv').config();

beforeAll(async () => {
    //expect.setDefaultOptions({timeout: 1000});
    await lib.getBrowser('Admin');
});

describe('gridField account only', () => {
    test('gridField account only', async () => {
        await expect(page).toClick('.x-tree-node span', {text: 'Gruppen', visible: true});
        await expect(page).toMatchElement('.x-grid3-hd-account_only');
    })
});

afterAll(async () => {
    browser.close();
});