/*
 * Tine 2.0
 * 
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Stefanie Stamer <s.stamer@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

Ext.namespace('Tine.Addressbook');

/**
 * admin settings panel
 * 
 * @namespace   Tine.Addressbook
 * @class       Tine.Addressbook.AdminPanel
 * @extends     Ext.TabPanel
 * 
 * <p>Addressbook Admin Panel</p>
 * <p><pre>
 * </pre></p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Stefanie Stamer <s.stamer@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Addressbook.AdminPanel
 */
Tine.Addressbook.AdminPanel = Ext.extend(Ext.TabPanel, {

    border: false,
    activeTab: 0,

    /**
     * @private
     */
    initComponent: function() {
        
        this.app = Tine.Tinebase.appMgr.get('Addressbook');
        
        this.items = [
            new Tine.Admin.config.GridPanel({
                configApp: this.app
            })
        ];
        
        Tine.Addressbook.AdminPanel.superclass.initComponent.call(this);
    }
});
    
/**
 * Addressbook Admin Panel Popup
 * 
 * @param   {Object} config
 * @return  {Ext.ux.Window}
 */
Tine.Addressbook.AdminPanel.openWindow = function (config) {
    var window = Tine.WindowFactory.getWindow({
        width: 600,
        height: 470,
        name: 'addressbook-admin-panel',
        contentPanelConstructor: 'Tine.Addressbook.AdminPanel',
        contentPanelConstructorConfig: config
    });
};
