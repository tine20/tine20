/*
 * Tine 2.0
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:GridPanel.js 7170 2009-03-05 10:58:55Z p.schuele@metaways.de $
 */
Ext.ns('Tine.Tinebase');

/**
 * admin settings panel
 * 
 * @namespace   Tine.Tinebase
 * @class       Tine.Tinebase.AdminPanel
 * @extends     Ext.TabPanel
 * 
 * <p>Tinebase Admin Panel</p>
 * <p><pre>
 * </pre></p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:GridPanel.js 7170 2009-03-05 10:58:55Z p.schuele@metaways.de $
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Tinebase.AdminPanel
 */
Tine.Tinebase.AdminPanel = Ext.extend(Ext.TabPanel, {

    activeTab: 0,

    /**
     * @private
     */
    initComponent: function() {
        this.initProfileTable();
        
        this.items = [{
            title: _('Profile Information'),
            layout: 'fit',
            items: []
        }];
        
        Tine.Tinebase.AdminPanel.superclass.initComponent.call(this);
    },
    
    // NOTE we maintain a list of possible profile information here cause:
    // - it's not clear if the addressbook app is installed -> model is present
    // - we have no field names / translations in the models
    initProfileTable: function() {
        console.log(Tine.Addressbook.Model.Contact.getField('n_given').label);
        /*
        {name: 'n_family'},
    {name: 'n_given'},
    {name: 'n_middle'},
    {name: 'n_prefix'},
    {name: 'n_suffix'},
    {name: 'n_fn'},
    {name: 'n_fileas'},
    {name: 'bday', type: 'date', dateFormat: Date.patterns.ISO8601Long },
        this.profileTable = '';
        */
        //this.profileTable = {html: 'test'};
    }
    
    
});
    
/**
 * Tinebase Admin Panel Popup
 * 
 * @param   {Object} config
 * @return  {Ext.ux.Window}
 */
Tine.Tinebase.AdminPanel.openWindow = function (config) {
    var window = Tine.WindowFactory.getWindow({
        width: 500,
        height: 470,
        name: 'tinebase-admin-panel',
        contentPanelConstructor: 'Tine.Tinebase.AdminPanel',
        contentPanelConstructorConfig: config
    }); 
};
