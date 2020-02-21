const expect = require('expect-puppeteer');
const lib = require('../lib/browser');
const help = require('../lib/helper');
require('dotenv').config();

describe('login', () => {
    test('login test', async () => {
        //expect.setDefaultOptions({timeout: 1000});
        await lib.getBrowser('Admin');
        await lib.getCurrenUser(page);
    })
});

afterAll(async () => {
    browser.close();
});
