const expect = require('expect-puppeteer');
const lib = require('../../lib/browser');

require('dotenv').config();

beforeAll(async () => {
    //expect.setDefaultOptions({timeout: 1000});
    await lib.getBrowser('Sales');
});

describe('Product', () => {
    test('MainScreen', async () => {
        await page.waitFor(1000);
        await expect(page).toClick('.tine-mainscreen-centerpanel-west span', {text: 'Produkte'});
        await page.screenshot({path: 'screenshots/8_sales/1_sales_uebersicht.png'});
        await page.screenshot(
            {
                path: 'screenshots/8_sales/2_sales_module.png',
                clip: {x: 0, y: 0, width: 150, height: 300}
            }
        )
    });

    test('open editDialog', async () => {
        await expect(page).toClick('.t-app-sales button', {text: 'Produkt hinzufügen'});
        let newPage = await lib.getNewWindow();
        await newPage.waitFor(5000);
        await newPage.screenshot({path: 'screenshots/8_sales/3_sales_produkt_neu.png'}); //@todo daten eingeben
        await newPage.close();
    });
});

describe('customer', () => {
    test('MainScreen', async () => {
        await page.waitFor(1000);
        await expect(page).toClick('.tine-mainscreen-centerpanel-west span', {text: 'Kunden'});
        await page.waitFor(1000);
    });
    test('open editDialog', async () => {
        await expect(page).toClick('.t-app-sales button', {text: 'Kunde hinzufügen'});
        let newPage = await lib.getNewWindow();
        await newPage.waitFor(5000);
        await newPage.screenshot({path: 'screenshots/8_sales/4_sales_kunden_neu.png'}); //@todo daten eingeben
        await newPage.close();
    });
});

describe('contracts', () => {
    test('MainScreen', async () => {
        await page.waitFor(1000);
        await expect(page).toClick('.tine-mainscreen-centerpanel-west span', {text: 'Verträge'});
    });
    let newPage;
    test('open editDialog', async () => {
        await page.waitFor(1000);
        await expect(page).toClick('.t-app-sales button', {text: 'Vertrag hinzufügen'});
        newPage = await lib.getNewWindow();
        await newPage.waitFor(5000);
        await newPage.screenshot({path: 'screenshots/8_sales/5_sales_vertrag_neu.png'}); //@todo daten eingeben
    });
    test('add product', async () => {
        await expect(newPage).toClick('span', {text: 'Produkte'});
        await newPage.waitFor(1000);
        await newPage.screenshot({path: 'screenshots/8_sales/6_sales_vertrag_neu_produkte.png'});
        await newPage.close();
    });
});

afterAll(async () => {
    browser.close();
});