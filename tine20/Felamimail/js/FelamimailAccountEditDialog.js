/**
 * Tine 2.0
 * 
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:MessageEditDialog.js 7170 2009-03-05 10:58:55Z p.schuele@metaways.de $
 *
 * TODO         add more input fields
 */
 
Ext.namespace('Tine.Felamimail');

/**
 * 
 * @class Tine.Felamimail.AccountEditDialog
 * @extends Tine.widgets.dialog.EditDialog
 */
Tine.Felamimail.AccountEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {
    
    /**
     * @private
     */
    windowNamePrefix: 'AccountEditWindow_',
    appName: 'Felamimail',
    recordClass: Tine.Felamimail.Model.Account,
    recordProxy: Tine.Felamimail.accountBackend,
    loadRecord: false,
    tbarItems: [],
    evalGrants: false,
    
    /**
     * overwrite update toolbars function (we don't have record grants yet)
     */
    updateToolbars: function() {

    },
    
    /**
     * returns dialog
     * 
     * NOTE: when this method gets called, all initalisation is done.
     */
    getFormItems: function() {
        return {
            autoScroll: true,
            border: false,
            frame: true,
            xtype: 'columnform',
            formDefaults: {
                xtype:'textfield',
                anchor: '90%',
                labelSeparator: '',
                maxLength: 256,
                columnWidth: 1
            },
            items: [[{
                fieldLabel: this.app.i18n._('Name'),
                name: 'name',
                allowBlank: false
            }, {
                fieldLabel: this.app.i18n._('Host'),
                name: 'host',
                allowBlank: false
            }]]      
        };
    }
});

/**
 * Felamimail Edit Popup
 */
Tine.Felamimail.AccountEditDialog.openWindow = function (config) {
    var id = (config.record && config.record.id) ? config.record.id : 0;
    var window = Tine.WindowFactory.getWindow({
        width: 400,
        height: 300,
        name: Tine.Felamimail.AccountEditDialog.prototype.windowNamePrefix + id,
        contentPanelConstructor: 'Tine.Felamimail.AccountEditDialog',
        contentPanelConstructorConfig: config
    });
    return window;
};
