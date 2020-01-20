const expect = require('expect-puppeteer');
const lib = require('../../lib/browser');
const help = require('../../lib/helper');
require('dotenv').config();

beforeAll(async () => {
    expect.setDefaultOptions({timeout: 1000});
    await lib.getBrowser('Zeiterfassung');
    await page.screenshot({
        path: 'screenshots/11_zeiterfassung/1_zeiterfassung_module.png',
        clip: {x: 0, y: 0, width: 150, height: 300}
    })
    await page.waitFor(2000);
});

describe('timeaccount', () => {
    describe('Edit Contact', () => {
        test('mainpage', async () => {
            await expect(page).toClick('.tine-mainscreen-centerpanel-west span', {text: 'Zeitkonten'});
            await page.waitFor(1000);
        });
        test('open EditDialog', async () => {
            await expect(page).toClick('button', {text: 'Zeitkonto hinzufügen'});
            //console.log('Klick Button');
            newPage = await lib.getNewWindow();
            await newPage.waitFor(5000);
            await newPage.screenshot({path: 'screenshots/11_zeiterfassung/2_zeiterfassung_zeitkonto_neu.png'});
            //console.log('Get Popup');
        });

        test('premissions', async () => {
            await expect(newPage).toClick('span', {text: 'Zugang'});
            await newPage.waitFor(2000);
            await newPage.screenshot({path: 'screenshots/11_zeiterfassung/3_zeiterfassung_zeitkonto_rechte.png'});
            await expect(newPage).toClick('button', {text: 'Abbrechen'});
        })
    });
});

describe('timetracker', () => {
    describe('Edit Contact', () => {
        test('mainpage', async () => {
            await expect(page).toClick('.tine-mainscreen-centerpanel-west span', {text: 'Stundenzettel'});
            await page.waitFor(1000);
        });

        test('open EditDialog', async () => {
            await expect(page).toClick('button', {text: 'Stundenzettel hinzufügen'});
            //await expect(page).toClick('.x-btn-split .x-btn-text button', {text: 'Stundenzettel hinzufügen'});
            //console.log('Klick Button');
            newPage = await lib.getNewWindow();
            await newPage.waitFor(5000);
            await newPage.screenshot({path: 'screenshots/11_zeiterfassung/4_zeiterfassung_stundenzettel_neu.png'});
            await expect(newPage).toClick('button', {text: 'Abbrechen'});
            //console.log('Get Popup');
        });
    });
});

afterAll(async () => {
    browser.close();
});
