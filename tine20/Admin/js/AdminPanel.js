/*
 * Tine 2.0
 * 
 * @package     Admin
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
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
 * TODO         make saving work, somehow we lose the params (recordData) before the request is sent
 * TODO         generalize this
 * </pre></p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
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
    
    //private
    initComponent: function(){
        this.record = new this.recordClass({
            id: this.appName
        });    
        
        Tine.Admin.AdminPanel.superclass.initComponent.call(this);
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
            var settings = this.record.get('settings'),
                form = this.getForm();
            for (var setting in settings) {
                form.findField(setting).setValue(settings[setting]);
            }
        
            form.clearInvalid();
            
            this.loadMask.hide();
        }
    },
    
    /**
     * executed when record gets updated from form
     */
    onRecordUpdate: function() {
        // merge changes from form into settings
        var settings = this.record.get('settings'),
            form = this.getForm(),
            newSettings = {};

        for (var setting in settings) {
            newSettings[setting] = form.findField(setting).getValue();
        }
        
        this.record.set('settings', newSettings);

        //console.log(this.record);
        
        // TODO update registry
    },
    
    /**
     * on update
     * 
     * TODO make this work
     */
//    onUpdate: function() {
//        // reload mainscreen to make sure registry gets updated
//        window.location = window.location.href.replace(/#+.*/, '');
//    },

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
    }
});

/**
 * Admin admin settings popup
 * 
 * @param   {Object} config
 * @return  {Ext.ux.Window}
 */
Tine.Admin.AdminPanel.openWindow = function (config) {
    var window = Tine.WindowFactory.getWindow({
        width: 600,
        height: 400,
        name: Tine.Admin.AdminPanel.prototype.windowNamePrefix + Ext.id(),
        contentPanelConstructor: 'Tine.Admin.AdminPanel',
        contentPanelConstructorConfig: config
    });
    return window;
};
