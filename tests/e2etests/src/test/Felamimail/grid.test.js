const expect = require('expect-puppeteer');
const lib = require('../../lib/browser');
require('dotenv').config();

beforeAll(async () => {
    await lib.getBrowser('E-Mail');
});

describe('grid adopts to folder selected', () => {
    test('select Sent folder', async () => {
        // select 'Sent' folder -> expect 'from_email' and from_name to disappear, to should appear
        expect(page).toClick('.felamimail-node-sent .x-tree-node-anchor', {text:'Gesendet'});

        // TODO replace waitFor with a selector (use loading animation?)
        await page.waitFor(5000); // wait for columns to be hidden/shown

        // TODO make this work
        // await lib.checkDisplayOfElement(page, '.x-grid3-hd.x-grid3-cell.x-grid3-td-from_email', false);
        // await lib.checkDisplayOfElement(page, '.x-grid3-hd.x-grid3-cell.x-grid3-td-from_name', false);
        // await lib.checkDisplayOfElement(page, '.x-grid3-hd.x-grid3-cell.x-grid3-td-to', true);

        // just for debugging
        // await page.screenshot({path: 'screenshots/4_email/grid_test_sent.png'});

        let from_email_td_display = await page.evaluate(() => document.querySelector(
            '.x-grid3-hd.x-grid3-cell.x-grid3-td-from_email').style.display);
        if (from_email_td_display !== 'none') {
            return Promise.reject('Error: from_name still visible');
        }
        let from_name_td_display = await page.evaluate(() => document.querySelector(
            '.x-grid3-hd.x-grid3-cell.x-grid3-td-from_name').style.display);
        if (from_name_td_display !== 'none') {
            return Promise.reject('Error: from_name still visible');
        }
        let to_td_display = await page.evaluate(() => document.querySelector(
            '.x-grid3-hd.x-grid3-cell.x-grid3-td-to').style.display);
        if (to_td_display === 'none') {
            return Promise.reject('Error: to still invisible');
        }
    });

    test('select INBOX', async () => {
        // select 'INBOX' folder -> expect 'from_email' and from_name to appear, 'to' should disappear
        expect(page).toClick('.felamimail-node-inbox .x-tree-node-anchor', {text:'Posteingang'});

        // TODO replace waitFor with a selector (use loading animation?)
        await page.waitFor(5000); // wait for columns to be hidden/shown

        let from_email_td_display = await page.evaluate(() => document.querySelector(
            '.x-grid3-hd.x-grid3-cell.x-grid3-td-from_email').style.display);
        if (from_email_td_display === 'none') {
            return Promise.reject('Error: from_name still invisible');
        }
        let from_name_td_display = await page.evaluate(() => document.querySelector(
            '.x-grid3-hd.x-grid3-cell.x-grid3-td-from_name').style.display);
        if (from_name_td_display === 'none') {
            return Promise.reject('Error: from_name still invisible');
        }
        let to_td_display = await page.evaluate(() => document.querySelector(
            '.x-grid3-hd.x-grid3-cell.x-grid3-td-to').style.display);
        if (to_td_display !== 'none') {
            return Promise.reject('Error: to still visible');
        }
    });

    // TODO add this again when we can be sure that the folder exists in the test account
    // test('select Drafts folder', async () => {
    //     // just for debugging
    //     //await page.screenshot({path: 'screenshots/4_email/grid_test.png'});
    //
    //     // select 'Drafts' folder -> expect 'from_email' and from_name to disappear, to should appear
    //     expect(page).toClick('.felamimail-node-drafts .x-tree-node-anchor', {text:'Entwürfe'});
    //
    //     // TODO replace waitFor with a selector (use loading animation?)
    //     await page.waitFor(5000); // wait for columns to be hidden/shown
    //
    //     // just for debugging
    //     await page.screenshot({path: 'screenshots/4_email/grid_test_drafts.png'});
    //
    //     let from_email_td_display = await page.evaluate(() => document.querySelector(
    //         '.x-grid3-hd.x-grid3-cell.x-grid3-td-from_email').style.display);
    //     if (from_email_td_display !== 'none') {
    //         return Promise.reject('Error: from_name still visible');
    //     }
    //     let from_name_td_display = await page.evaluate(() => document.querySelector(
    //         '.x-grid3-hd.x-grid3-cell.x-grid3-td-from_name').style.display);
    //     if (from_name_td_display !== 'none') {
    //         return Promise.reject('Error: from_name still visible');
    //     }
    //     let to_td_display = await page.evaluate(() => document.querySelector(
    //         '.x-grid3-hd.x-grid3-cell.x-grid3-td-to').style.display);
    //     if (to_td_display === 'none') {
    //         return Promise.reject('Error: to still invisible');
    //     }
    // });
});

afterAll(async () => {
    browser.close();
});
