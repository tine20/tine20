const expect = require('expect-puppeteer');
const lib = require('../../lib/browser');

require('dotenv').config();

beforeAll(async () => {
    //expect.setDefaultOptions({timeout: 1000});
    await lib.getBrowser('Human Resources');
    // modules
    await page.screenshot({
        path: 'screenshots/10_humanresources/1_human_module.png',
        clip: {x: 0, y: 0, width: 150, height: 300}
    })
});

describe('MainScreen', () => {

});

describe('employee', () => {
    let newPage;
    test('open EditDialog', async () => {
        await expect(page).toClick('.tine-mainscreen-centerpanel-west span', {text: 'Mitarbeiter'});
        var [button] = await lib.getElement('button', page, 'Mitarbeiter hinzufügen');
        await button.click();
        //console.log('Klick Button');
        newPage = await lib.getNewWindow();
        await newPage.waitFor(5000); //@todo wait for selector etc...
        await newPage.screenshot({path: 'screenshots/2_allgemeines/16_allgemein_hr_eingabemaske_neu.png'});
        await newPage.screenshot({path: 'screenshots/10_humanresources/2_human_mitarbeiter_hinzu.png'});
        let stick = await newPage.$$('button');
        await stick[1].hover();
        await newPage.waitFor(1000);
        await newPage.screenshot({
            path: 'screenshots/10_humanresources/3_human_mitarbeiter_kontaktdaten_zauberstab.png',
            clip: {x: 0, y: 0, width: 1366, height: (768 / 3)}
        });
    });

    test('costcenter', async () => {
        await expect(newPage).toClick('span', {text: 'Kostenstellen'});
        await newPage.screenshot({path: 'screenshots/10_humanresources/4_human_mitarbeiter_kostenstellen.png'});
    });

    test('contracts', async () => {
        await expect(newPage).toClick('span', {text: 'Verträge'});
        await newPage.waitFor(1000);
        await expect(newPage).toClick('button', {text: 'Vertrag hinzufügen'});
        let popup = await lib.getNewWindow();
        await popup.waitFor(2000);
        await popup.screenshot({path: 'screenshots/10_humanresources/6_human_mitarbeiter_vertrag.png'});
        await popup.close();
    });

    test('add Tag', async () => {
        await expect(newPage).toClick('span', {text: 'Mitarbeiter'});
        //await newPage.waitFor(1000);
        let arrowtrigger = await newPage.$$('.x-form-arrow-trigger');
        arrowtrigger.forEach(async function (element, index) {
            //console.log(index + ' \n ' + element);
            if (index == 5) await element.click();
        });
        await newPage.waitFor('.x-widget-tag-tagitem-text');
        await newPage.screenshot({path: 'screenshots/2_allgemeines/19_allgemein_hr_eingabemaske_neu_tag.png'});
        let btn_text = await newPage.$$('.x-btn-text');
        btn_text.forEach(async function (element, index) {
            //console.log(index + ' \n ' + element);
            if (index == 2) await element.click();
        });
        await newPage.waitFor('.ext-mb-input');
        await expect(newPage).toFill('.ext-mb-input', 'Persönlicher Tag');
        await newPage.screenshot({path: 'screenshots/2_allgemeines/20_allgemein_hr_eingabemaske_neu_tag_auswahl.png'});
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
            await newPage.waitFor(2000);
            await expect(newPage).toClick('span', {text: 'Urlaub'});
            await newPage.waitFor(2000);
            await newPage.screenshot({path: 'screenshots/10_humanresources/7_human_mitarbeiter_urlaub.png'});
            await expect(newPage).toClick('button', {text: 'Urlaubstage hinzufügen'});
            let popup = await lib.getNewWindow();
            await popup.waitFor(2000);
            await popup.screenshot({path: 'screenshots/10_humanresources/8_human_mitarbeiter_urlaubstage.png'});
            await expect(popup).toClick('button', {text: 'Abbrechen'});
            await expect(newPage).toClick('button', {text: 'Abbrechen'});
        })
    })
});

describe('employee accounts', () => {
    let newPage;
    test('mainpage', async () => {
        await expect(page).toClick('.tine-mainscreen-centerpanel-west span', {text: 'Personalkonten'});
        await page.waitFor(2000);
        let row = await page.$$('.t-app-humanresources .t-contenttype-account .x-grid3-row');
        await page.waitFor(2000);
        await row[2].click({clickCount: 2});
        newPage = await lib.getNewWindow();
        await newPage.waitFor(5000);
        await newPage.screenshot({path: 'screenshots/10_humanresources/10_human_personalkonto.png'});
    });

    // not in BE
    test.skip('special leave', async () => {
        await expect(newPage).toClick('span', {text: 'Sonderurlaub'});
        await page.waitFor(2000);
        await expect(newPage).toClick('button', {text: 'Sonderurlaub hinzufügen'});
        let popup = await lib.getNewWindow();
        await popup.waitFor(2000);
        await popup.screenshot({path: 'screenshots/10_humanresources/12_human_personalkonto_sonderurlaub.png'});
    });
});

afterAll(async () => {
    browser.close();
});