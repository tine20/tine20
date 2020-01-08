const timeout = process.env.SLOWMO ? 30000 : 30000;
const expect = require('expect-puppeteer');
const lib = require('../../lib/browser');
const help = require('../../lib/helper');
require('dotenv').config();

beforeAll(async () => {

    expect.setDefaultOptions({timeout: 1000});

    await lib.getBrowser('Adressbuch');
});


describe('Contacts', () => {
    describe('Test MainPage', () => {

        test('go to mainscreen', async () => {
            await expect(page).toClick('.tine-mainscreen-centerpanel-west span', {text: 'Kontakte'});
            await page.waitFor(2000);
            await page.screenshot({path: ''});
        });

        test('choose grid fields', async () => {
            await expect(page).toMatchElement('span', {text: 'Tine 2.0'});
            await page.click('.t-app-addressbook .ext-ux-grid-gridviewmenuplugin-menuBtn');
            await page.waitFor('.x-menu-list');
            await page.screenshot({path: 'screenshots/1_adressverwaltung/9_adressbuch_mit_spaltenauswahl.png'});
        });

        test('Import', async () => {
            var [button] = await help.getElement('button', page, 'Kontakte importieren');
            var newPagePromise = new Promise(x => browser.once('targetcreated', target => x(target.page())));
            await button.click();
            newPage = await lib.getNewWindow();
            await newPage.waitForXPath('//button');
            await newPage.screenshot({path: 'screenshots/1_adressverwaltung/1_adressbuch_importfenster.png'});
            /*
            //var [button] = await  help.getElement('button', newPage, 'Wählen Sie die Datei mit Ihren Kontakte');
            //await button.click();
            await page.setRequestInterception(true);

            // Request intercept handler... will be triggered with
            // each page.goto() statement
            page.on('request', interceptedRequest => {

                // Here, is where you change the request method and
                // add your post data
                let data = {

                    'method': 'POST',
                    'postData': 'paramFoo=valueBar&paramThis=valueThat'
                };

                // Request modified... finish sending!
                interceptedRequest.continue(data);
            });


            await newPage.waitFor(5000);
            //@ todo für den weiteren Dialog muss man eine datei hochladen können.
            */
            await newPage.close();
        });

        test('show map', async () => {
            await expect(page).toClick('.x-grid3-row-first', {clickCount: 2});
            var newPage = await lib.getNewWindow();
            try {
                await newPage.waitFor('.x-tab-edge');
                await expect(newPage).toClick('span', {text: 'Karte', clickCount: 2});
                await newPage.waitFor(10000); // wait to load map
                await newPage.screenshot({path: 'screenshots/1_adressverwaltung/12_adressbuch_kontakt_karte.png'});
            } catch (e) {
                //console.log('Map musst enabled');
            }
            var [button2] = await help.getElement('button', newPage, 'Abbrechen');
            //console.log('Save Kontakt');
            await button2.click();
        });

        test('notes', async () => {
            console.log('start notes');
            await expect(page).toClick('.x-grid3-row-first', {clickCount: 2});
            var newPage = await lib.getNewWindow();
            await newPage.waitFor('.x-tab-edge');
            await expect(newPage).toClick('span', {text: new RegExp("Notizen.*")});
            await newPage.screenshot({path: 'screenshots/2_allgemeines/17_allgemein_hr_eingabemaske_neu_notiz.png'});
            let [button] = await help.getElement('button', newPage, 'Notizen hinzufügen');
            await button.click();
            await newPage.waitFor('.x-window-bwrap .x-form-trigger.x-form-arrow-trigger');
            await newPage.click('.x-window-bwrap .x-form-trigger.x-form-arrow-trigger');
            await newPage.waitFor(1000);
            await newPage.screenshot({path: 'screenshots/2_allgemeines/18_allgemein_hr_eingabemaske_neu_notiz_notiz.png'});
            await expect(newPage).toClick('.x-window-bwrap button', newPage, 'Abbrechen');
            [button] = await help.getElement('button', newPage, 'Abbrechen');
            //console.log('Save Kontakt');
            await button.click();
        });

        test('attachments', async () => {
            await expect(page).toClick('.x-grid3-row-first', {clickCount: 2});
            var newPage = await lib.getNewWindow();
            await newPage.waitFor('.x-tab-edge');
            await expect(newPage).toClick('span', {text: new RegExp("Anhänge.*"), clickCount: 2});
            await newPage.screenshot({path: 'screenshots/2_allgemeines/22_allgemein_hr_mitarbeiter_anhang.png'});
            [button] = await help.getElement('button', newPage, 'Abbrechen');
            //console.log('Save Kontakt');
            await button.click();
        });

        test('relations', async () => {
            await expect(page).toClick('.x-grid3-row-first', {clickCount: 2});
            var newPage = await lib.getNewWindow();
            await newPage.waitFor('.x-tab-edge');
            await expect(newPage).toClick('span', {text: new RegExp("Verknüpfungen.*")});
            await newPage.waitFor(3000);
            await newPage.screenshot({path: 'screenshots/2_allgemeines/23_allgemein_hr_mitarbeiter_verknuepfungen.png'});
            let arrowtrigger = await newPage.$$('.x-form-arrow-trigger');
            arrowtrigger.forEach(async function (element, index) {
                if (index == 9) {
                    await element.click({clickCount: 2});
                    await newPage.waitFor(1000);
                    await newPage.screenshot({path: 'screenshots/1_adressverwaltung/13_adressbuch_kontakt_bearbeiten_verknuepfung_links.png'});
                    await newPage.screenshot({path: 'screenshots/2_allgemeines/24_allgemein_hr_mitarbeiter_verknuepfungen_hinzu.png'});
                }
            });
            arrowtrigger.forEach(async function (element, index) {
                if (index == 10) {
                    await element.click();
                    await newPage.waitFor(1000);
                    await newPage.screenshot({path: 'screenshots/1_adressverwaltung/14_adressbuch_kontakt_bearbeiten_verknuepfung_rechts.png'});

                }
            });
            await newPage.waitFor(3000);
            var [button2] = await help.getElement('button', newPage, 'Abbrechen');
            await button2.click();
        });

        test('history', async () => {
            await expect(page).toClick('.x-grid3-row-first', {clickCount: 2});
            var newPage = await lib.getNewWindow();
            await newPage.waitFor(2000);
            await expect(newPage).toClick('span', {text: new RegExp("Historie.*")});
            await newPage.screenshot({path: 'screenshots/2_allgemeines/21_allgemein_hr_mitarbeiter_historie.png'});
            var [button2] = await help.getElement('button', newPage, 'Abbrechen');
            //console.log('Save Kontakt');
            await button2.click();
        });

        describe('ContextMenu', () => {

            test('test Tags', async () => {
                await expect(page).toClick('.x-grid3-row', {button: 'right'});
                await expect(page).toClick('.action_tag.x-menu-item-icon');
                await page.waitFor(500);
                await expect(page).toClick('.x-window .x-form-arrow-trigger');
                await page.waitFor(1000);
                await page.hover('.x-widget-tag-tagitem-text');
                await page.screenshot({path: 'screenshots/1_adressverwaltung/18_adressbuch_kontakten_tags_zuweisen.png'});
                await page.keyboard.press('Escape');
                await page.keyboard.press('Escape');
            });

            /* @todo error Node is either not visible or not an HTMLElement

            test('test mail', async () => {
                await expect(page).toClick('.x-grid3-row', {button: 'right'});
                await page.waitFor(500);
                await page.keyboard.press('Escape');
                await page.waitFor(500);
                await expect(page).toClick('.x-grid3-row-first', {button: 'right'});
                await page.waitFor(5000);
                let felamimailIcon = await expect(page).toMatchElement('.FelamimailIconCls.x-menu-item-icon');
                await felamimailIcon.click();
                await page.waitFor(1000);
                await page.screenshot({path: 'screenshots/1_adressverwaltung/17_adressbuch_email_viele_empfaenger.png'});
            });

            test('send mail', async () => {
                await expect(page).toClick('.FelamimailIconCls.x-menu-item-icon');
                var newPage = await lib.getNewWindow();
                await newPage.waitFor(500);
                await page.screenshot({path: 'screenshots/1_adressverwaltung/20_adressbuch_email_als_notiz.png'});
            })
            */
        });
        /* skip... is to unstable
                describe('treeNodes', () => {
                    test('open context menu', async () => {
                        try {
                            await page.waitFor(500);
                            await page.click('#Addressbook_Contact_Tree .x-tool.x-tool-toggle');
                        } catch (e) {

                        } // @todo geht nur bei jeden zweiten mal...
                        await expect(page).toClick('#Addressbook_Contact_Tree span', {
                            text: 'Alle Adressbücher',
                        });
                        await expect(page).toClick('#Addressbook_Contact_Tree span', {
                            text: 'Meine Adressbücher',
                        });

                        await page.waitFor(1000);

                        await expect(page).toClick('#Addressbook_Contact_Tree span', {
                            text: 'Tine 2.0 Admin Account\'s personal addressbook',
                            button: 'right'
                        });
                        await page.waitFor(500);
                        await page.hover('.x-menu-item-icon.action_managePermissions');
                        await page.waitFor(500);
                        await page.screenshot({path: 'screenshots/2_allgemeines/3_allgemein_adresse_berechtigungen.png'});
                    });

                    test('premissons dialog', async () => {
                        await page.click('.x-menu-item-icon.action_managePermissions');
                        await page.waitFor(500);
                        await page.screenshot({path: 'screenshots/2_allgemeines/4_allgemein_adressbuch_berechtigungen_verwalten.png'});
                        await page.keyboard.press('Escape');

                    });
                });
           */
    });


    describe('Edit Contact', () => {
        let newPage;
        test('open EditDialog', async () => {
            var [button] = await help.getElement('button', page, 'Kontakt hinzufügen');
            await button.click();
            //console.log('Klick Button');
            newPage = await lib.getNewWindow();
            //console.log('Get Popup');
        });

        test('From Fields', async () => {
            //console.log('Fill fields');
            await newPage.waitForXPath('//input');
            // @ todo make a array wiht key(n_prefix....) and value -> forech!
            await expect(newPage).toMatchElement('input[name=n_prefix]');
            await newPage.waitFor(2000);
            //console.log('wait ');
            await expect(newPage).toFill('input[name=n_prefix]', 'Dr.');
            await expect(newPage).toFill('input[name=n_given]', 'Thomas');
            await expect(newPage).toFill('input[name=n_middle]', 'Bernd');
            await expect(newPage).toFill('input[name=n_family]', 'Gaurad');
            await expect(newPage).toFill('input[name=org_name]', 'DWE');
            await expect(newPage).toFill('input[name=org_unit]', 'Personalwesen');
            await expect(newPage).toFill('input[name=title]', 'CEO');
            await expect(newPage).toFill('input[name=bday]', '12.03.1956');
            await expect(newPage).toFill('input[name=tel_work]', '040734662533');
            await expect(newPage).toFill('input[name=tel_cell]', '0179461021');
            await expect(newPage).toFill('input[name=adr_one_region]', 'Hamburg');
            await expect(newPage).toFill('input[name=adr_one_postalcode]', '20475');
            await expect(newPage).toFill('input[name=adr_one_street]', 'Pickhuben');
            await expect(newPage).toFill('input[name=adr_one_locality]', 'Hamburg');
            await expect(newPage).toFill('input[name=adr_one_countryname]', 'Deutschland');
            await newPage.waitFor('.x-combo-list-item');
            await newPage.keyboard.down('Enter');
            await newPage.screenshot({path: 'screenshots/1_adressverwaltung/10_adressbuch_kontakt_bearbeiten.png'})
        });

        test('parseAddress', async () => {
            var [button3] = await help.getElement('button', newPage, 'Adresse einlesen');
            await button3.click();
            //console.log('Klich parse Button');
            await newPage.waitFor('.ext-mb-textarea');
            await expect(newPage).toFill('.ext-mb-textarea', 'Max Mustermann \nBeispielweg 1 \n \n354234 Musterdorf !');
            await newPage.screenshot({path: 'screenshots/1_adressverwaltung/11_adressbuch_kontakt_neu_einlesen.png'});
            await newPage.click('.x-tool-close');
        });

        test('add Tag', async () => {
            //await newPage.waitFor(1000);
            let arrowtrigger = await newPage.$$('.x-form-arrow-trigger');
            arrowtrigger.forEach(async function (element, index) {
                //console.log(index + ' \n ' + element);
                if (index == 8) await element.click();
            });
            await newPage.waitFor(2000);
            await newPage.waitFor('.x-widget-tag-tagitem-text');
            await newPage.screenshot({path: 'screenshots/1_adressverwaltung/15_adressbuch_tag_hinzu.png'});
            let btn_text = await newPage.$$('.x-btn-text');
            btn_text.forEach(async function (element, index) {
                //console.log(index + ' \n ' + element);
                if (index == 3) await element.click();
            });
            await newPage.waitFor(2000);
            await newPage.waitFor('.ext-mb-input');
            await expect(newPage).toFill('.ext-mb-input', 'Persönlicher Tag');
            await newPage.screenshot({path: 'screenshots/1_adressverwaltung/16_adressbuch_persoenlicher_tag_hinzu.png'});
            await expect(newPage).toClick('button', {text: 'Abbrechen'});
        });


        test('save', async () => {
            var [button2] = await help.getElement('button', newPage, 'Ok');
            //console.log('Save Kontakt');
            await button2.click();
        });
    });

    /*  describe('add new Record', () => {

          test('edidDialog', async () => {
              await page.click('')
          })
      });*/
});


describe('Group', () => {
    describe('Mainscreen', () => {
        test('go to Mainscreen', async () => {
            await expect(page).toClick('.tine-mainscreen-centerpanel-west span', {text: 'Gruppen'});
            await page.screenshot({path: 'screenshots/1_adressverwaltung/22_adressbuch_gruppen_uebersicht.png'});
            await page.screenshot({
                path: 'screenshots/1_adressverwaltung/23_adressbuch_gruppen_modul.png',
                clip: {x: 0, y: 0, width: 150, height: 300}
            })
        });
    });
});


afterAll(async () => {
    browser.close();
});