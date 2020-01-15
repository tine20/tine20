const timeout = process.env.SLOWMO ? 30000 : 30000;
const expect = require('expect-puppeteer');
const lib = require('../../lib/browser');
const help = require('../../lib/helper');
require('dotenv').config();

beforeAll(async () => {
    expect.setDefaultOptions({timeout: 1000});
    await lib.getBrowser('Kalender');
});

describe('MainScreen', () => {
    test('favorite', async () => {
        await page.waitFor(1000);
        await expect(page).toClick('button', {text: 'Blatt'});
        await page.screenshot({
            path: 'screenshots/3_kalender/2_kalender_favoriten_ausschnit.png',  //@ todo x/y of element +/-.
            clip: {x: 0, y: 0, width: 200, height: 300}
        });
    });

    test('filter', async () => {
        await page.screenshot({path: 'screenshots/2_allgemeines/2_allgemein_kalender_filterliste.png'});
    });

    test('months', async () => {
        await expect(page).toClick('button', {text: 'Woche'});
        await page.screenshot({
            path: 'screenshots/3_kalender/14_kalender_ansicht.png',
            clip: {x: 900, y: 0, width: 1366 - 900, height: 150}
        });
        await expect(page).toClick('button', {text: 'Monat'});
        await page.waitFor(2000);
        await page.screenshot({path: 'screenshots/3_kalender/1_kalender_monatsuebersicht.png'});
        await page.waitFor(2000);
    });

    test.skip('details panel', async () => {
        await expect(page).toClick('.cal-daysviewpanel-event-body', {text: 'Test Event'}); // @todo need event!
        await page.waitFor(1000);
        await page.screenshot({path: 'screenshots/3_kalender/13_kalender_termin_info.png'});
    });

    test('mini calendar', async () => {
        await page.screenshot({
            path: 'screenshots/3_kalender/4_kalender_minikalender.png',
            clip: {x: 0, y: 768 / 3, width: 250, height: 400}
        });
    });

    test('add attendee', async () => {
        await expect(page).toClick('.x-grid3-cell-inner.x-grid3-col-user_id', {text: 'Teilnehmer hinzufügen'});
        await page.waitFor(1000);
        await page.click('.x-trigger-wrap-focus span');
        await page.waitFor(500);
        await page.screenshot({
            path: 'screenshots/3_kalender/3_kalender_teilnehmer_hinzu.png',
            clip: {x: 0, y: 768 / 4, width: 500, height: 500}
        });
    });

    test('time beam', async () => {
        await expect(page).toClick('button', {text: 'Zeitstrahl'});
        await page.waitFor(1000);
        await page.screenshot({path: 'screenshots/3_kalender/5_kalender_termine_listenansicht.png'});
    });
});

describe('editDialog', () => {

    test('new event', async () => {
        await expect(page).toClick('button', {text: 'Termin hinzufügen'});
        newPage = await lib.getNewWindow();
        await newPage.waitFor(5000);
        await newPage.type('input[name=summary]', 'Test Event');
        await newPage.screenshot({path: 'screenshots/3_kalender/6_kalender_neuer_termin.png'});
        await newPage.click('[id^=CalendarEditDialogContainerSelectorext]');
        console.log('klick');
        await newPage.waitFor(500);
        await expect(newPage).toClick('.x-combo-list-item', {text: 'Andere Kalender wählen...'});
        await newPage.waitFor(2000);
        let collapseTree = await newPage.$$('.x-tree-node-el.x-unselectable.x-tree-node-collapsed');
        for (let i = 0; i < collapseTree.length; i++) {
            await collapseTree[i].click();
            await newPage.waitFor(500);
        }
        await newPage.screenshot({path: 'screenshots/3_kalender/7_kalender_neuer_termin_kalenderauswahl.png'});
        await newPage.keyboard.press('Escape');
    });

    test('add attendees', async () => {
        let viewport = await newPage.viewport();
        await newPage.screenshot({
            path: 'screenshots/3_kalender/8_kalender_teilnehmer_hinzu.png',
            clip: {x: 0, y: viewport.height * 1 / 3, width: viewport.width, height: viewport.height * 2 / 3}
        });
        await newPage.click('.x-grid3-row.x-cal-add-attendee-row.x-grid3-row-last .x-grid3-cell-inner.x-grid3-col-user_type');
        await newPage.waitFor('.x-combo-list-item ');
        await newPage.screenshot({
            path: 'screenshots/3_kalender/9_kalender_termin_teilnehmertyp.png',
            clip: {x: 0, y: viewport.height * 1 / 3, width: viewport.width, height: viewport.height * 2 / 3}
        });
        await newPage.click('.x-grid3-row.x-cal-add-attendee-row.x-grid3-row-last .x-grid3-cell-inner.x-grid3-col-user_id');
        await newPage.waitFor(2000);
        await newPage.click('.x-form-field-wrap.x-form-field-trigger-wrap.x-trigger-wrap-focus .x-form-trigger.x-form-arrow-trigger');
        await newPage.waitFor('.cal-attendee-picker-combo-list-item');
        let atttendee = await newPage.$$('.cal-attendee-picker-combo-list-item');
        await atttendee[1].click();
        await newPage.waitFor(2000);
    });

    test('user view', async () => {
        await newPage.click('.x-form-trigger.x-form-arrow-trigger');
        await newPage.waitFor(1000);
        let orga = await newPage.$$('.x-combo-list-item');
        await orga[orga.length - 1].click(); // not good!
        await newPage.waitFor(1000);
        await newPage.screenshot({path: 'screenshots/3_kalender/10_kalender_termin_anderer_organisator.png'});
    });

    test('save events', async () => {
        await newPage.click('.x-form-trigger.x-form-arrow-trigger');
        await newPage.waitFor(1000);
        let orga = await newPage.$$('.x-combo-list-item');
        await orga[orga.length - 3].click(); // not good!
        await newPage.waitFor(1000);
        await expect(newPage).toClick('button', {text: 'Ok'});

    })

});

describe('context menu', () => {
    test('email', async () => {
        await expect(page).toClick('button', {text: 'Blatt'});
        await page.waitFor(2000);
        await expect(page).toClick('button', {text: 'Woche'});
        await page.waitFor(2000);
        await expect(page).toClick('.cal-daysviewpanel-event-body', {text: 'Test Event' , button: 'right'});
        await page.waitFor(1000);
        await page.screenshot({path: 'screenshots/3_kalender/11_kalender_termin_kontextmenue.png'});
    });

    test('set answer', async () => {
        let answser = await expect(page).toMatchElement('.x-menu-item-text', {text: 'Meine Antwort setzen'});
        await answser.hover();
        answser = await expect(page).toMatchElement('.x-menu-item-text', {text: 'Keine Antwort'});
        await answser.hover();
        await page.waitFor(1000);
        await page.screenshot({path: 'screenshots/3_kalender/12_kalender_termin_antworten.png'});
    })
});

describe('poll client', () => {
    /* skip
    test('main page', async () => {
        await page.goto('http://localhost:4001/Calendar/view/poll/1ecd9fe7e338a794166e8ccb3849d43b45814fa2', {waitUntil: 'domcontentloaded'});
        await page.waitFor('.container');
        await page.screenshot({path: 'screenshots/3_kalender/15_kalender_umfrage_link.png'})
    })
    */
});

afterAll(async () => {
    browser.close();
});