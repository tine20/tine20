/*
 * Tine 2.0
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 */
 
/*global Ext, Tine, Locale*/
 
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
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Tinebase.AdminPanel
 * @TODO        Invent some kind of registry for inner panels
 */
Tine.Tinebase.AdminPanel = Ext.extend(Ext.TabPanel, {
    
    activeItem: 0,
    border: false,
    
    /**
     * @private
     */
    initComponent: function () {
        this.items = new Tine.Tinebase.Admin.UserProfileConfigPanel({});
        
        Tine.Tinebase.AdminPanel.superclass.initComponent.call(this);
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

Ext.ns('Tine.Tinebase.Admin');
Tine.Tinebase.Admin.UserProfileConfigPanel = Ext.extend(Ext.Panel, { // TODO: extend some kind of AppAdminPanel

    layout: 'fit',
    border: false,
    
    /**
     * @private
     */
    initComponent: function () {
        this.title = _('Profile Information');
        this.items = [];
        
        this.applyAction = new Ext.Action({
            text: _('Apply'),
            disabled: true,
            iconCls: 'action_applyChanges',
            handler: this.applyConfig.createDelegate(this)
        });
        this.buttons = [this.applyAction];
        
        Tine.Tinebase.getUserProfileConfig(this.initProfileTable, this);
        
        this.supr().initComponent.call(this);
    },
    
    afterRender: function () {
        this.supr().afterRender.apply(this, arguments);
        
        this.loadMask = new Ext.LoadMask(this.getEl(), {msg: _('Please Wait')});
        if (! this.store) {
            (function () {
                this.loadMask.show();
            }).defer(50, this);
        }
    },
    
    applyConfig: function () {
        var userProfileConfig = {
            readableFields: [],
            updateableFields: []
        };
        
        this.store.each(function (field) {
            var fieldName = field.get('fieldName');
            
            if (field.get('readGrant')) {
                userProfileConfig.readableFields.push(fieldName);
            }
            if (field.get('editGrant')) {
                userProfileConfig.updateableFields.push(fieldName);
            }
        }, this);
        
        this.loadMask.show();
        Tine.Tinebase.setUserProfileConfig(userProfileConfig, function () {
            this.store.commitChanges();
            this.applyAction.setDisabled(true);
            this.loadMask.hide();
        }, this);
        
    },
    
    initProfileTable: function (userProfileConfig) {
        var adbI18n = new Locale.Gettext();
        adbI18n.textdomain('Addressbook');
        
        var fieldData = [];
        
        Ext.each(userProfileConfig.possibleFields, function (fieldName) {
            var fieldDefinition = Tine.Addressbook.Model.Contact.getField(fieldName);
            fieldData.push([
                fieldName,
                adbI18n._hidden(fieldDefinition.label),
                userProfileConfig.readableFields.indexOf(fieldName) >= 0,
                userProfileConfig.updateableFields.indexOf(fieldName) >= 0
            ]);
        }, this);
        
        this.store = new Ext.data.ArrayStore({
            autoDestroy: true,
            fields: ['fieldName', 'fieldLabel', 'readGrant', 'editGrant'],
            data: fieldData,
            listeners: {
                scope: this,
                update: this.onStoreUpdate
            }
        });
        
        var cbs = [
            new Ext.ux.grid.CheckColumn({
                header: _('Read'),
                tooltip: _('The field is readable part of the profile'),
                dataIndex: 'readGrant',
                width: 55
            }), new Ext.ux.grid.CheckColumn({
                header: _('Edit'),
                tooltip: _('The field is editable part of the profile'),
                dataIndex: 'editGrant',
                width: 55
            })
        ];
        
        this.userProfileConfigGrid = new Ext.grid.EditorGridPanel({
            layout: 'fit',
            store: this.store,
            autoExpandColumn: 'fieldName',
            plugins: cbs,
            columns: [{
                id: 'fieldName',
                header: _('Field Name'),
                dataIndex: 'fieldLabel'
            }].concat(cbs)
        });
        
        this.add(this.userProfileConfigGrid);
        this.doLayout();
        
        if (this.loadMask) {
            this.loadMask.hide();
        }
    },
    
    onStoreUpdate: function () {
        this.applyAction.setDisabled(false);
    }
});
