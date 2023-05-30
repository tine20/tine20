const expect = require('expect-puppeteer');
const lib = require('../../lib/browser');
require('dotenv').config();

beforeAll(async () => {
    //expect.setDefaultOptions({timeout: 1000});
    await lib.getBrowser('Admin');
});

describe('accounts', () => {
    test('User Mainpage', async () => {
        await page.waitForTimeout(1000);
        await expect(page).toClick('.t-app-admin .tine-mainscreen-centerpanel-west span', {text: 'Benutzer'});
        await page.screenshot({path: 'screenshots/Administration/1_admin_benutzertabelle.png'});
    });
    test('choose grid fields', async () => {
        await expect(page).toMatchElement('.x-grid3-cell-inner.x-grid3-col-accountLoginName', {text: 'tine20admin'});
        await page.click('.t-app-admin .ext-ux-grid-gridviewmenuplugin-menuBtn');
        await page.waitForSelector('.x-menu-list');
        await page.screenshot({
            path: 'screenshots/Administration/2_admin_spaltenauswahl.png',
            clip: {x: (1366 - (1366 / 5)), y: 0, width: (1366 / 5), height: 768}
        });
    });
    let newUserDialog;
    test('new user', async () => {
        newUserDialog = lib.getNewWindow();
        await expect(page).toClick('button', {text: 'Benutzer hinzufügen'});
        newUserDialog = await newUserDialog;
        await newUserDialog.waitForTimeout(2500);
        await newUserDialog.screenshot({path: 'screenshots/Administration/3_admin_benutzer_neu.png'});
    });
    test('groups', async () => {
        await newUserDialog.waitForTimeout(1000);
        await expect(newUserDialog).toClick('span', {text: 'Gruppen'});
        await newUserDialog.waitForTimeout(500);
        await newUserDialog.screenshot({path: 'screenshots/Administration/4_admin_benutzer_gruppe.png'});
        await expect(newUserDialog).toClick('button', {text: 'Abbrechen', visible: true});
    });
    test('edit user', async () => {
        let row = await page.$$('#gridAdminUsers .x-grid3-row');
        let userDialog = lib.getNewWindow();
        await row[3].click({clickCount: 2});
        userDialog = await userDialog;
        await userDialog.waitForTimeout(2000);
        await userDialog.screenshot({path: 'screenshots/Administration/5_admin_benutzer_editieren.png'});
        await userDialog.waitForTimeout(500);
        await expect(userDialog).toClick('button', {text: 'Abbrechen', visible: true});
    });
});

describe('groups', () => {
    test('group mainpage', async () => {
        await page.waitForTimeout(1000);
        await expect(page).toClick('.t-app-admin .tine-mainscreen-centerpanel-west span', {text: 'Gruppen'});
        await page.waitForTimeout(2000);
        await page.screenshot({path: 'screenshots/Administration/6_admin_gruppen.png'});
    });
    test('edit group', async () => {
        let groupDialog = lib.getNewWindow();
        await expect(page).toClick('.t-app-admin .x-grid3-cell-inner.x-grid3-col-name', {text: 'Users', clickCount: 2});
        groupDialog = await groupDialog;
        await groupDialog.waitForTimeout(2000);
        await groupDialog.screenshot({path: 'screenshots/Administration/7_admin_gruppen_editieren.png'});
        await groupDialog.waitForTimeout(500);
        await expect(groupDialog).toClick('button', {text: 'Abbrechen'});
    })
});

describe('roles', () => {
    test('roles mainpage', async () => {
        await page.waitForTimeout(1000);
        await expect(page).toClick('.t-app-admin .tine-mainscreen-centerpanel-west span', {text: 'Rollen'});
        //await page.screenshot({path: 'screenshots/Administration/6_admin_gruppen.png'});
    });
    test('edit roles', async () => {
        let roleDialog = lib.getNewWindow();
        await expect(page).toClick('.x-grid3-cell-inner.x-grid3-col-name', {text: 'user role', clickCount: 2});
        roleDialog = await roleDialog;
        await roleDialog.waitForTimeout(2000);
        await roleDialog.screenshot({path: 'screenshots/Administration/8_admin_rolle_editieren.png'});
        await expect(roleDialog).toClick('span', {text: 'Rechte'});
        await roleDialog.waitForTimeout(500);
        await roleDialog.screenshot({path: 'screenshots/Administration/9_admin_rolle_rechte_editieren.png'});
        await roleDialog.waitForTimeout(500);
        await expect(roleDialog).toClick('button', {text: 'Abbrechen'});
    })
});

