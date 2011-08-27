/*
 * Tine 2.0
 * 
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/*global Ext, Tine*/

Ext.namespace('Tine.Addressbook');

/**
 * admin settings panel
 * 
 * @namespace   Tine.Addressbook
 * @class       Tine.Addressbook.AdminPanel
 * @extends     Tine.widgets.dialog.AdminPanel
 * 
 * <p>Addressbook Admin Panel</p>
 * <p><pre>
 * </pre></p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Addressbook.AddressbookPanel
 */
Tine.Addressbook.AdminPanel = Ext.extend(Tine.widgets.dialog.AdminPanel, {
    /**
     * @private
     */
    appName: 'Addressbook',
    
    /**
     * get config items
     * 
     * @return {Array}
     */
    getConfigItems: function () {
    	var addressTypeStore = [['adr_one_', this.app.i18n._('Company address')], ['adr_two_', this.app.i18n._('Private address')]];
    	
        return [[{
            xtype: 'combo',
            fieldLabel: this.app.i18n._('Default address for map panel'),
            name: 'defaultMapAddress',
            blurOnSelect: true,
            mode: 'local',
            forceSelection: true,
            typeAhead: true,
            triggerAction: 'all',
            store: addressTypeStore
        }]];
    }
});

/**
 * admin panel on update function
 * 
 * TODO         update registry without reloading the mainscreen
 */
Tine.Addressbook.AdminPanel.onUpdate = function () {
    // reload mainscreen to make sure registry gets updated
    window.location = window.location.href.replace(/#+.*/, '');
};

/**
 * Addressbook admin settings popup
 * 
 * @param   {Object} config
 * @return  {Ext.ux.Window}
 */
Tine.Addressbook.AdminPanel.openWindow = function (config) {
    var window = Tine.WindowFactory.getWindow({
        width: 600,
        height: 400,
        name: Tine.Addressbook.AdminPanel.prototype.windowNamePrefix + Ext.id(),
        contentPanelConstructor: 'Tine.Addressbook.AdminPanel',
        contentPanelConstructorConfig: config
    });
    return window;
};
