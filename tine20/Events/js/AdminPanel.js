/*
 * Tine 2.0
 * 
 * @package     Events
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2015 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

Ext.namespace('Tine.Events');

/**
 * admin settings panel
 * 
 * @namespace   Tine.Events
 * @class       Tine.Events.AdminPanel
 * @extends     Ext.TabPanel
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2015 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Events.AdminPanel
 */
Tine.Events.AdminPanel = Ext.extend(Ext.TabPanel, {

    border: false,
    activeTab: 0,

    /**
     * @private
     */
    initComponent: function() {
        
        this.app = Tine.Tinebase.appMgr.get('Events');
        
        this.items = [
            new Tine.Admin.config.GridPanel({
                configApp: this.app
            })

        ];
        
        Tine.Events.AdminPanel.superclass.initComponent.call(this);
    }
});
    
/**
 * Events Admin Panel Popup
 * 
 * @param   {Object} config
 * @return  {Ext.ux.Window}
 */
Tine.Events.AdminPanel.openWindow = function (config) {
    var window = Tine.WindowFactory.getWindow({
        width: 600,
        height: 470,
        name: 'eventsapp-admin-panel',
        contentPanelConstructor: 'Tine.Events.AdminPanel',
        contentPanelConstructorConfig: config
    });
};
