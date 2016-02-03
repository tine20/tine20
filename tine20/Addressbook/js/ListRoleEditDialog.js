/*
 * Tine 2.0
 *
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2016 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/*global Ext, Tine*/

Ext.ns('Tine.Addressbook');

/**
 * @namespace   Tine.Addressbook
 * @class       Tine.Addressbook.ListRoleEditDialog
 * @extends     Tine.widgets.dialog.EditDialog
 * Addressbook Edit Dialog <br>
 *
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */
Tine.Addressbook.ListRoleEditDialog = Ext.extend(Tine.widgets.dialog.SimpleRecordEditDialog, {
    windowNamePrefix: 'ListRoleEditWindow_',
    appName: 'Addressbook',
    recordClass: Tine.Addressbook.Model.ListRole,
});

/**
 * Opens a new contact edit dialog window
 *
 * @return {Ext.ux.Window}
 */
Tine.Addressbook.ListRoleEditDialog.openWindow = function (config) {
    var id = (config.record && config.record.id) ? config.record.id : 0;
    var window = Tine.WindowFactory.getWindow({
        width: 400,
        height: 300,
        name: Tine.Addressbook.ListRoleEditDialog.prototype.windowNamePrefix + id,
        contentPanelConstructor: 'Tine.Addressbook.ListRoleEditDialog',
        contentPanelConstructorConfig: config
    });
    return window;
};
