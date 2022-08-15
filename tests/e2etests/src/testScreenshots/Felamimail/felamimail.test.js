const expect = require('expect-puppeteer');
const lib = require('../../lib/browser');

require('dotenv').config();

beforeAll(async () => {
    //expect.setDefaultOptions({timeout: 1000});
    await lib.getBrowser('E-Mail');
    //console.log('Screenshots form MainScreen');
    await page.screenshot({path: ''});

});

describe('MainScreen', () => {
    test('Grid', async () => {
        await page.screenshot({path: 'screenshots/StandardBedienhinweise/5_standardbedienhinweise_email.png'});
        await page.screenshot({path: 'screenshots/EMail/2_email_posteingang_geflaggt.png'});
        await page.screenshot({
            path: 'screenshots/EMail/3_email_posteingang_speicherplatz.png',
            clip: {x: 1366 / 2, y: 0, width: 1366 / 2, height: 768 / 2}
        }); //@todo needs Mail data and select one mail
    });

    test('choose grid fields', async () => {
        await expect(page).toMatchElement('span', {text: process.env.TEST_USER});
        //await app.click('.ext-ux-grid-gridviewmenuplugin-menuBtn.x-grid3-hd-btn');
        await page.click('.t-app-felamimail .ext-ux-grid-gridviewmenuplugin-menuBtn.x-grid3-hd-btn');
        await page.waitForSelector('.x-menu-list');
        await page.screenshot({
            path: 'screenshots/StandardBedienhinweise/6_standardbedienhinweise_email_spaltenauswahl.png',  //@ todo x/y of element +/-.
            clip: {x: 1000, y: 0, width: 1366 - 1000, height: 500}
        });
        await page.screenshot({
            path: 'screenshots/EMail/1_email_favoriten_und_konten.png',
            clip: {x: 0, y: 0, width: 200, height: 400}
        })
    });
});

describe('editDialog', () => {
    let newPage;
    test('open editDialog', async () => {
        await expect(page).toClick('button', {text: 'Konto hinzufügen'});
        newPage = await lib.getNewWindow();
        await newPage.waitForTimeout(2000);
        await expect(newPage).toFill('input[name=from]', 'Rauch, Tim');
        await newPage.screenshot({path: 'screenshots/EMail/4_email_neues_konto.png'});
    });
    test('imap', async () => {
        await expect(newPage).toClick('span', {text: 'IMAP'});
        await newPage.waitForTimeout(1000);
        await expect(newPage).toFill('input[name=host]', 'mail.tine20.net');
        await newPage.screenshot({path: 'screenshots/EMail/5_email_neues_konto_imap.png'});
    });
    test('smtp', async () => {
        await expect(newPage).toClick('span', {text: 'SMTP'});
        await newPage.waitForTimeout(1000);
        await expect(newPage).toFill('input[name=smtp_hostname]', 'mail.tine20.net');
        await newPage.screenshot({path: 'screenshots/EMail/6_email_neues_konto_smtp.png'});
    });
    test('other settings', async () => {
        await newPage.waitForTimeout(1000);
        await expect(newPage).toClick('span', {text: 'Andere Einstellungen'});
        await newPage.screenshot({path: 'screenshots/EMail/7_email_neues_konto_andere.png'});
        await newPage.close()
    })
});

describe('context menu', () => {
    let mail;
    test('open context menu', async () => {
        await page.waitForTimeout(1000);
        mail = await lib.getCurrentUser(page);
        await expect(page).toClick('span', {text: mail.accountEmailAddress, button: 'right'}); // @todo currten user mail
        await page.waitForTimeout(1000);
        await page.hover('.x-menu-item-icon.action_add');
        await page.screenshot({path: 'screenshots/EMail/8_email_konto_kontextmenu.png'});
    });
    test.skip('absence note', async () => {
        await page.hover('.x-menu-item-icon.action_email_replyAll');
        await page.screenshot({path: 'screenshots/EMail/18_email_server_kontextmenu.png'});
        await page.click('.x-menu-item-icon.action_email_replyAll');
        let newPage = await lib.getNewWindow();
        await newPage.waitForTimeout(2000);
        await newPage.click('.x-form-text.x-form-field.x-trigger-noedit[name=enabled]');
        await newPage.waitForTimeout(500);
        await newPage.screenshot({path: 'screenshots/EMail/10_email_abwesenheitsnotiz.png'});
        await newPage.close();
    });
    test.skip('add mail filter', async () => {
        await expect(page).toClick('span', {text: mail.accountEmailAddress, button: 'right'});
        await page.waitForTimeout(1000);
        await expect(page).toClick('.x-menu-item-icon.action_email_forward');
        let newPage = await lib.getNewWindow();
        await newPage.waitForTimeout(2000);
        await newPage.click('.x-btn-image.action_add');
        let popup = await lib.getNewWindow();
        await popup.waitForTimeout(2000);
        await popup.screenshot({path: 'screenshots/EMail/12_email_filterregeln_editieren.png'});
        let combo = await popup.$$('.x-form-trigger.x-form-arrow-trigger');
        await combo[1].click();
        await popup.hover('.x-combo-list-item.tw-ftb-field-subject');
        await popup.screenshot({path: 'screenshots/EMail/13_email_filterregeln_auswahl.png'});
        await popup.click('.x-combo-list-item.tw-ftb-field-subject');
        await popup.type('.x-form-text.x-form-field.x-form-empty-field', 'Einladung zum Termin');
        await popup.keyboard.press('Enter');
        combo = await popup.$$('.x-form-trigger.x-form-arrow-trigger');
        await combo[3].click();
        await popup.waitForTimeout(1000);
        await popup.screenshot({path: 'screenshots/EMail/14_email_filteraktion_auswahl.png'});
        await popup.close();
        await newPage.screenshot({path: 'screenshots/EMail/11_email_empfangsfilter.png'});
        await newPage.close();
    });
    test('create folder', async () => {
        await expect(page).toClick('span', {text: 'Posteingang', button: 'right'});
        await page.waitForTimeout(1000);
        await page.screenshot({path: 'screenshots/EMail/9_email_ordner_kontextmenu.png'});
        await page.keyboard.press('Escape')
    });
});

