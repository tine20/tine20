/*
 * Tine 2.0
 * 
 * @package     ActiveSync
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2015 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

Ext.namespace('Tine.ActiveSync');

/**
 * admin settings panel
 * 
 * @namespace   Tine.ActiveSync
 * @class       Tine.ActiveSync.AdminPanel
 * @extends     Ext.TabPanel
 * 
 * <p>ActiveSync Admin Panel</p>
 * <p><pre>
 * </pre></p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.ActiveSync.AdminPanel
 */
Tine.ActiveSync.AdminPanel = Ext.extend(Ext.TabPanel, {

    border: false,
    activeTab: 0,

    /**
     * @private
     */
    initComponent: function() {
        
        this.app = Tine.Tinebase.appMgr.get('ActiveSync');
        
        this.items = [new Tine.ActiveSync.SyncDevicesGridPanel({
            title: this.app.i18n._('Sync Devices'),
            // TODO make this work
            disabled: ! Tine.Tinebase.common.hasRight('manage_devices', 'ActiveSync')
        })];
        
        Tine.ActiveSync.AdminPanel.superclass.initComponent.call(this);
    }
});
    
/**
 * ActiveSync Admin Panel Popup
 * 
 * @param   {Object} config
 * @return  {Ext.ux.Window}
 */
Tine.ActiveSync.AdminPanel.openWindow = function (config) {
    var window = Tine.WindowFactory.getWindow({
        width: 700,
        height: 470,
        name: 'activesync-manage-syncdevice',
        contentPanelConstructor: 'Tine.ActiveSync.AdminPanel',
        contentPanelConstructorConfig: config
    });
};
