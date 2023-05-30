const expect = require('expect-puppeteer');
const lib = require('../../lib/browser');

require('dotenv').config();

beforeAll(async () => {
    await lib.getBrowser('Adressbuch', 'Kontakte');
    try {
        let favoritePanelCollapsed = await page.$x('//div[contains(@class, " ux-arrowcollapse ux-arrowcollapse-noborder x-tree x-panel-collapsed") and contains(., "Favoriten")]');
        await favoritePanelCollapsed[0].click();
    } catch (e) {
        console.log('favoritePanel also collapsed');
    }
});

describe('Mainpage', () => {
    const favorite = 'test favorite ' + Math.round(Math.random() * 10000000);
    const favoriteShared = 'test favorite shared ' + Math.round(Math.random() * 10000000);

    test('save favorite', async () => {
        try {
            await expect(page).toClick('.t-app-addressbook button', {text: 'Details anzeigen'});
        } catch (e) {
            console.log('filterpanel is aktiv');
        }
        await page.waitForSelector('.t-app-addressbook .action_saveFilter');
        await expect(page).toClick('.t-app-addressbook .action_saveFilter');
        await page.waitForSelector('.x-window.x-resizable-pinned');
        await expect(page).toFill('.x-form-text.x-form-field.x-form-invalid', favorite);
        await page.waitForSelector('.x-panel.x-wdgt-pickergrid.x-grid-panel.x-masked-relative.x-masked');
        await expect(page).toClick('.x-btn-image.action_saveAndClose');
        await page.waitForTimeout(3000); //wait for save the favorite
    });

    test('save shared favorite', async () => {
        try {
            await expect(page).toClick('.t-app-addressbook button', {text: 'Details anzeigen'});
        } catch (e) {
            console.log('filterpanel is aktiv');
        }
        await page.waitForSelector('.t-app-addressbook .action_saveFilter');
        await expect(page).toClick('.t-app-addressbook .action_saveFilter');
        await page.waitForSelector('.x-window.x-resizable-pinned');
        await expect(page).toFill('.x-form-text.x-form-field.x-form-invalid', favoriteShared);
        await page.click('.x-form-checkbox.x-form-field');
        await page.waitForFunction(() => !document.querySelector('.x-panel.x-wdgt-pickergrid.x-grid-panel.x-masked-relative.x-masked'));
        await expect(page).toClick('.x-btn-image.action_saveAndClose');
        await page.waitForTimeout(3000); //wait for save the favorite
    });

    test('edit favorite', async () => {
        try {
            let favoritePanelCollapsed = await page.$x('//div[contains(@class, " ux-arrowcollapse ux-arrowcollapse-noborder x-tree x-panel-collapsed") and contains(., "Favoriten")]');
            await favoritePanelCollapsed[0].click();
        } catch (e) {
            console.log('favoritePanel also collapsed');
        }
        await page.waitForTimeout(2000);
        await expect(page).toClick('span', {text: favorite, button:'right'});
        await expect(page).toClick('.x-menu-item-icon.action_edit', {visible: true});
        await page.waitForSelector('.x-window.x-resizable-pinned');
        await page.screenshot({path: 'screenshots/openFavorite1.png'});
        await page.waitForSelector('.x-panel.x-wdgt-pickergrid.x-grid-panel.x-masked-relative.x-masked');
        await expect(page).toClick('.x-btn-image.action_cancel');
        await page.waitForFunction(() => !document.querySelector('.x-window.x-resizable-pinned'));
    });

    test('edit shared favorite', async () => {
        try {
            let favoritePanelCollapsed = await page.$x('//div[contains(@class, " ux-arrowcollapse ux-arrowcollapse-noborder x-tree x-panel-collapsed") and contains(., "Favoriten")]');
            await favoritePanelCollapsed[0].click();
        } catch (e) {
            console.log('favoritePanel also collapsed');
        }
        await page.waitForTimeout(2000);
        await expect(page).toClick('span', {text: favoriteShared, button:'right'});
        await expect(page).toClick('.x-menu-item-icon.action_edit', {visible: true});
        await page.waitForSelector('.x-window.x-resizable-pinned');
        await page.waitForFunction(() => !document.querySelector('.x-panel.x-wdgt-pickergrid.x-grid-panel.x-masked-relative.x-masked'));
        await expect(page).toClick('.x-btn-image.action_cancel');
        await page.waitForFunction(() => !document.querySelector('.x-window.x-resizable-pinned'));
    });
});

afterAll(async () => {
    browser.close();
});
