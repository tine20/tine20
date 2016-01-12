/*
 * Tine 2.0
 * 
 * @package     ExampleApplication
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2015 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

Ext.namespace('Tine.ExampleApplication');

/**
 * admin settings panel
 * 
 * @namespace   Tine.ExampleApplication
 * @class       Tine.ExampleApplication.AdminPanel
 * @extends     Ext.TabPanel
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2015 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.ExampleApplication.AdminPanel
 */
Tine.ExampleApplication.AdminPanel = Ext.extend(Ext.TabPanel, {

    border: false,
    activeTab: 0,

    /**
     * @private
     */
    initComponent: function() {
        
        this.app = Tine.Tinebase.appMgr.get('ExampleApplication');
        
        this.items = [
            new Tine.Admin.config.GridPanel({
                configApp: this.app
            })

        ];
        
        Tine.ExampleApplication.AdminPanel.superclass.initComponent.call(this);
    }
});
    
/**
 * ExampleApplication Admin Panel Popup
 * 
 * @param   {Object} config
 * @return  {Ext.ux.Window}
 */
Tine.ExampleApplication.AdminPanel.openWindow = function (config) {
    var window = Tine.WindowFactory.getWindow({
        width: 600,
        height: 470,
        name: 'exampleapp-admin-panel',
        contentPanelConstructor: 'Tine.ExampleApplication.AdminPanel',
        contentPanelConstructorConfig: config
    });
};
