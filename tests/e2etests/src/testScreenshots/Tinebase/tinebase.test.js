const expect = require('expect-puppeteer');
const lib = require('../../lib/browser');
require('dotenv').config();

beforeAll(async () => {
    //expect.setDefaultOptions({timeout: 1000});
    await lib.getBrowser('Adressbuch');
});

describe('mainScreen', () => {
    let Apps = ['Admin', 'Adressbuch', 'Dateimanager', 'Kalender', 'Crm', 'Aufgaben', 'E-Mail', 'Sales', 'Human Resources', 'Zeiterfassung', 'Inventarisierung'];

    test('all apps', async () => {
        for (let i = 0; i < Apps.length; i++) {
            try {
                await page.waitForTimeout(500);
                await expect(page).toClick('span', {text: 'tine ® Admin'});
                await page.waitForTimeout(500);
                await expect(page).toClick('span', {text: Apps[i]});
            } catch (e) {
                //console.log('Application ' + Apps[i] + ' don´t install');
            }
        }
        await expect(page).toClick('span', {text: process.env.TEST_BRANDING_TITLE});
        await page.waitForTimeout(500);
        await expect(page).toClick('span', {text: 'Adressbuch'});
        await page.waitForTimeout(5000);
        await page.screenshot({path: 'screenshots/StandardBedienhinweise/1_standardbedienhinweise_alle_reiter.png'});
    })
});

describe('usersettings', () => {
    let newPage;
    let settings;
    test('open usersettings', async () => {
        await page.click('.x-btn-text.tine-grid-row-action-icon.renderer_accountUserIcon');
        await page.waitForTimeout(2000);
        settings = await page.$$('.x-menu.x-menu-floating.x-layer .x-menu-item-icon.action_adminMode');
        await settings[1].hover();
        await page.screenshot({
            path: 'screenshots/Benutzereinstellungen/1_benutzereinstellungen_link.png'
            , clip: {x: 1000, y: 0, width: 1366 - 1000, height: 100}
        });
    });
    test('usersettings', async () => {
        await settings[1].click();
        newPage = await lib.getNewWindow();
        await newPage.waitForTimeout(2000);
        await newPage.screenshot({path: 'screenshots/Benutzereinstellungen/2_benutzereinstellungen_generelle_einstellungen.png'});
    });

    test('appsettings', async () => {
        await getSettingScreenshots(newPage, 'Mein Profil', '4_benutzereinstellungen_profil');
        await getSettingScreenshots(newPage, 'ActiveSync', '5_benutzereinstellungen_activesync');
        await getSettingScreenshots(newPage, 'Zeiterfassung', '6_benutzereinstellungen_zeiterfassung');
        await getSettingScreenshots(newPage, 'Inventarisierung', '7_benutzereinstellungen_inventar');
        await getSettingScreenshots(newPage, 'E-Mail', '9_benutzereinstellungen_email');
        await getSettingScreenshots(newPage, 'Crm', '10_benutzereinstellungen_crm');
        await getSettingScreenshots(newPage, 'Kalender', '11_benutzereinstellungen_kalender');
        await getSettingScreenshots(newPage, 'Adressbuch', '12_benutzereinstellungen_adressbuch');
        await getSettingScreenshots(newPage, 'Aufgaben', '8_benutzereinstellungen_aufgaben');
    });


    test('admin mode', async () => {
        await expect(newPage).toClick('span', {text: 'Generelle Einstellungen'});
        await newPage.waitForTimeout(1000);
        await newPage.click('.x-btn-image.action_adminMode');
        await newPage.waitForTimeout(2000);
        await newPage.screenshot({path: 'screenshots/Benutzereinstellungen/3_benutzereinstellungen_generelle_einstellungen_adminmodus.png'});
        await expect(newPage).toClick('button', {text: 'Abbrechen'});
    });
});

afterAll(async () => {
    browser.close();
});

async function getSettingScreenshots(newPage, text, screenName) {
    await expect(newPage).toClick('span', {text: text});
    await newPage.waitForTimeout(1000);
    await newPage.screenshot({path: 'screenshots/Benutzereinstellungen/' + screenName + '.png'});
}
