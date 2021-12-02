const expect = require('expect-puppeteer');
const lib = require('../../lib/browser');

require('dotenv').config();

beforeAll(async () => {
    //expect.setDefaultOptions({timeout: 1000});
    await lib.getBrowser('Human Resources');
    // modules
    await page.screenshot({
        path: 'screenshots/HumanResources/1_humanresources_module.png',
        clip: {x: 0, y: 0, width: 150, height: 300}
    })
});

describe('MainScreen', () => {

});

describe.skip('employee', () => {
    let newPage;
    test('open EditDialog', async () => {
        await expect(page).toClick('.tine-mainscreen-centerpanel-west span', {text: 'Mitarbeiter'});
        var [button] = await lib.getElement('button', page, 'Mitarbeiter hinzufügen');
        await button.click();
        //console.log('Klick Button');
        newPage = await lib.getNewWindow();
        await newPage.waitForTimeout(5000); //@todo wait for selector etc...
        await newPage.screenshot({path: 'screenshots/StandardBedienhinweise/16_standardbedienhinweise_hr_eingabemaske_neu.png'});
        await newPage.screenshot({path: 'screenshots/HumanResources/6_humanresources_mitarbeiter_dialog.png'});
        let stick = await newPage.$$('button');
        await stick[1].hover();
        await newPage.waitForTimeout(1000);
        await newPage.screenshot({
            path: 'screenshots/HumanResources/7_humanresources_mitarbeiter_kontaktdaten_zauberstab.png',
            clip: {x: 0, y: 0, width: 1366, height: (768 / 3)}
        });
    });

    test('costcenter', async () => {
        await expect(newPage).toClick('span', {text: 'Kostenstellen'});
        await newPage.screenshot({path: 'screenshots/HumanResources/8_humanresources_mitarbeiter_kostenstellen.png'});
    });

    test('contracts', async () => {
        await expect(newPage).toClick('span', {text: 'Verträge'});
        await newPage.waitForTimeout(1000);
        await newPage.screenshot({path: 'screenshots/HumanResources/9_humanresources_mitarbeiter_vertraege.png'});
        await expect(newPage).toClick('button', {text: 'Vertrag hinzufügen'});
        let popup = await lib.getNewWindow();
        await popup.waitForTimeout(2000);
        await popup.screenshot({path: 'screenshots/HumanResources/10_humanresources_mitarbeiter_vertragsdialog.png'});
        await popup.close();
    });

    test('add Tag', async () => {
        await expect(newPage).toClick('span', {text: 'Mitarbeiter'});
        //await newPage.waitForTimeout(1000);
        let arrowtrigger = await newPage.$$('.x-form-arrow-trigger');
        arrowtrigger.forEach(async function (element, index) {
            //console.log(index + ' \n ' + element);
            if (index == 5) await element.click();
        });
        await newPage.waitForSelector('.x-widget-tag-tagitem-text');
        await newPage.screenshot({path: 'screenshots/StandardBedienhinweise/19_standardbedienhinweise_hr_eingabemaske_neu_tag.png'});
        let btn_text = await newPage.$$('.x-btn-text');
        btn_text.forEach(async function (element, index) {
            //console.log(index + ' \n ' + element);
            if (index == 2) await element.click();
        });
        await newPage.waitForSelector('.ext-mb-input');
        await expect(newPage).toFill('.ext-mb-input', 'Persönlicher Tag');
        await newPage.screenshot({path: 'screenshots/StandardBedienhinweise/20_standardbedienhinweise_hr_eingabemaske_neu_tag_auswahl.png'});
        await newPage.keyboard.press('Escape');
        await newPage.close();
    });

    describe('edit employee', () => {
        let newPage;
        test('open', async () => {
            let row = await page.$$('.t-app-humanresources .t-contenttype-employee .x-grid3-row');
            await row[3].click({clickCount: 2});
            newPage = await lib.getNewWindow();
        });

        test('vacation', async () => {
            await newPage.waitForTimeout(2000);
            await expect(newPage).toClick('span', {text: 'Urlaub'});
            await newPage.waitForTimeout(2000);
            await newPage.screenshot({path: 'screenshots/HumanResources/11_humanresources_mitarbeiter_vertraege.png'});
            await expect(newPage).toClick('button', {text: 'Urlaubstage hinzufügen'});
            let popup = await lib.getNewWindow();
            await popup.waitForTimeout(2000);
            await popup.screenshot({path: 'screenshots/HumanResources/12_humanresources_mitarbeiter_urlaub.png'});
            await expect(popup).toClick('button', {text: 'Abbrechen'});
            await newPage.close();
        })
    })
});

describe.skip('employee accounts', () => {
    let newPage;
    test('mainpage', async () => {
        await expect(page).toClick('.tine-mainscreen-centerpanel-west span', {text: 'Personalkonten'});
        await page.waitForTimeout(2000);
        let row = await page.$$('.t-app-humanresources .t-contenttype-account .x-grid3-row');
        await page.waitForTimeout(2000);
        await row[2].click({clickCount: 2});
        newPage = await lib.getNewWindow();
        await newPage.waitForTimeout(5000);
        await newPage.screenshot({path: 'screenshots/HumanResources/14_humanresources_personalkonten_dialog.png'});
    });

    // not in BE
    test.skip('special leave', async () => {
        await expect(newPage).toClick('span', {text: 'Sonderurlaub'});
        await page.waitForTimeout(2000);
        await expect(newPage).toClick('button', {text: 'Sonderurlaub hinzufügen'});
        let popup = await lib.getNewWindow();
        await popup.waitForTimeout(2000);
        await popup.screenshot({path: 'screenshots/HumanResources/16_humanresources_personalkonten_sonderurlaub.png'});
    });
});

afterAll(async () => {
    browser.close();
});