describe('application', () => {
    test('apps mainpage', async () => {
        await page.waitForTimeout(1000);
        await expect(page).toClick('.t-app-admin .tine-mainscreen-centerpanel-west span', {text: 'Anwendungen'});
        await page.waitForTimeout(2000);
        await page.screenshot({path: 'screenshots/Administration/10_admin_anwendungen.png'});
    });
    test('addressbook settings', async () => {
        let editDialog = await getAppConfigDialog('Adressbuch');
        await editDialog.screenshot({path: 'screenshots/Administration/11_admin_admin_einstellung.png'});
        await editDialog.close();
    });
    test('add resousce', async () => {
        let editDialog = await getAppConfigDialog('Kalender');
        await editDialog.screenshot({path: 'screenshots/Administration/12_admin_kalender_einstellung.png'});
        let popup = lib.getNewWindow();
        await expect(editDialog).toClick('button', {text: 'Ressource hinzufügen'});
        popup = await popup;
        await popup.waitForTimeout(2000);
        await popup.screenshot({path: 'screenshots/Administration/13_admin_kalender_ressource_neu.png'});
        await expect(popup).toClick('span', {text: 'Zugriffsrechte'});
        await popup.waitForTimeout(1000);
        await popup.screenshot({path: 'screenshots/Administration/14_admin_kalender_ressource_rechte.png'});
        await popup.close();
        await editDialog.close();
    });
    test('crm settings', async () => {
        let editDialog = await getAppConfigDialog('Crm');
        await editDialog.screenshot({path: 'screenshots/Administration/15_admin_crm_einstellungen.png'});
        let rows = await editDialog.$$('.x-grid3-cell-inner.x-grid3-col-value');
        await rows[3].click({clickCount: 2, delay: 500});
        await editDialog.waitForTimeout(2000);
        await editDialog.screenshot({path: 'screenshots/Administration/16_admin_crm_lead_status.png'});
        await editDialog.close();
    });
    test('hr settings', async () => {
        let editDialog = await getAppConfigDialog('Human Resources');
        await editDialog.screenshot({path: 'screenshots/Administration/17_admin_hr_einstellungen.png'});
        await editDialog.close();
    });
    test('sales settings', async () => {
        let editDialog = await getAppConfigDialog('Sales');
        await editDialog.waitForTimeout(2000);
        await editDialog.screenshot({path: 'screenshots/Administration/18_admin_sales_einstellungen.png'});
        await editDialog.close();
    });
    test('tinebase settings', async () => {
        let editDialog = await getAppConfigDialog('Tinebase');
        await expect(editDialog).toClick('span', {text: 'Profilinformation'});
        await editDialog.waitForTimeout(500);
        await editDialog.screenshot({path: 'screenshots/Administration/19_admin_tinebase_einstellungen.png'});
        await editDialog.close();
    });
});

describe('container', () => {
    test('container mainpage', async () => {
        await expect(page).toClick('.t-app-admin .tine-mainscreen-centerpanel-west span', {text: 'Container'});
        await page.waitForTimeout(2000);
        await page.screenshot({path: 'screenshots/Administration/24_admin_container.png'});
        let containerDialog = lib.getNewWindow();
        await expect(page).toClick('.x-grid3-cell-inner.x-grid3-col-name', {text: 'Internal Contacts', clickCount: 2});
        containerDialog = await containerDialog;
        await containerDialog.waitForTimeout(3000);
        await containerDialog.screenshot({path: 'screenshots/Administration/25_admin_container_editieren.png'});
        await containerDialog.close();
    });
    test('add container', async () => {
        let containerDialog = lib.getNewWindow();
        await expect(page).toClick('button', {text: 'Container hinzufügen'});
        containerDialog = await containerDialog;
        await containerDialog.waitForTimeout(3000);
        await containerDialog.screenshot({path: 'screenshots/Administration/26_admin_container_neu.png'});
        await containerDialog.close();
    })
});

