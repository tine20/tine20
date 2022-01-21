const expect = require('expect-puppeteer');
const lib = require('../../lib/browser');

require('dotenv').config();

beforeAll(async () => {
    //expect.setDefaultOptions({timeout: 1000});
    await lib.getBrowser('Kalender');
    await expect(page).toClick('button', {text:'Heute'}); //need for init setup!
});

describe('MainScreen', () => {
    test('favorite', async () => {
        await page.waitForTimeout(2000);
        await expect(page).toClick('button', {text: 'Blatt'});
        await page.waitForTimeout(1000);
        await page.screenshot({
            path: 'screenshots/Kalender/2_kalender_favoriten_ausschnit.png',  //@ todo x/y of element +/-.
            clip: {x: 0, y: 0, width: 200, height: 300}
        });
    });

    test('filter', async () => {
        await page.screenshot({path: 'screenshots/StandardBedienhinweise/2_standardbedienhinweise_kalender_filterliste.png'});
    });

    test('months', async () => {
        await expect(page).toClick('button', {text: 'Woche'});
        await page.screenshot({
            path: 'screenshots/Kalender/14_kalender_ansicht.png',
            clip: {x: 900, y: 0, width: 1366 - 900, height: 150}
        });
        await expect(page).toClick('button', {text: 'Monat'});
        await page.waitForTimeout(2000);
        await page.screenshot({path: 'screenshots/Kalender/1_kalender_monatsuebersicht.png'});
        await page.waitForTimeout(2000);
    });

    test('details panel', async () => {
        try {
            await expect(page).toClick('.cal-daysviewpanel-event-body', {text: 'Test Event'}); // @todo need event!
            await page.waitForTimeout(1000);
            await page.screenshot({path: 'screenshots/Kalender/13_kalender_termin_info.png'});
        } catch (e) {
            console.log('No Test Event found!')
        }
    });

    test('mini calendar', async () => {
        await page.screenshot({
            path: 'screenshots/Kalender/4_kalender_minikalender.png',
            clip: {x: 0, y: 768 / 3, width: 250, height: 400}
        });
    });

    test('add attendee', async () => {
        await expect(page).toClick('.x-grid3-cell-inner.x-grid3-col-user_id', {text: 'Teilnehmer hinzufügen'});
        await page.waitForTimeout(1000);
        await page.click('.x-trigger-wrap-focus span');
        await page.waitForTimeout(500);
        await page.screenshot({
            path: 'screenshots/Kalender/Kalender_teilnehmer_hinzu.png',
            clip: {x: 0, y: 768 / 4, width: 500, height: 500}
        });
    });

    test('time beam', async () => {
        await expect(page).toClick('button', {text: 'Zeitstrahl'});
        await page.waitForTimeout(1000);
        await page.screenshot({path: 'screenshots/Kalender/5_kalender_termine_listenansicht.png'});
        await page.waitForTimeout(1000);
        await expect(page).toClick('button', {text:'Heute', visible: true});
    });
});

