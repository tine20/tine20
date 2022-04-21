const expect = require('expect-puppeteer');
const lib = require('../../lib/browser');

require('dotenv').config();

beforeAll(async () => {
    await lib.getBrowser('Adressbuch', 'Kontakte');
});


describe('Contacts', () => {
    describe('Test MainPage', () => {
        test('choose grid fields', async () => {
            await expect(page).toMatchElement('span', {text: 'tine ® Admin'});
            await expect(page).toMatchElement('.t-app-addressbook .ext-ux-grid-gridviewmenuplugin-menuBtn');
            await page.click('.t-app-addressbook .ext-ux-grid-gridviewmenuplugin-menuBtn');
            await page.waitForSelector('.x-menu-list');
            await page.screenshot({path: 'screenshots/Adressbuch/9_adressbuch_mit_spaltenauswahl.png'});
        });

        test('Import', async () => {
            let importDialog ;
            await expect(page).toMatchElement('.x-btn-text', {text: 'Kontakte importieren'});
            await page.waitForTimeout(100); // wait for btn to get active
            await expect(page).toClick('.x-btn-text', {text: 'Kontakte importieren'});
            importDialog = await lib.getNewWindow();

            await importDialog.waitForXPath('//button');
            await lib.uploadFile(importDialog, 'src/testScreenshots/Addressbook/test.csv');
            await expect(importDialog).toMatchElement('button', {text: new RegExp('test.csv.*')})
            await importDialog.screenshot({path: 'screenshots/Adressbuch/1_adressbuch_importfenster.png'});
            await expect(importDialog).toClick('button', {text: 'Vorwärts'});
            await importDialog.screenshot({path: 'screenshots/Adressbuch/4_adressbuch_mit_import_optionen_setzen.png.png'});
            await expect(importDialog).toClick('button', {text: 'Vorwärts'});
            await expect(importDialog).toMatchElement('span', {text: 'Zusammenfassung'});
            await importDialog.screenshot({path: 'screenshots/Adressbuch/8_adressbuch_mit_import_zusammenfassung.png'});
            await expect(importDialog).toClick('button', {text: 'Ende'});
        });

        test('Import conflicts', async () => {
            let importDialog ;
            await page.waitForTimeout(500);
            await expect(page).toMatchElement('.x-btn-text', {text: 'Kontakte importieren'});
            await page.waitForTimeout(100); // wait for btn to get active
            await expect(page).toClick('.x-btn-text', {text: 'Kontakte importieren'});
            importDialog = await lib.getNewWindow();

            await importDialog.waitForXPath('//button');
            await lib.uploadFile(importDialog, 'src/testScreenshots/Addressbook/test.csv');
            await expect(importDialog).toMatchElement('button', {text: new RegExp('test.csv.*')})
            await expect(importDialog).toClick('button', {text: 'Vorwärts'});
            await expect(importDialog).toClick('button', {text: 'Vorwärts'});
            await expect(importDialog).toMatchElement('span', {text: 'Konflikte auflösen'});
            await importDialog.screenshot({path: 'screenshots/Adressbuch/7_adressbuch_mit_import_konflikte_aufloesen.png'});
            await importDialog.close();
        });

        test.skip('Import conflicts', async () => {
            let importDialog ;
            await page.waitForTimeout(500);
            await expect(page).toMatchElement('.x-btn-text', {text: 'Kontakte importieren'});
            await page.waitForTimeout(100); // wait for btn to get active
            await expect(page).toClick('.x-btn-text', {text: 'Kontakte importieren'});
            importDialog = await lib.getNewWindow();

            await importDialog.waitForXPath('//button');
            await lib.uploadFile(importDialog, 'src/testScreenshots/Addressbook/test_fail.csv');
            await expect(importDialog).toMatchElement('button', {text: new RegExp('test_fail.csv.*')})
            await expect(importDialog).toClick('button', {text: 'Vorwärts'});
            await expect(importDialog).toClick('button', {text: 'Vorwärts'});
            await expect(importDialog).toMatchElement('span', {text: 'Konflikte auflösen'});
            await importDialog.screenshot({path: 'screenshots/Adressbuch/7_adressbuch_mit_import_konflikte_aufloesen.png'});
            await expect(importDialog).toClick('button', {text: 'Ende'});
            await importDialog.close();
        });

        describe('editDialog', () => {
            let popupWindow;

            test('open editDialog', async () => {
                await expect(page).toClick('.x-grid3-row-first', {clickCount: 2});
                popupWindow = await lib.getNewWindow();
                await popupWindow.waitFor('.x-tab-edge');
            });

            test('show map', async () => {
                try {
                    await expect(popupWindow).toClick('span', {text: 'Karte'});
                    await popupWindow.waitFor(10000); // wait to load map
                    await popupWindow.screenshot({path: 'screenshots/1_adressverwaltung/12_adressbuch_kontakt_karte.png'});
                } catch (e) {
                    await console.log('Map musst enabled');
                }
            });

        test('notes', async () => {
            await selectTab(popupWindow, 'Notizen.*');
            await popupWindow.screenshot({path: 'screenshots/StandardBedienhinweise/17_standardbedienhinweise_hr_eingabemaske_neu_notiz.png'});
            await expect(popupWindow).toClick('button', {text: 'Notizen hinzufügen'});
            await popupWindow.waitForSelector('.x-window-bwrap .x-form-trigger.x-form-arrow-trigger');
            await popupWindow.click('.x-window-bwrap .x-form-trigger.x-form-arrow-trigger');
            //await popupWindow.waitForTimeout(1000);
            await popupWindow.screenshot({path: 'screenshots/StandardBedienhinweise/18_standardbedienhinweise_hr_eingabemaske_neu_notiz_notiz.png'});
            await expect(popupWindow).toClick('.x-window-bwrap button', 'Abbrechen');
        });

            test('attachments', async () => {
                await expect(popupWindow).toClick('span', {text: new RegExp("Anhänge.*")});
                await popupWindow.screenshot({path: 'screenshots/2_allgemeines/22_allgemein_hr_mitarbeiter_anhang.png'});
            });

            test('relations', async () => {
                await expect(popupWindow).toClick('span', {text: new RegExp("Verknüpfungen.*")});
                await popupWindow.waitFor(3000);
                await popupWindow.screenshot({path: 'screenshots/2_allgemeines/23_allgemein_hr_mitarbeiter_verknuepfungen.png'});
                let test = await popupWindow.waitForSelector('.x-panel.x-wdgt-pickergrid.x-grid-panel');

                let arrowtrigger = await test.$$('.x-form-arrow-trigger');
                await arrowtrigger[0].click(); // test!
                await popupWindow.screenshot({path: 'screenshots/1_adressverwaltung/13_adressbuch_kontakt_bearbeiten_verknuepfung_links.png'});
                await popupWindow.screenshot({path: 'screenshots/2_allgemeines/24_allgemein_hr_mitarbeiter_verknuepfungen_hinzu.png'});
                await arrowtrigger[1].click(); // test!
                await popupWindow.screenshot({path: 'screenshots/1_adressverwaltung/14_adressbuch_kontakt_bearbeiten_verknuepfung_rechts.png'});
            });

            test('history', async () => {
                await expect(popupWindow).toClick('span', {text: new RegExp("Historie.*")});
                await popupWindow.screenshot({path: 'screenshots/2_allgemeines/21_allgemein_hr_mitarbeiter_historie.png'});
                await popupWindow.close();
            });
        });
    });
    describe('ContextMenu', () => {
        let felamimailIcon;

        test('test Tags', async () => {
            await page.waitForTimeout(1000);
            await expect(page).toClick('.x-grid3-row.x-grid3-row-last', {button: 'right'});
            await expect(page).toClick('.action_tag.x-menu-item-icon');
            await expect(page).toClick('.x-window .x-form-arrow-trigger');
            await page.waitForSelector('.x-widget-tag-tagitem-text');
            await page.hover('.x-widget-tag-tagitem-text');
            await page.screenshot({path: 'screenshots/Adressbuch/18_adressbuch_kontakten_tags_zuweisen.png'});
            await page.keyboard.press('Escape');
            await page.keyboard.press('Escape');
        });

        // @todo error Node is either not visible or not an HTMLElement

        test('test mail', async () => {
            await expect(page).toClick('.x-grid3-row.x-grid3-row-last ', {button: 'right'});
            await page.keyboard.press('Escape');
            await expect(page).toClick('.x-grid3-row.x-grid3-row-first', {button: 'right'});
            felamimailIcon = await expect(page).toMatchElement('.x-menu-item-text', {text: 'Nachricht verfassen'});
            await felamimailIcon.hover();
            await page.screenshot({path: 'screenshots/Adressbuch/17_adressbuch_email_viele_empfaenger.png'});
        });

        test('send mail', async () => {
            let popupWindow;
            await felamimailIcon.click();
            popupWindow = await lib.getNewWindow();
            await popupWindow.waitForFunction(() => !document.querySelector('.ext-el-mask'));
            await popupWindow.screenshot({path: 'screenshots/Adressbuch/20_adressbuch_email_als_notiz.png'});
            await popupWindow.close();
        })

    });
    describe('treeNodes', () => {
        test('open context menu', async () => {
            if (!(await expect(page).toMatchElement('#Addressbook_Contact_Tree span', {text: 'Meine Adressbücher'}))) {
                await page.click('#Addressbook_Contact_Tree .x-tool.x-tool-toggle');
            }
            await expect(page).toClick('#Addressbook_Contact_Tree span', {text: 'Alle Adressbücher'});
            await expect(page).toClick('#Addressbook_Contact_Tree span', {text: 'Meine Adressbücher'});
            await expect(page).toClick('#Addressbook_Contact_Tree span', {text: 'Gemeinsame Adressbücher'});
            await page.waitForTimeout(1000) //musst wait load node tree

            try {
                await expect(page).toClick('#Addressbook_Contact_Tree span', {
                text: 'tine ® Admins personal addressbook',
                button: 'right'
            });
            } catch (e) {
                await expect(page).toClick('#Addressbook_Contact_Tree span', {
                    text: 'tine ® Admins persönliches Adressbuch',
                    button: 'right'
                });
            }
            await page.waitForSelector('.x-menu-item-icon.action_managePermissions')
            await page.hover('.x-menu-item-icon.action_managePermissions');
            await page.screenshot({path: 'screenshots/StandardBedienhinweise/3_standardbedienhinweise_adresse_berechtigungen.png'});
        });

        test('premissons dialog', async () => {
            await page.click('.x-menu-item-icon.action_managePermissions');
            await page.screenshot({path: 'screenshots/StandardBedienhinweise/4_standardbedienhinweise_adressbuch_berechtigungen_verwalten.png'});
            await page.keyboard.press('Escape');
        });
    });


    describe('Edit Contact', () => {
        let popupWindow;
        test('open EditDialog', async () => {
            popupWindow = await lib.getEditDialog('Kontakt hinzufügen');
        });

        test('From Fields', async () => {
            //console.log('Fill fields');
            await popupWindow.waitForXPath('//input');
            // @ todo make a array wiht key(n_prefix....) and value -> forech!
            await expect(popupWindow).toMatchElement('input[name=n_prefix]');
            //await popupWindow.waitForTimeout(2000);
            //console.log('wait ');
            await expect(popupWindow).toFill('input[name=n_prefix]', 'Dr.');
            await expect(popupWindow).toFill('input[name=n_given]', 'Thomas');
            await expect(popupWindow).toFill('input[name=n_middle]', 'Bernd');
            await expect(popupWindow).toFill('input[name=n_family]', 'Gaurad');
            await expect(popupWindow).toFill('input[name=org_name]', 'DWE');
            await expect(popupWindow).toFill('input[name=org_unit]', 'Personalwesen');
            //await expect(popupWindow).toFill('input[name=title]', 'CEO');
            await expect(popupWindow).toFill('input[name=bday]', '12.03.1956');
            await expect(popupWindow).toFill('input[name=tel_work]', '040734662533');
            await expect(popupWindow).toFill('input[name=tel_cell]', '0179461021');
            //await expect(popupWindow).toFill('input[name=adr_one_region]', 'Hamburg');
            await expect(popupWindow).toFill('input[name=adr_one_postalcode]', '20475');
            await expect(popupWindow).toFill('input[name=adr_one_street]', 'Pickhuben');
            await expect(popupWindow).toFill('input[name=adr_one_locality]', 'Hamburg');
            await expect(popupWindow).toFill('input[name=adr_one_countryname]', 'Deutschland');
            await popupWindow.waitForSelector('.x-combo-list-item');
            await popupWindow.keyboard.down('Enter');
            await popupWindow.screenshot({path: 'screenshots/Adressbuch/10_adressbuch_kontakt_bearbeiten.png'})
        });

        test('parseAddress', async () => {
            await expect(popupWindow).toClick('button', {text: 'Adresse einlesen'});
            await popupWindow.waitForSelector('.ext-mb-textarea');
            await expect(popupWindow).toFill('.ext-mb-textarea', 'Max Mustermann \nBeispielweg 1 \n \n354234 Musterdorf !');
            await popupWindow.screenshot({path: 'screenshots/Adressbuch/11_adressbuch_kontakt_neu_einlesen.png'});
            await popupWindow.click('.x-tool-close');
        });

        test('add Tag', async () => {
            let arrowtrigger = await popupWindow.$$('.x-form-arrow-trigger');
            await arrowtrigger[9].click();
            await expect(popupWindow).toMatchElement('.x-widget-tag-tagitem-text', {text: 'Elbphilharmonie'});
            await popupWindow.screenshot({path: 'screenshots/Adressbuch/15_adressbuch_tag_hinzu.png'});
            let btn_text = await popupWindow.$$('.x-btn-text');
            await btn_text[3].click();
            await popupWindow.waitForSelector('.ext-mb-input');
            await expect(popupWindow).toFill('.ext-mb-input', 'Persönlicher Tag');
            await popupWindow.screenshot({path: 'screenshots/Adressbuch/16_adressbuch_persoenlicher_tag_hinzu.png'});
            await expect(popupWindow).toClick('button', {text: 'Abbrechen'});
        });

        test('save', async () => {
            await expect(popupWindow).toClick('button', {text: 'Ok'});
            await page.waitFor(1000);
        });
    });
});


describe('Group', () => {
    describe('Mainscreen', () => {
        test('go to Mainscreen', async () => {
            await expect(page).toClick('.tine-mainscreen-centerpanel-west span', {text: 'Gruppen'});
            await page.waitForTimeout(500);
            await page.screenshot({path: 'screenshots/Adressbuch/22_adressbuch_gruppen_uebersicht.png'});
            await page.screenshot({
                path: 'screenshots/Adressbuch/23_adressbuch_gruppen_modul.png',
                clip: {x: 0, y: 0, width: 150, height: 300}
            })
        });
    });
});


afterAll(async () => {
    browser.close();
});

async function selectTab(popupWindow, regEx) {
    await expect(popupWindow).toClick('span .x-tab-strip-text', {text: new RegExp(regEx)});
    await popupWindow.waitForTimeout(500); //fix click issue @todo find better way
    await expect(popupWindow).toClick('span .x-tab-strip-text', {text: new RegExp(regEx)});
}
