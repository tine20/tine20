/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.Phone');

require('../../Voipmanager/js/Snom/PhoneEditDialog');

/**
 * @namespace   Tine.Phone
 * @class       Tine.Phone.CallEditDialog
 * @extends     Tine.widgets.dialog.EditDialog
 * 
 * <p>Call Compose Dialog</p>
 * <p></p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Phone.CallEditDialog
 */

Tine.Phone.CallGridPanel = Ext.extend(Tine.widgets.grid.GridPanel, {
    
    /**
     * the panel holds the phones available for the user
     * 
     * @type {Ext.tree.TreePanel}
     */
    phoneTreePanel: null,
    
    /**
     * 
     * @type {Ext.Action}
     */
    editPhoneSettingsAction: null,
    
    /**
     * initializes the component
     */
    initComponent: function() {
        Tine.Phone.CallGridPanel.superclass.initComponent.call(this);
        this.initPhoneTreePanel();
    },
    
    /**
     * @private
     */
    initActions: function() {
        this.action_editPhoneSettings = new Ext.Action({
            text: this.app.i18n._('Edit phone settings'),
            iconCls: 'PhoneIconCls',
            handler: this.onEditPhoneSettings.createDelegate(this),
            scope: this,
            actionUpdater: function(action, grants, records) {
                if (this.phoneTreePanel.getActiveNode()) {
                    this.action_editPhoneSettings.setDisabled(0);
                } else {
                    this.action_editPhoneSettings.setDisabled(1);
                }
            }
        });
        
        this.action_dialNumber = new Ext.Action({
            text: this.app.i18n._('Dial number'),
            tooltip: this.app.i18n._('Initiate a new outgoing call'),
            handler: this.onDialNumber,
            iconCls: 'action_DialNumber',
            scope: this,
            actionUpdater: function(action, grants, records) {
                if (records.length != 1) {
                    this.action_dialNumber.setDisabled(1);
                } else {
                    this.action_dialNumber.setDisabled(0);
                }
            }
        });
        
        // register actions in updater
        this.actionUpdater.addActions([
            this.action_editPhoneSettings,
            this.action_dialNumber
        ]);
        this.getActionToolbar();
    },
    
    /**
     * row doubleclick handler
     * 
     * @param {} grid
     * @param {} row
     * @param {} e
     */
    onRowDblClick: function(grid, row, e) {
        this.onDialNumber(grid, row, e);
    },
    
    /**
     * get action toolbar
     * 
     * @return {Ext.Toolbar}
     */
    getActionToolbar: function() {
        if (! this.actionToolbar) {
            this.actionToolbar = new Ext.Toolbar({
                items: [{
                    xtype: 'buttongroup',
                    plugins: [{
                        ptype: 'ux.itemregistry',
                        key:   this.app.appName + '-' + this.recordClass.prototype.modelName + '-GridPanel-ActionToolbar-leftbtngrp'
                    }],
                    items: [
                        Ext.apply(new Ext.Button(this.action_editPhoneSettings), {
                            scale: 'medium',
                            rowspan: 2,
                            iconAlign: 'top'
                        }),
                        Ext.apply(new Ext.Button(this.action_dialNumber), {
                            scale: 'medium',
                            rowspan: 2,
                            iconAlign: 'top'
                        })
                    ]
                }]
            });
        }
        
        if (this.filterToolbar && typeof this.filterToolbar.getQuickFilterField == 'function') {
            this.actionToolbar.add('->', this.filterToolbar.getQuickFilterField());
        }
        
        return this.actionToolbar;
    },
     
    /**
     * add custom items to context menu
     * 
     * @return {Array}
     */
    getContextMenuItems: function() {
        var items = [
            '-',
            this.action_dialNumber
            ];
        
        return items;
    },
    
    /**
     * is called on dial a number
     */
    onDialNumber: function() {
        var record = this.grid.getSelectionModel().getSelected(),
            number = false;
        if (record) {
            if (record.get('resolved_destination') != '') {
                number = record.get('resolved_destination');
            } else {
                number = record.get('destination');
            }
        }
        Tine.Phone.dialPhoneNumber(number);
    },
    
    /**
     * initializes the phone tree panel
     */
    initPhoneTreePanel: function() {
        this.phoneTreePanel = new Tine.Phone.PhoneTreePanel({
            app: this.app,
            title: this.app.i18n._('Phones'),
            grid: this
        });
        
        var westPanel = this.app.getMainScreen().getWestPanel().getPortalColumn();
        westPanel.add(this.phoneTreePanel);
        
        this.phoneTreePanel.updateTree();
    },
    
    /**
     * called before store queries for data
     */
    onStoreBeforeload: function(store, options) {
        Tine.Phone.CallGridPanel.superclass.onStoreBeforeload.apply(this, arguments);
        var node = this.phoneTreePanel.getActiveNode();
        if (node && node.hasOwnProperty('attributes')) {
            var r = node.attributes.record;
            if (! options.params) options.params = {};
            options.params.filter = [
                {field: 'phone_id', operator: 'AND', value: [{field: ':id', operator: 'equals', value: r.get('id')}]}
            ];
        }
        
    },
    
    /**
     * is called on edit phone button click
     */
    onEditPhoneSettings: function() {
        var node = this.phoneTreePanel.getActiveNode();
        if (node) {
            var rp = Tine.Phone.myphoneBackend;
                rp.recordReader = function(response) {
                    var record = new Tine.Phone.Model.MyPhone(Ext.decode(response.responseText));
                    var recordId = record.get(record.idProperty);
                    record.id = recordId ? recordId : 0;
                    return record;
                };
                
            var popupWindow = Tine.Voipmanager.SnomPhoneEditDialog.openWindow({
                    recordProxy: rp,
                    record: node.attributes.record,
                    appName: 'Phone',
                    modelName: 'MyPhone'
                });
            
        } else {
            Ext.Msg.show({
                title: this.app.i18n._('No phone selected'), 
                msg: this.app.i18n._('Please select the desired phone in the tree!'),
                icon: Ext.MessageBox.INFO,
                buttons: Ext.Msg.OK,
                scope: this
            });
        }
    }
});

