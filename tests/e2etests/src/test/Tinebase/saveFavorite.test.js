const expect = require('expect-puppeteer');
const lib = require('../../lib/browser');

require('dotenv').config();

beforeAll(async () => {
    await lib.getBrowser('Adressbuch', 'Kontakte');
});

describe('Mainpage', () => {

    test('save favorite', async () => {
        try {
            await expect(page).toClick('.t-app-addressbook button', {text: 'Details anzeigen'});
        } catch (e) {
            console.log('filterpanel is aktiv');
        }
        await page.waitForSelector('.t-app-addressbook .action_saveFilter');
        await expect(page).toClick('.t-app-addressbook .action_saveFilter');
        await page.waitForSelector('.x-window.x-resizable-pinned');
        await page.type('.x-form-text.x-form-field.x-form-invalid', 'favorite');
        await page.waitForSelector('.x-panel.x-wdgt-pickergrid.x-grid-panel.x-masked-relative.x-masked');
        await expect(page).toClick('.x-btn-image.action_saveAndClose');
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
        await page.type('.x-form-text.x-form-field.x-form-invalid', 'shared favorite');
        await page.click('.x-form-checkbox.x-form-field');
        await page.waitFor(() => !document.querySelector('.x-panel.x-wdgt-pickergrid.x-grid-panel.x-masked-relative.x-masked'));
        await expect(page).toClick('.x-btn-image.action_saveAndClose');
        await page.waitFor(1000);
    });

    test('edit favorite', async () => {
        try {
            let favoritePanelCollapsed = await page.$x('//div[contains(@class, "ux-arrowcollapse ux-arrowcollapse-noborder x-tree x-panel-collapsed") and contains(., "Favoriten")]');
            await favoritePanelCollapsed[0].click();
        } catch (e) {
            console.log('favoritePanel also collapsed');
        }
        await page.waitFor(2000);
        await expect(page).toClick('span', {text: 'favorite', button:'right'});
        await expect(page).toClick('.x-menu-item-icon.action_edit', {visible: true});
        await page.waitForSelector('.x-window.x-resizable-pinned');
        await page.screenshot({path: 'screenshots/openFavorite1.png'});
        await page.waitForSelector('.x-panel.x-wdgt-pickergrid.x-grid-panel.x-masked-relative.x-masked');
        await page.keyboard.press('Escape');
        await page.waitFor(() => !document.querySelector('.x-window.x-resizable-pinned'));
    });

    test('edit shared favorite', async () => {
        try {
            let favoritePanelCollapsed = await page.$x('//div[contains(@class, "ux-arrowcollapse ux-arrowcollapse-noborder x-tree x-panel-collapsed") and contains(., "Favoriten")]');
            await favoritePanelCollapsed[0].click();
        } catch (e) {
            console.log('favoritePanel also collapsed');
        }
        await page.waitFor(2000);
        await expect(page).toClick('span', {text: 'shared favorite', button:'right'});
        await expect(page).toClick('.x-menu-item-icon.action_edit', {visible: true});
        await page.waitForSelector('.x-window.x-resizable-pinned');
        await page.waitFor(() => !document.querySelector('.x-panel.x-wdgt-pickergrid.x-grid-panel.x-masked-relative.x-masked'));
        await page.keyboard.press('Escape');
        await page.waitFor(() => !document.querySelector('.x-window.x-resizable-pinned'));
    });
});

afterAll(async () => {
    browser.close();
});
