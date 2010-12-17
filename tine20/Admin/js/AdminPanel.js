/*
 * Tine 2.0
 * 
 * @package     Admin
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: AdminPanel.js 17913 2010-12-17 11:18:34Z p.schuele@metaways.de $
 *
 */

Ext.namespace('Tine.Admin');

/**
 * admin settings panel
 * 
 * @namespace   Tine.Admin
 * @class       Tine.Admin.AdminPanel
 * @extends     Tine.widgets.dialog.EditDialog
 * 
 * <p>Admin Admin Panel</p>
 * <p><pre>
 * TODO         generalize this
 * </pre></p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: AdminPanel.js 17913 2010-12-17 11:18:34Z p.schuele@metaways.de $
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Admin.AdminPanel
 */
Tine.Admin.AdminPanel = Ext.extend(Tine.widgets.dialog.EditDialog, {
    /**
     * @private
     */
    appName: 'Admin',
    recordClass: Tine.Tinebase.Model.Config,
    evalGrants: false,
    
    /**
     * overwrite update toolbars function (we don't have record grants yet)
     * @private
     */
//    updateToolbars: function() {
//    },

    /**
     * init record to edit
     */
    initRecord: function() {
        this.record = new this.recordClass({
            id: this.appName
        });
        this.loadRequest = this.recordProxy.loadRecord(this.record, {
            scope: this,
            success: function(record) {
                this.record = record;
                this.onRecordLoad();
            }
        });
    },
    
    /**
     * executed after record got updated from proxy
     */
    onRecordLoad: function() {
        // interrupt process flow until dialog is rendered
        if (! this.rendered) {
            this.onRecordLoad.defer(250, this);
            return;
        }
        
        this.window.setTitle(String.format(_('Change settings for application {0}'), this.appName));
        
        if (this.fireEvent('load', this) !== false) {
            //this.getForm().loadRecord(this.record.get('settings'));
            // TODO load settings into form
            this.getForm().clearInvalid();
            
            this.loadMask.hide();
        }
    },
    
    /**
     * executed when record gets updated from form
     */
    onRecordUpdate: function() {
        var form = this.getForm();
        
        // TODO merge changes from form into settings
        //form.updateRecord(this.record.get('settings'));
        
        // TODO update registry
    },
    
    /**
     * returns dialog
     * 
     * NOTE: when this method gets called, all initalisation is done.
     * 
     * @return {Object}
     * @private
     */
    getFormItems: function() {
        
        return {
            xtype: 'tabpanel',
            activeTab: 0,
            border: true,
            items: [{
                title: this.app.i18n._('Defaults'),
                autoScroll: true,
                border: false,
                frame: true,
                xtype: 'columnform',
                formDefaults: {
                    anchor: '90%',
                    labelSeparator: '',
                    columnWidth: 1
                },
                items: [[{
                    xtype: 'tinerecordpickercombobox',
                    fieldLabel: this.app.i18n._('Default Addressbook for new contacts and groups'),
                    name: 'defaultInternalAddressbook',
                    blurOnSelect: true,
                    recordClass: Tine.Tinebase.Model.Container,
                    recordProxy: Tine.Admin.sharedAddressbookBackend
                }]]
            }]
        };                
    } // end of getFormItems
});

/**
 * Admin admin settings popup
 * 
 * @param   {Object} config
 * @return  {Ext.ux.Window}
 */
Tine.Admin.AdminPanel.openWindow = function (config) {
    var id = (config.record && config.record.id) ? config.record.id : 0;
    var window = Tine.WindowFactory.getWindow({
        width: 600,
        height: 400,
        name: Tine.Admin.AdminPanel.prototype.windowNamePrefix + id,
        contentPanelConstructor: 'Tine.Admin.AdminPanel',
        contentPanelConstructorConfig: config
    });
    return window;
};
