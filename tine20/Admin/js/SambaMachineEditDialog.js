/*
 * Tine 2.0
 * 
 * @package     Admin
 * @subpackage  SambaMachine
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

Ext.ns('Tine.Admin.sambaMachine');

/**
 * @namespace   Tine.Admin.sambaMachine
 * @class       Tine.Admin.SambaMachineEditDialog
 * @extends     Tine.widgets.dialog.EditDialog
 */
Tine.Admin.SambaMachineEditDialog  = Ext.extend(Tine.widgets.dialog.EditDialog, {
    
    /**
     * @private
     */
    windowNamePrefix: 'sambaMachineEditWindow_',
    appName: 'Admin',
    recordClass: Tine.Admin.Model.SambaMachine,
    recordProxy: Tine.Admin.sambaMachineBackend,
    evalGrants: false,
    
    getFormItems: function() {
        return {
            xtype: 'columnform',
            labelAlign: 'top',
            border: false,
            formDefaults: {
                xtype:'textfield',
                anchor: '100%',
                labelSeparator: '',
                columnWidth: 1
            },
            items: [[{
                fieldLabel: this.app.i18n._('Computer Name'),
                name: 'accountLoginName'
            }]]
        };
    }
});

/**
 * User edit popup
 */
Tine.Admin.SambaMachineEditDialog.openWindow = function (config) {
    var id = (config.record && config.record.id) ? config.record.id : 0;
    var window = Tine.WindowFactory.getWindow({
        width: 300,
        height: 100,
        name: Tine.Admin.SambaMachineEditDialog.windowNamePrefix + id,
        contentPanelConstructor: 'Tine.Admin.SambaMachineEditDialog',
        contentPanelConstructorConfig: config
    });
    return window;
};
