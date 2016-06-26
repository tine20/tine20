/*
 * Tine 2.0
 * 
 * @package     Crm
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2015 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

Ext.namespace('Tine.Crm');

/**
 * admin settings panel
 * 
 * @namespace   Tine.Crm
 * @class       Tine.Crm.AdminPanel
 * @extends     Tine.widgets.dialog.EditDialog
 * 
 * <p>Crm Admin Panel</p>
 * <p><pre>
 * TODO         generalize this
 * TODO         revert/rollback changes onCancel
 * </pre></p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2015 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Crm.AdminPanel
 */
Tine.Crm.AdminPanel = Ext.extend(Ext.TabPanel, {

    border: false,
    activeTab: 0,

    /**
     * @private
     */
    initComponent: function() {

        this.app = Tine.Tinebase.appMgr.get('Crm');

        this.items = [
            new Tine.Admin.config.GridPanel({
                configApp: this.app
            })

        ];

        this.supr().initComponent.call(this);
    }
});

/**
 * Crm admin settings popup
 * 
 * @param   {Object} config
 * @return  {Ext.ux.Window}
 */
Tine.Crm.AdminPanel.openWindow = function (config) {
    var id = (config.record && config.record.id) ? config.record.id : 0;
    var window = Tine.WindowFactory.getWindow({
        width: 600,
        height: 470,
        name: Tine.Crm.AdminPanel.prototype.windowNamePrefix + id,
        contentPanelConstructor: 'Tine.Crm.AdminPanel',
        contentPanelConstructorConfig: config
    });
    return window;
};
