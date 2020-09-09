const expect = require('expect-puppeteer');
const lib = require('../../lib/browser');

require('dotenv').config();

beforeAll(async () => {

    //expect.setDefaultOptions({timeout: 1000});

    await lib.getBrowser('Adressbuch', 'Kontakte');
});


describe('Contacts', () => {
    describe('Test MainPage', () => {
        test('choose grid fields', async () => {
            await expect(page).toMatchElement('span', {text: 'Tine 2.0'});
            await expect(page).toMatchElement('.t-app-addressbook .ext-ux-grid-gridviewmenuplugin-menuBtn');
            await page.click('.t-app-addressbook .ext-ux-grid-gridviewmenuplugin-menuBtn');
            await page.waitForSelector('.x-menu-list');
            await page.screenshot({path: 'screenshots/1_adressverwaltung/9_adressbuch_mit_spaltenauswahl.png'});
        });

        test('Import', async () => {
            await expect(page).toClick('button', {text: 'Kontakte importieren'});
            let popupWindow = await lib.getNewWindow();
            await popupWindow.waitForXPath('//button');
            await popupWindow.screenshot({path: 'screenshots/1_adressverwaltung/1_adressbuch_importfenster.png'});
            /*
            //var [button] = await  help.getElement('button', popupWindow, 'Wählen Sie die Datei mit Ihren Kontakte');
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
            await popupWindow.close();
        });

        test('show map', async () => {
            await expect(page).toClick('.x-grid3-row-first', {clickCount: 2});
            let popupWindow = await lib.getNewWindow();
            await popupWindow.waitFor('.x-tab-edge');
            try {
                await expect(popupWindow).toClick('span', {text: 'Karte'});
                await popupWindow.waitFor(10000); // wait to load map
                await popupWindow.screenshot({path: 'screenshots/1_adressverwaltung/12_adressbuch_kontakt_karte.png'});
            } catch (e) {
                //console.log('Map musst enabled');
            }
            await popupWindow.close();
        });

        test('notes', async () => {
            await expect(page).toClick('.x-grid3-row-first', {clickCount: 2});
            let popupWindow = await lib.getNewWindow();
            await popupWindow.waitFor('.x-tab-edge');
            await expect(popupWindow).toClick('span', {text: new RegExp("Notizen.*")});
            await popupWindow.screenshot({path: 'screenshots/2_allgemeines/17_allgemein_hr_eingabemaske_neu_notiz.png'});
            await expect(popupWindow).toClick('button', {text: 'Notizen hinzufügen'});
            await popupWindow.waitFor('.x-window-bwrap .x-form-trigger.x-form-arrow-trigger');
            await popupWindow.click('.x-window-bwrap .x-form-trigger.x-form-arrow-trigger');
            //await popupWindow.waitFor(1000);
            await popupWindow.screenshot({path: 'screenshots/2_allgemeines/18_allgemein_hr_eingabemaske_neu_notiz_notiz.png'});
            await expect(popupWindow).toClick('.x-window-bwrap button', 'Abbrechen');
            await popupWindow.close();
        });

        test('attachments', async () => {
            await expect(page).toClick('.x-grid3-row-first', {clickCount: 2});
            var popupWindow = await lib.getNewWindow();
            await popupWindow.waitFor('.x-tab-edge');
            await expect(popupWindow).toClick('span', {text: new RegExp("Anhänge.*")});
            await popupWindow.screenshot({path: 'screenshots/2_allgemeines/22_allgemein_hr_mitarbeiter_anhang.png'});
            await popupWindow.close();
        });

        test('relations', async () => {
            await expect(page).toClick('.x-grid3-row-first', {clickCount: 2});
            var popupWindow = await lib.getNewWindow();
            await popupWindow.waitFor('.x-tab-edge');
            await expect(popupWindow).toClick('span', {text: new RegExp("Verknüpfungen.*")});
            await popupWindow.waitFor(3000);
            await popupWindow.screenshot({path: 'screenshots/2_allgemeines/23_allgemein_hr_mitarbeiter_verknuepfungen.png'});
            let arrowtrigger = await popupWindow.$$('.x-form-arrow-trigger');
            await arrowtrigger[9].click();
            await popupWindow.screenshot({path: 'screenshots/1_adressverwaltung/13_adressbuch_kontakt_bearbeiten_verknuepfung_links.png'});
            await popupWindow.screenshot({path: 'screenshots/2_allgemeines/24_allgemein_hr_mitarbeiter_verknuepfungen_hinzu.png'});
            await arrowtrigger[10].click();
            await popupWindow.screenshot({path: 'screenshots/1_adressverwaltung/14_adressbuch_kontakt_bearbeiten_verknuepfung_rechts.png'});
            await popupWindow.close();
        });

        test('history', async () => {
            await expect(page).toClick('.x-grid3-row-first', {clickCount: 2});
            var popupWindow = await lib.getNewWindow();
            await expect(popupWindow).toClick('span', {text: new RegExp("Historie.*")});
            await popupWindow.screenshot({path: 'screenshots/2_allgemeines/21_allgemein_hr_mitarbeiter_historie.png'});
            await popupWindow.close();
        });

        describe('ContextMenu', () => {

            test('test Tags', async () => {
                await expect(page).toClick('.x-grid3-row', {button: 'right'});
                await expect(page).toClick('.action_tag.x-menu-item-icon');
                await expect(page).toClick('.x-window .x-form-arrow-trigger');
                await page.waitForSelector('.x-widget-tag-tagitem-text');
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
        let popupWindow;
        test('open EditDialog', async () => {
            popupWindow = await lib.getEditDialog('Kontakt hinzufügen');
        });

        test('From Fields', async () => {
            //console.log('Fill fields');
            await popupWindow.waitForXPath('//input');
            // @ todo make a array wiht key(n_prefix....) and value -> forech!
            await expect(popupWindow).toMatchElement('input[name=n_prefix]');
            await popupWindow.waitFor(2000);
            //console.log('wait ');
            await expect(popupWindow).toFill('input[name=n_prefix]', 'Dr.');
            await expect(popupWindow).toFill('input[name=n_given]', 'Thomas');
            await expect(popupWindow).toFill('input[name=n_middle]', 'Bernd');
            await expect(popupWindow).toFill('input[name=n_family]', 'Gaurad');
            await expect(popupWindow).toFill('input[name=org_name]', 'DWE');
            await expect(popupWindow).toFill('input[name=org_unit]', 'Personalwesen');
            await expect(popupWindow).toFill('input[name=title]', 'CEO');
            await expect(popupWindow).toFill('input[name=bday]', '12.03.1956');
            await expect(popupWindow).toFill('input[name=tel_work]', '040734662533');
            await expect(popupWindow).toFill('input[name=tel_cell]', '0179461021');
            await expect(popupWindow).toFill('input[name=adr_one_region]', 'Hamburg');
            await expect(popupWindow).toFill('input[name=adr_one_postalcode]', '20475');
            await expect(popupWindow).toFill('input[name=adr_one_street]', 'Pickhuben');
            await expect(popupWindow).toFill('input[name=adr_one_locality]', 'Hamburg');
            await expect(popupWindow).toFill('input[name=adr_one_countryname]', 'Deutschland');
            await popupWindow.waitFor('.x-combo-list-item');
            await popupWindow.keyboard.down('Enter');
            await popupWindow.screenshot({path: 'screenshots/1_adressverwaltung/10_adressbuch_kontakt_bearbeiten.png'})
        });

        test('parseAddress', async () => {
            await expect(popupWindow).toClick('button', {text: 'Adresse einlesen'});
            await popupWindow.waitFor('.ext-mb-textarea');
            await expect(popupWindow).toFill('.ext-mb-textarea', 'Max Mustermann \nBeispielweg 1 \n \n354234 Musterdorf !');
            await popupWindow.screenshot({path: 'screenshots/1_adressverwaltung/11_adressbuch_kontakt_neu_einlesen.png'});
            await popupWindow.click('.x-tool-close');
        });

        test('add Tag', async () => {
            //await newPage.waitFor(1000);
            let arrowtrigger = await popupWindow.$$('.x-form-arrow-trigger');
            await arrowtrigger[8].click();
            await popupWindow.waitFor(2000);
            await popupWindow.waitFor('.x-widget-tag-tagitem-text');
            await popupWindow.screenshot({path: 'screenshots/1_adressverwaltung/15_adressbuch_tag_hinzu.png'});
            let btn_text = await popupWindow.$$('.x-btn-text');
            await btn_text[3].click();
            await popupWindow.waitFor(2000);
            await popupWindow.waitFor('.ext-mb-input');
            await expect(popupWindow).toFill('.ext-mb-input', 'Persönlicher Tag');
            await popupWindow.screenshot({path: 'screenshots/1_adressverwaltung/16_adressbuch_persoenlicher_tag_hinzu.png'});
            await expect(popupWindow).toClick('button', {text: 'Abbrechen'});
        });


        test('save', async () => {
            await expect(popupWindow).toClick('button', {text: 'Ok'});
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
            await page.waitFor(500);
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