describe('editDialog', () => {

    test('new event', async () => {
        await page.waitForTimeout(3000);
        newPage = await lib.getEditDialog('Termin hinzufügen');
        await newPage.waitForTimeout(5000);
        await newPage.type('input[name=summary]', 'Test Event');
        await newPage.screenshot({path: 'screenshots/Kalender/6_kalender_neuer_termin.png'});
        await newPage.click('[id^=CalendarEditDialogContainerSelectorext]');
        console.log('klick');
        await newPage.waitForTimeout(500);
        await expect(newPage).toClick('.x-combo-list-item', {text: 'Andere Kalender wählen...'});
        await newPage.waitForTimeout(2000);
        let collapseTree = await newPage.$$('.x-tree-node-el.x-unselectable.x-tree-node-collapsed');
        for (let i = 0; i < collapseTree.length; i++) {
            await collapseTree[i].click();
            await newPage.waitForTimeout(500);
        }
        await newPage.mouse.move(0, 0);
        await newPage.waitForTimeout(500);
        await newPage.screenshot({path: 'screenshots/Kalender/7_kalender_neuer_termin_kalenderauswahl.png'});
        await newPage.keyboard.press('Escape');
    });

    test('add attendees', async () => {
        let viewport = await newPage.viewport();
        await newPage.screenshot({
            path: 'screenshots/Kalender/8_kalender_teilnehmer_hinzu.png',
            clip: {x: 0, y: viewport.height * 1 / 3, width: viewport.width, height: viewport.height * 2 / 3}
        });
        await newPage.click('.x-grid3-row.x-cal-add-attendee-row.x-grid3-row-last .x-grid3-cell-inner.x-grid3-col-user_type');
        await newPage.waitForSelector('.x-combo-list-item ');
        await newPage.screenshot({
            path: 'screenshots/Kalender/9_kalender_termin_teilnehmertyp.png',
            clip: {x: 0, y: viewport.height * 1 / 3, width: viewport.width, height: viewport.height * 2 / 3}
        });
        await newPage.click('.x-grid3-row.x-cal-add-attendee-row.x-grid3-row-last .x-grid3-cell-inner.x-grid3-col-user_id');
        await newPage.waitForTimeout(2000);
        await newPage.click('.x-form-field-wrap.x-form-field-trigger-wrap.x-trigger-wrap-focus .x-form-trigger.x-form-arrow-trigger');
        await newPage.waitForSelector('.cal-attendee-picker-combo-list-item');
        let atttendee = await newPage.$$('.cal-attendee-picker-combo-list-item');
        await atttendee[1].click();
        await newPage.waitForTimeout(2000);
    });

    test('user view', async () => {
        await newPage.click('.x-form-trigger.x-form-arrow-trigger');
        await newPage.waitForTimeout(1000);
        let orga = await newPage.$$('.x-combo-list-item');
        await orga[orga.length - 1].click(); // not good!
        await newPage.waitForTimeout(1000);
        await newPage.screenshot({path: 'screenshots/Kalender/10_kalender_termin_anderer_organisator.png'});
    });

    test('save events', async () => {
        await newPage.click('.x-form-trigger.x-form-arrow-trigger');
        await newPage.waitForTimeout(1000);
        let orga = await newPage.$$('.x-combo-list-item');
        await orga[orga.length - 3].click(); // not good!
        await newPage.waitForTimeout(1000);
        await expect(newPage).toClick('button', {text: 'Ok'});

    })

});

describe('context menu', () => {
    test('email', async () => {
        await expect(page).toClick('button', {text: 'Blatt'});
        await page.waitForTimeout(2000);
        await expect(page).toClick('button', {text: 'Woche'});
        await page.waitForTimeout(2000);
        await expect(page).toClick('.cal-daysviewpanel-event-body', {text: 'Test Event', button: 'right'});
        await page.waitForTimeout(1000);
        await page.screenshot({path: 'screenshots/Kalender/11_kalender_termin_kontextmenue.png'});
    });

    test('set answer', async () => {
        let answser = await expect(page).toMatchElement('.x-menu-item-text', {text: 'Meine Antwort setzen'});
        await answser.hover();
        answser = await expect(page).toMatchElement('.x-menu-item-text', {text: 'Keine Antwort'});
        await answser.hover();
        await page.waitForTimeout(1000);
        await page.screenshot({path: 'screenshots/Kalender/12_kalender_termin_antworten.png'});
    })
});

describe('poll client', () => {
    test.skip('main page', async () => {
        await page.mouse.move(0, 0);
        await page.goto(process.env.TEST_URL+ '/Calendar/view/poll/1ecd9fe7e338a794166e8ccb3849d43b45814fa2', {waitUntil: 'domcontentloaded'}); //@todo need other event_ID!
        await page.waitForSelector('.container');
        await page.mouse.move(0, 0);
        await page.waitForTimeout(500);
        await page.screenshot({path: 'screenshots/Kalender/15_kalender_umfrage_link.png'})
    })
});

afterAll(async () => {
    browser.close();
});