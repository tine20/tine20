const timeout = process.env.SLOWMO ? 30000 : 30000;
const expect = require('expect-puppeteer');
const lib = require('../lib/browser');
require('dotenv').config();

describe('login', () => {
    test('login test', async () => {
        expect.setDefaultOptions({timeout: 1000});
        await lib.getBrowser('Admin');
    })
});

afterAll(async () => {
    browser.close();
});
