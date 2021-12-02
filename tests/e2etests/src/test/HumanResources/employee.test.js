const expect = require('expect-puppeteer');
const lib = require('../../lib/browser');

require('dotenv').config();

beforeAll(async () => {
    await lib.getBrowser('Human Resources');
});

describe.skip('employee', () => {
    describe('employee grid', () => {
        test('show grid', async () => {
            await expect(page).toClick('.x-tree-node span', {text: 'Mitarbeiter', visible: true});
            await expect(page).toMatchElement('.x-grid3-hd-account_id');
        });

        test('select employee', async () => {
            await expect(page).toClick('.x-grid3-col-account_id', {text: 'McBlack, James'});
        });
    });
    
    describe('edit dialog', () => {
        let employeeEditDialog
        test('open dialog', async () => {
            employeeEditDialog = await lib.getEditDialog('Mitarbeiter bearbeiten');
        });
    
        describe('vacation (freetime)', () => {
            const testString = 'test vacation ' + Math.round(Math.random() * 10000000);
            test('vacation grid', async () => {
                await expect(employeeEditDialog).toClick('.x-tab-strip-text', {text: 'Urlaub'});
            });
    
            describe('add vacation', () => {
                let freetimeEditDialog;
                test('open dialog', async() => {
                    freetimeEditDialog = await lib.getEditDialog('Urlaubstage hinzufügen', employeeEditDialog);
                    await freetimeEditDialog.waitForTimeout(50);
                    await freetimeEditDialog.waitForFunction(() => document.querySelector('.ext-el-mask-msg.x-mask-loading div').textContent === 'Übertrage Freizeitanspruch...');
                    await freetimeEditDialog.waitForFunction(() => !document.querySelector('.ext-el-mask-msg.x-mask-loading div'));
                    await expect(freetimeEditDialog).toFill('textarea[name=description]', testString);
                });
            
                test('employee is resolved', async () => {
                    if ('James McBlack' !== await freetimeEditDialog.evaluate(() => document.querySelector('input[name=employee_id]').value)) {
                        return Promise.reject('employee not resolved');
                    }
                });

                test('type is resolved', async () => {
                    if ('[U] Urlaub' !== await freetimeEditDialog.evaluate(() => document.querySelector('input[name=type]').value)) {
                        return Promise.reject('type not resolved');
                    }
                });

                test('exclude days (a sunday) are loaded and applied', async () => {
                        await freetimeEditDialog.waitForSelector('.x-date-picker table > tbody > tr:nth-child(2) > td > table > tbody > tr:nth-child(3) > td:nth-child(7).x-date-disabled');
                });
                
                test('dates can be selected', async () => {
                    let remainingDays = await freetimeEditDialog.evaluate(() => +document.querySelector('input[name=scheduled_remaining_vacation_days]').value);
                    await expect(freetimeEditDialog).toClick('.x-date-picker table > tbody > tr:nth-child(2) > td > table > tbody > tr:nth-child(3) > td:nth-child(2) > a > em > span');
                    await expect(freetimeEditDialog).toClick('.x-date-picker table > tbody > tr:nth-child(2) > td > table > tbody > tr:nth-child(3) > td:nth-child(4) > a > em > span');
                    await freetimeEditDialog.waitForTimeout(50);
                    
                    if (remainingDays-2 !== await freetimeEditDialog.evaluate(() => +document.querySelector('input[name=scheduled_remaining_vacation_days]').value)) {
                        throw new Error('remaining days do not decrease');
                    }
                });

                test('dates can be deselected', async () => {
                    let remainingDays = await freetimeEditDialog.evaluate(() => +document.querySelector('input[name=scheduled_remaining_vacation_days]').value);

                    await expect(freetimeEditDialog).toClick('.x-date-picker table > tbody > tr:nth-child(2) > td > table > tbody > tr:nth-child(3) > td:nth-child(2) > a > em > span');
                    await freetimeEditDialog.waitForTimeout(50);
                    
                    if (remainingDays+1 !== await freetimeEditDialog.evaluate(() => +document.querySelector('input[name=scheduled_remaining_vacation_days]').value)) {
                        throw new Error('remaining days do not increase');
                    }
                });
                
                test('vacation is saved', async () => {
                    await expect(freetimeEditDialog).toClick('.x-toolbar-right-row button', {text: 'Ok'});

                    // wait for loading starts and ends
                    await expect(employeeEditDialog).toMatchElement('.tine-hr-freetimegrid-type-VACATION .x-ux-pagingtb-refresh-disabled.x-item-disabled');
                    await employeeEditDialog.waitForFunction(() => !document.querySelector('.tine-hr-freetimegrid-type-VACATION .x-ux-pagingtb-refresh-disabled.x-item-disabled'));

                    await expect(employeeEditDialog).toClick('.x-grid3-col-description', {text: testString});
                });
            });

            describe('updated vacation', () => {
                let freetimeEditDialog;
                test('load vacation', async () => {
                    freetimeEditDialog = await lib.getEditDialog('Urlaubstage bearbeiten', employeeEditDialog);
                    await freetimeEditDialog.waitForTimeout(50);
                    await freetimeEditDialog.waitForFunction(() => document.querySelector('.ext-el-mask-msg.x-mask-loading div').textContent === 'Übertrage Freizeitanspruch...');
                    await freetimeEditDialog.waitForFunction(() => !document.querySelector('.ext-el-mask-msg.x-mask-loading div'));
                    await freetimeEditDialog.waitForSelector('.x-date-picker table > tbody > tr:nth-child(2) > td > table > tbody > tr:nth-child(3) > td:nth-child(4).x-date-selected');
                });

                test('vacation can be updated', async () => {
                    // feastAndFreeDays loaded/applied
                    await freetimeEditDialog.waitForSelector('.x-date-picker table > tbody > tr:nth-child(2) > td > table > tbody > tr:nth-child(3) > td:nth-child(7).x-date-disabled');
                    
                    await expect(freetimeEditDialog).toClick('.x-date-picker table > tbody > tr:nth-child(2) > td > table > tbody > tr:nth-child(3) > td:nth-child(2) > a > em > span');
                    await expect(freetimeEditDialog).toFill('textarea[name=description]', testString + ' update');

                    await expect(freetimeEditDialog).toClick('.x-toolbar-right-row button', {text: 'Ok'});

                    // wait for loading starts and ends
                    await expect(employeeEditDialog).toMatchElement('.tine-hr-freetimegrid-type-VACATION .x-ux-pagingtb-refresh-disabled.x-item-disabled');
                    await employeeEditDialog.waitForFunction(() => !document.querySelector('.tine-hr-freetimegrid-type-VACATION .x-ux-pagingtb-refresh-disabled.x-item-disabled'));
                    
                    await expect(employeeEditDialog).toClick('.x-grid3-col-description', {text: testString + ' update'});
                    
                    await employeeEditDialog.waitForTimeout(500); // wait till view is updated
                    const daysEl = await employeeEditDialog.$x('//label[text()="Anzahl der Tage:"]/following-sibling::div/div');
                    const days = await employeeEditDialog.evaluate(div => div.textContent, daysEl[0]);
                    
                    if (days !=2) {
                        throw new Error('days count mismatch');
                    }
                });
            });

            describe('delete vacation', () => {
                test('confirm dialog is shown', async () => {
                    await expect(employeeEditDialog).toClick('button', {text: 'Urlaubstage löschen'});
                    await expect(employeeEditDialog).toClick('button', {text: 'Ja'});
                });
                
                test('vacation is deleted', async () => {
                    // wait for loading starts and ends
                    await expect(employeeEditDialog).toMatchElement('.tine-hr-freetimegrid-type-VACATION .x-ux-pagingtb-refresh-disabled.x-item-disabled');
                    await employeeEditDialog.waitForFunction(() => !document.querySelector('.tine-hr-freetimegrid-type-VACATION .x-ux-pagingtb-refresh-disabled.x-item-disabled'));

                    await expect(employeeEditDialog).not.toMatchElement('.x-grid3-col-description', {text: testString});
                });
            });
        });

        describe('sickness (freetime)', () => {
            const testString = 'test sickness ' + Math.round(Math.random() * 10000000);
            test('sickness grid', async () => {
                await expect(employeeEditDialog).toClick('.x-tab-strip-text', {text: 'Krankheit'});
            });

            describe('add sickness', () => {
                let freetimeEditDialog;
                test('open dialog', async() => {
                    freetimeEditDialog = await lib.getEditDialog('Krankheitstage hinzufügen', employeeEditDialog);
                    await freetimeEditDialog.waitForTimeout(50);
                    await freetimeEditDialog.waitForFunction(() => document.querySelector('.ext-el-mask-msg.x-mask-loading div').textContent === 'Übertrage Freizeitanspruch...');
                    await freetimeEditDialog.waitForFunction(() => !document.querySelector('.ext-el-mask-msg.x-mask-loading div'));
                    await expect(freetimeEditDialog).toFill('textarea[name=description]', testString);
                });

                test('type is resolved', async () => {
                    if ('[K] Krankheit' !== await freetimeEditDialog.evaluate(() => document.querySelector('input[name=type]').value)) {
                        return Promise.reject('type not resolved');
                    }
                });

                test('exclude days (a sunday) are loaded and applied', async () => {
                    await freetimeEditDialog.waitForSelector('.x-date-picker table > tbody > tr:nth-child(2) > td > table > tbody > tr:nth-child(3) > td:nth-child(7).x-date-disabled');
                });

                test('status can be set', async () => {
                    await expect(freetimeEditDialog).toClick('input[name=sicknessStatus] + img');
                    await expect(freetimeEditDialog).toClick('.x-combo-list-item', {text: 'Unentschuldigt'});
                });
                
                test('dates can be selected', async () => {
                    await expect(freetimeEditDialog).toClick('.x-date-picker table > tbody > tr:nth-child(2) > td > table > tbody > tr:nth-child(2) > td:nth-child(2) > a > em > span');
                });
                
                test('sickness is saved', async () => {
                    await expect(freetimeEditDialog).toClick('.x-toolbar-right-row button', {text: 'Ok'});
                    
                    // wait for loading starts and ends
                    await expect(employeeEditDialog).toMatchElement('.tine-hr-freetimegrid-type-SICKNESS .x-ux-pagingtb-refresh-disabled.x-item-disabled');
                    await employeeEditDialog.waitForFunction(() => !document.querySelector('.tine-hr-freetimegrid-type-SICKNESS .x-ux-pagingtb-refresh-disabled.x-item-disabled'));

                    await employeeEditDialog.screenshot({path: 'screenshots/HumanResources/13_humanresources_mitarbeiter_krankheit.png'});

                    await expect(employeeEditDialog).toClick('.tine-hr-freetimegrid-type-SICKNESS .x-grid3-col-description', {text: testString});
                });
            });

            describe('book sickness as vacation', () => {
                test('can book sickness as vacation', async () => {
                    await expect(employeeEditDialog).toClick('.x-grid3-col-description', {text: testString, button: 'right'});
                    await expect(employeeEditDialog).toClick('.x-menu-item-text', {text: 'Als Urlaub buchen'});
                });
                test('sickness got vacation', async () => {
                    await expect(employeeEditDialog).toClick('.x-tab-strip-text', {text: 'Urlaub'});
                    await expect(employeeEditDialog).toClick('.tine-hr-freetimegrid-type-VACATION .x-grid3-col-description', {text: testString});
                });
            });
            
            describe('delete vacation (was sickness)', () => {
                test('confirm dialog is shown', async () => {
                    await expect(employeeEditDialog).toClick('button', {text: 'Urlaubstage löschen'});
                    await expect(employeeEditDialog).toClick('button', {text: 'Ja'});
                });

                test('vacation is deleted', async () => {
                    // wait for loading starts and ends
                    await expect(employeeEditDialog).toMatchElement('.tine-hr-freetimegrid-type-VACATION .x-ux-pagingtb-refresh-disabled.x-item-disabled');
                    await employeeEditDialog.waitForFunction(() => !document.querySelector('.tine-hr-freetimegrid-type-VACATION .x-ux-pagingtb-refresh-disabled.x-item-disabled'));

                    await expect(employeeEditDialog).not.toMatchElement('.tine-hr-freetimegrid-type-VACATION .x-grid3-col-description', {text: testString});
                });
            });
        });
    });
});

afterAll(async () => {
    browser.close();
});
