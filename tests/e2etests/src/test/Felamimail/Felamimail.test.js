const timeout = process.env.SLOWMO ? 30000 : 30000;
const expect = require('expect-puppeteer');
const lib = require('../../lib/browser');
const help = require('../../lib/helper');
require('dotenv').config();

beforeAll(async () => {
    expect.setDefaultOptions({timeout: 1000});
    await lib.getBrowser('E-Mail');
    //console.log('Screenshots form MainScreen');
    await page.screenshot({path: ''});


});

describe('MainScreen', () => {
    test('Grid', async () => {
        await page.screenshot({path: 'screenshots/2_allgemeines/5_allgemein_email.png'});
        await page.screenshot({path: 'screenshots/4_email/2_email_posteingang_geflaggt.png'});
        await page.screenshot({
            path: 'screenshots/4_email/3_email_posteingang_speicherplatz.png',
            clip: {x: 1366 / 2, y: 0, width: 1366 / 2, height: 768 / 2}
        }); //@todo needs Mail data and select one mail
    });

    test('choose grid fields', async () => {
        await expect(page).toMatchElement('span', {text: 'Tine 2.0'});
        //await app.click('.ext-ux-grid-gridviewmenuplugin-menuBtn.x-grid3-hd-btn');
        await page.click('.t-app-felamimail .ext-ux-grid-gridviewmenuplugin-menuBtn.x-grid3-hd-btn');
        await page.waitFor('.x-menu-list');
        await page.screenshot({
            path: 'screenshots/2_allgemeines/6_allgemein_email_spaltenauswahl.png',  //@ todo x/y of element +/-.
            clip: {x: 1000, y: 0, width: 1366 - 1000, height: 768}
        });
        await page.screenshot({
            path: 'screenshots/4_email/1_email_favoriten_und_konten.png',
            clip: {x: 0, y: 0, width: 200, height: 400}
        })
    });
});

describe('editDialog', () => {
    let newPage;
    test('open editDialog', async () => {
        await expect(page).toClick('button', {text: 'Konto hinzufügen'});
        newPage = await lib.getNewWindow();
        await newPage.waitFor(2000);
        await expect(newPage).toFill('input[name=from]', 'Rauch, Tim');
        await newPage.screenshot({path: 'screenshots/4_email/4_email_neues_konto.png'});
    });
    test('imap', async () => {
        await expect(newPage).toClick('span', {text: 'IMAP'});
        await newPage.waitFor(1000);
        await expect(newPage).toFill('input[name=host]', 'mail.tine20.net');
        await newPage.screenshot({path: 'screenshots/4_email/5_email_neues_konto_imap.png'});
    });
    test('smtp', async () => {
        await expect(newPage).toClick('span', {text: 'SMTP'});
        await newPage.waitFor(1000);
        await expect(newPage).toFill('input[name=smtp_hostname]', 'mail.tine20.net');
        await newPage.screenshot({path: 'screenshots/4_email/6_email_neues_konto_smtp.png'});
    });
    test('other settings', async () => {
        await newPage.waitFor(1000);
        await expect(newPage).toClick('span', {text: 'Andere Einstellungen'});
        await newPage.screenshot({path: 'screenshots/4_email/7_email_neues_konto_andere.png'});
    })
});

describe.skip('filterBar', () => {

    test('default search', async () => {
        try {
            await expect(page).toClick('.t-app-felamimail button', {text: 'Details verbergen'});
        } catch (e) {

        }
        await page.screenshot({
            path: 'screenshots/2_allgemeines/8_allgemein_suchfilter.png'
            , clip: {x: 1000, y: 0, width: 1366 - 1000, height: 100}
        });
        await page.type('.t-app-felamimail .x-toolbar-right-row .x-form-text.x-form-field.x-form-empty-field', 'Test Search');
        await page.keyboard.press('Enter');
        await page.screenshot({
            path: 'screenshots/2_allgemeines/9_allgemein_suchfilter_x_button.png'
            , clip: {x: 1000, y: 0, width: 1366 - 1000, height: 100}
        });
    });

    test('details display', async () => {
        try {
            await expect(page).toClick('.t-app-felamimail button', {text: 'Details anzeigen'});
        } catch (e) {

        }
        let arrowtrigger = await page.$$('.t-app-felamimail .x-form-arrow-trigger');
        await arrowtrigger[0].click();
        await page.screenshot({path: 'screenshots/2_allgemeines/7_allgemein_email_suchoptionen.png'});
        await page.keyboard.press('Escape');
    });

    test('delete all filter', async () => {
        await page.click('.t-app-felamimail .action_addFilter');
        await page.waitFor('.t-app-felamimail .action_delAllFilter');

        await page.hover('.t-app-felamimail .action_delAllFilter');
        await page.screenshot({
            path: 'screenshots/2_allgemeines/10_allgemein_alle_filter_zuruecksetzen.png',
            clip: {x: 850, y: 0, width: 1366 - 850, height: 150}
        });
        await page.click('.t-app-felamimail .action_delAllFilter');
        //console.log('delete all filter!');
    });

    test('operator for filter', async () => {
        await page.waitFor(2000);
        let arrowtrigger = await page.$$('.t-app-felamimail .x-form-arrow-trigger');
        await arrowtrigger[0].click();
        await page.waitFor(2000);
        await page.click('.x-combo-list-item.tw-ftb-field-path');
        await page.waitFor(2000);
        arrowtrigger = await page.$$('.t-app-felamimail .x-form-arrow-trigger');
        await arrowtrigger[1].click();
        await page.screenshot({path: 'screenshots/2_allgemeines/11_allgemein_email_suchfilter_operatoren.png'});
    });

    test('alternate filter', async () => {
        await expect(page).toClick('.t-app-felamimail span', {text: 'oder alternativ'});
        await page.hover('.t-app-felamimail .action_addFilter');
        await page.waitFor(500);
        await page.screenshot({
            path: 'screenshots/2_allgemeines/12_allgemein_alternative_oder_filter.png',
            clip: {x: 850, y: 0, width: 1366 - 850, height: 200}
        });
        await expect(page).toClick('.t-app-felamimail span', {text: 'Alternativen Filter hinzufügen'});
        await page.waitFor(500);
        await page.screenshot({
            path: 'screenshots/2_allgemeines/13_allgemein_weitere_alternative_filter.png',
            clip: {x: 850, y: 0, width: 1366 - 850, height: 200}
        });
        await page.keyboard.press('Enter');
        await page.click('.t-app-felamimail .tw-ftb-filterstructure-treepanel .x-tree-selected .x-tree-node-anchor', {button: 'right'});
        await page.screenshot({
            path: 'screenshots/2_allgemeines/14_allgemein_alternativen_filter_entfernen.png',
            clip: {x: 850, y: 0, width: 1366 - 850, height: 200}
        });
        await page.click('.x-menu-item-icon.action_remove');

    });

    test('save filter as favoriten', async () => {

        await page.click('.t-app-felamimail .action_addFilter');
        await page.click('.t-app-felamimail .action_saveFilter');
        await page.waitFor(500);
        await page.type('.x-form-text.x-form-field.x-form-invalid', 'Filter');
        await page.screenshot({path: 'screenshots/2_allgemeines/15_allgemein_email_filter_favoriten.png'});
    });
});

afterAll(async () => {
    browser.close();
});