describe('filterBar', () => {

    test('default search', async () => {
        try {
            await expect(page).toClick('.t-app-felamimail button', {text: 'Details verbergen'});
        } catch (e) {
            console.log('details also not activate')
        }
        await page.screenshot({
            path: 'screenshots/StandardBedienhinweise/8_standardbedienhinweise_suchfilter.png'
            , clip: {x: 1000, y: 0, width: 1366 - 1000, height: 100}
        });
        await page.type('.t-app-felamimail .x-toolbar-right-row .x-form-text.x-form-field.x-form-empty-field', 'Test Search');
        await page.keyboard.press('Enter');
        await page.screenshot({
            path: 'screenshots/StandardBedienhinweise/9_standardbedienhinweise_suchfilter_x_button.png'
            , clip: {x: 1000, y: 0, width: 1366 - 1000, height: 100}
        });
    });

    test('details display', async () => {
        try {
            await expect(page).toClick('.t-app-felamimail button', {text: 'Details anzeigen'});
        } catch (e) {
            console.log('details also activate')
        }
        await expect(page).toMatchElement('button', {text: 'Suche starten', visible: true})
        let arrowtrigger = await page.$$('.t-app-felamimail .tw-filtertoolbar .x-form-arrow-trigger');
        await arrowtrigger[0].click();
        await page.screenshot({path: 'screenshots/StandardBedienhinweise/7_standardbedienhinweise_email_suchoptionen.png'});
        await page.keyboard.press('Escape');
    });

    test('delete all filter', async () => {
        await page.click('.t-app-felamimail .action_addFilter');
        await page.waitForSelector('.t-app-felamimail .action_delAllFilter');

        await page.hover('.t-app-felamimail .action_delAllFilter');
        await page.screenshot({
            path: 'screenshots/StandardBedienhinweise/110_standardbedienhinweise_alle_filter_zuruecksetzen.png',
            clip: {x: 850, y: 0, width: 1366 - 850, height: 150}
        });
        await page.click('.t-app-felamimail .action_delAllFilter');
        //console.log('delete all filter!');
    });

    test('operator for filter', async () => {
        await page.waitForTimeout(2000);
        let arrowtrigger = await page.$$('.t-app-felamimail .x-form-arrow-trigger');
        await arrowtrigger[0].click();
        await page.waitForTimeout(2000);
        await page.click('.x-combo-list-item.tw-ftb-field-path');
        await page.waitForTimeout(2000);
        arrowtrigger = await page.$$('.t-app-felamimail .x-form-arrow-trigger');
        await arrowtrigger[1].click();
        await page.screenshot({path: 'screenshots/StandardBedienhinweise/11_standardbedienhinweise_email_suchfilter_operatoren.png'});
    });

    test('alternate filter', async () => {
        await expect(page).toClick('.t-app-felamimail span', {text: 'oder alternativ'});
        await page.hover('.t-app-felamimail .action_addFilter');
        await page.waitForTimeout(500);
        await page.screenshot({
            path: 'screenshots/StandardBedienhinweise/12_standardbedienhinweise_alternative_oder_filter.png',
            clip: {x: 850, y: 0, width: 1366 - 850, height: 200}
        });
        await expect(page).toClick('.t-app-felamimail span', {text: 'Alternativen Filter hinzufügen'});
        await page.waitForTimeout(500);
        await page.screenshot({
            path: 'screenshots/StandardBedienhinweise/13_standardbedienhinweise_weitere_alternative_filter.png',
            clip: {x: 850, y: 0, width: 1366 - 850, height: 200}
        });
        await page.keyboard.press('Enter');
        await page.click('.t-app-felamimail .tw-ftb-filterstructure-treepanel .x-tree-selected .x-tree-node-anchor', {button: 'right'});
        await page.screenshot({
            path: 'screenshots/StandardBedienhinweise/14_standardbedienhinweise_alternativen_filter_entfernen.png',
            clip: {x: 850, y: 0, width: 1366 - 850, height: 200}
        });
        await page.click('.x-menu-item-icon.action_remove');

    });

    test('save filter as favoriten', async () => {

        await page.click('.t-app-felamimail .action_addFilter');
        await page.click('.t-app-felamimail .action_saveFilter');
        await page.waitForTimeout(500);
        await page.type('.x-form-text.x-form-field.x-form-invalid', 'Filter');
        await page.screenshot({path: 'screenshots/EMail/15_allgemein_email_filter_favoriten.png'});
        await page.screenshot({path: 'screenshots/StandardBedienhinweise/15_standardbedienhinweise_email_filter_favoriten.png'});
        await page.keyboard.press('Escape');
        await page.waitForTimeout(500);
        try {
            await page.click('.x-btn-image.action_delAllFilter');
        } catch (e) {
            
        }
    });
});

describe.skip('write E-Mail', () => {
    test('open editDialog', async () => {
        let popupWindow = await lib.getEditDialog('Verfassen');
        await popupWindow.waitForTimeout(2000);
        await popupWindow.click('.x-btn-image.AddressbookIconCls');
        let popup = await lib.getNewWindow();
        await popup.waitForTimeout(2000);
        await popup.screenshot({path: 'screenshots/EMail/16_email_auswahl_empfaenger.png'});
        await popup.close();
        await popupWindow.close();
    });
});

afterAll(async () => {
    browser.close();
});
