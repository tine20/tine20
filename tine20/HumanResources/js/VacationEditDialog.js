/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.HumanResources');

/**
 * @namespace   Tine.HumanResources
 * @class       Tine.HumanResources.VacationEditDialog
 * @extends     Tine.widgets.dialog.EditDialog
 * 
 * <p>Vacation Compose Dialog</p>
 * <p></p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * Create a new Tine.HumanResources.VacationEditDialog
 */
Tine.HumanResources.VacationEditDialog = Ext.extend(Tine.HumanResources.FreeTimeEditDialog, {
    freetimeType: 'VACATION'
});

Tine.HumanResources.VacationEditDialog.openWindow  = function (cfg) {
    var id = (cfg.record && cfg.record.id) ? cfg.record.id : 0;
    var window = Tine.WindowFactory.getWindow({
        width: 440,
        height: 450,
        name: 'VacationEditWindow_' + id,
        contentPanelConstructor: 'Tine.HumanResources.VacationEditDialog',
        contentPanelConstructorConfig: cfg
    });
    return window;
};