describe('shared tags', () => {
    test('tag mainpage', async () => {
        await expect(page).toClick('.t-app-admin .tine-mainscreen-centerpanel-west span', {text: 'Gemeinsame Tags'});
        await page.waitForTimeout(1000);
        await page.screenshot({path: 'screenshots/Administration/21_admin_gemeinsame_tags.png'});
        let tagDialog = lib.getNewWindow();
        await expect(page).toClick('.x-grid3-cell-inner.x-grid3-col-name', {text: 'internal', clickCount: 2});
        tagDialog = await tagDialog;
        await tagDialog.waitForTimeout(2000);
        await tagDialog.screenshot({path: 'screenshots/Administration/22_admin_gemeinsame_tags_rechte.png'});
        await expect(tagDialog).toClick('span', {text: 'Kontexte'});
        await tagDialog.waitForTimeout(500);
        await tagDialog.screenshot({path: 'screenshots/Administration/23_admin_gemeinsame_tags_kontexte.png'});
        await tagDialog.close();
    });
});

describe('customfields', () => {
    test('mainpage', async () => {
        await expect(page).toClick('.t-app-admin .tine-mainscreen-centerpanel-west span', {text: 'Zusatzfelder'});
        await page.waitForTimeout(1000);
        await page.screenshot({path: 'screenshots/Administration/27_admin_zusatzfelder.png'});
    });
    test('edit customfields', async () => {
        let cfDialog = lib.getNewWindow();
        await expect(page).toClick('.t-app-admin button', {text: 'Zusatzfeld hinzufügen'});
        cfDialog = await cfDialog;
        await cfDialog.waitForTimeout(2000);
        await cfDialog.screenshot({path: 'screenshots/Administration/28_admin_zusatzfelder_neu.png'});
        cfDialog.close();
    });
});

describe('activSync', () => {
    test('mainpage', async () => {
        await expect(page).toClick('.t-app-admin .tine-mainscreen-centerpanel-west span', {text: 'ActiveSync Geräte'});
        await page.waitForTimeout(1000);
        await page.screenshot({path: 'screenshots/Administration/30_admin_activesync_devices.png'});
    });
    test('edit dialog', async () => {
        let activSyncDialog = lib.getNewWindow();
        await expect(page).toClick('.t-app-admin .x-grid3-cell-inner.x-grid3-col-devicetype', {
            text: 'android',
            clickCount: 2
        });
        activSyncDialog = await activSyncDialog;
        await activSyncDialog.waitForTimeout(3000);
        await activSyncDialog.screenshot({path: 'screenshots/Administration/31_admin_activesync_devices_editieren.png'});
        await activSyncDialog.close()
    });
});


describe('access log', () => {
    test('mainpage', async () => {
        await expect(page).toClick('.t-app-admin .tine-mainscreen-centerpanel-west span', {text: 'Zugriffslog'});
        await page.waitForTimeout(1000);
        await page.screenshot({path: 'screenshots/Administration/20_admin_zugriffslog.png'});
    })
});

describe('server info', () => {
    test('mainpage', async () => {
        await expect(page).toClick('.t-app-admin .tine-mainscreen-centerpanel-west span', {text: 'Server Informationen'});
        await page.waitForTimeout(1000);
        await page.screenshot({path: 'screenshots/Administration/29_admin_serverinfo.png'});
    })
});

afterAll(async () => {
    browser.close();
});

async function getAppConfigDialog(text) {
    await page.waitForTimeout(1000);
    let popupWindow = lib.getNewWindow();
    await expect(page).toClick('.x-grid3-cell-inner.x-grid3-col-name', {text: text, clickCount: 2});
    popupWindow = await popupWindow;
    try {
        await popupWindow.waitForSelector('.ext-el-mask', {timeout: 5000});
    } catch {}
    await popupWindow.waitForFunction(() => !document.querySelector('.ext-el-mask'));
    return popupWindow
}
