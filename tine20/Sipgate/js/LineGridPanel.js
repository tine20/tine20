/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <alex@stintzing.net>
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.Sipgate');

/**
 * Line grid panel
 * 
 * @namespace   Tine.Sipgate
 * @class       Tine.Sipgate.GridPanel
 * @extends     Tine.widgets.grid.GridPanel
 * 
 * <p>Line Grid Panel</p>
 * <p><pre>
 * </pre></p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <alex@stintzing.net>
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Sipgate.GridPanel
 */
Tine.Sipgate.LineGridPanel = Ext.extend(Tine.widgets.grid.GridPanel, {
    /**
     * record class
     * @cfg {Tine.Sipgate.Model.Line} recordClass
     */
    recordClass: Tine.Sipgate.Model.Line,
    evalGrants: false,
    /**
     * grid specific
     * @private
     */
    defaultSortInfo: {field: 'sip_uri', direction: 'DESC'},
    gridConfig: {
        autoExpandColumn: 'sip_uri'
    },
     
    lineTypes: null,
    /**
     * inits this cmp
     * @private
     */
    initComponent: function() {
        this.recordProxy = Tine.Sipgate.lineBackend;
        this.gridConfig.columns = this.getColumns();
        
        this.initFilterToolbar();
        this.plugins.push(this.filterToolbar);
        
        Tine.Sipgate.LineGridPanel.superclass.initComponent.call(this);
    },
    
    /**
     * initializes filter toolbar
     */
    initFilterToolbar: function() {
        this.filterToolbar = new Tine.widgets.grid.FilterToolbar({
            filterModels: Tine.Sipgate.Model.Line.getFilterModel(),
            defaultFilter: 'query',
            filters: [],
            plugins: [
                new Tine.widgets.grid.FilterToolbarQuickFilterPlugin()
            ]
        });
    },  
    
    /**
     * returns cm
     * 
     * @return Ext.grid.ColumnModel
     * @private
     */
    getColumns: function(){
        return [{
                id : 'tos',
                header : this.app.i18n._('Type'),
                dataIndex : 'tos',
                sortable: true,
                hidden:false,
                renderer: Tine.Tinebase.widgets.keyfield.Renderer.get('Sipgate', 'connectionTos'),
                scope: this
            }, {
                id : 'account_id',
                header : this.app.i18n._('Account'),
                dataIndex : 'account_id',
                width : 35,
                scope: this,
                sortable: true,
                renderer: this.accountRenderer
            }, {
                id : 'user_id',
                header : this.app.i18n._('User'),
                dataIndex : 'user_id',
                sortable: true,
                hidden : false,
                renderer: Tine.Tinebase.common.usernameRenderer
            }, {
                id : 'uri_alias',
                header : this.app.i18n._('Uri Alias'),
                dataIndex : 'uri_alias',
                sortable: true,
                hidden : false
            }, {
                id : 'sip_uri',
                header : this.app.i18n._('SIP Uri'),
                dataIndex : 'sip_uri',
                sortable: true,
                hidden : false
            }, {
                id : 'e164_in',
                header : this.app.i18n._('Incoming'),
                dataIndex : 'e164_in',
                sortable: true,
                hidden : false,
                renderer: Tine.Sipgate.common.renderE164In,
                scope:this
            }, {
                id : 'e164_out',
                header : this.app.i18n._('Outgoing'),
                dataIndex : 'e164_out',
                sortable: true,
                hidden : false
            }, {
                id : 'creation_time',
                header : this.app.i18n._('Created'),
                renderer: Tine.Tinebase.common.dateTimeRenderer,
                dataIndex : 'creation_time',
                sortable: true,
                hidden : true
            }];
    },
    
    accountRenderer: function(value) {
        return Ext.util.Format.htmlEncode(value.description);
    },
    
    getContextMenu: function() {
        if (! this.contextMenu) {
            var items = [];
            
            items.push(this.actions_syncLine);
            
            this.contextMenu = new Ext.menu.Menu({
                items: items,
                plugins: [{
                    ptype: 'ux.itemregistry',
                    key:   this.app.appName + '-GridPanel-ContextMenu'
                }]
            });
        }
        return this.contextMenu;
    },
    
    getActionToolbarItems: function() {
        return [
            Ext.apply(new Ext.Button(this.actions_dialNumber), {
                scale: 'medium',
                rowspan: 2,
                iconAlign: 'top'
            }),
            Ext.apply(new Ext.Button(this.actions_syncLine), {
                scale: 'medium',
                rowspan: 2,
                iconAlign: 'top'
            })
        ];
    },
    
    initActions: function() {
        
        var userMaySync = Tine.Tinebase.common.hasRight('sync_lines', this.app.name);
        
        this.actions_dialNumber = new Ext.Action({
            text : this.app.i18n._('Dial number'),
            tooltip : this.app.i18n._('Initiates a new outgoing call'),
            handler : this.onDialPhoneNumber,
            iconCls : 'action_DialNumber',
            scope : this
        });
        this.actions_syncLine = new Ext.Action({
            text: this.app.i18n._('Synchronize Line'),
            singularText: this.app.i18n._('Synchronize Line') + '&nbsp;&nbsp;&nbsp;&nbsp;',
            pluralText:  this.app.i18n._('Synchronize Lines') + '&nbsp;&nbsp;',
            allowMultiple: true,
            tooltip : this.app.i18n._('Synchronizes the selected line(s)'),
            handler : this.onSyncLine.createDelegate(this),
            disabled: true,
            iconCls : 'action_Sync',
            scope : this,
            actionUpdater: function(action, grants, records) {
                // sync right
                if (! userMaySync) return;
                
                if(records.length > 0) {
                    this.actions_syncLine.setDisabled(0);
                    if(records.length > 1) {
                        this.actions_syncLine.setText(this.actions_syncLine.initialConfig.pluralText);
                    } else {
                        this.actions_syncLine.setText(this.actions_syncLine.initialConfig.singularText);
                    }
                } else {
                    this.actions_syncLine.setDisabled(1);
                }
            }
        });
        
        this.grid.store.on('load', function() {
            this.actions_syncLine.initialConfig.actionUpdater.call(this, null, null, this.getGrid().getSelectionModel().getSelections());
        }, this);
        
        this.action_addInNewWindow = new Ext.Action({
            disabled: ! Tine.Tinebase.common.hasRight('manage_accounts', 'Sipgate'),
            actionType: 'add',
            text: this.app.i18n._('Add Account'),
            handler: this.onAddAccountInNewWindow,
            iconCls: (this.newRecordIcon !== null) ? this.newRecordIcon : this.app.appName + 'IconCls',
            scope: this
        });
        
                    
        this.actionUpdater.addActions([
            this.actions_dialNumber,
            this.action_addInNewWindow,
            this.actions_syncLine
        ]);
        
        this.getActionToolbar();
    },
    
    /**
     * calls the AccountEditDialog
     */
    onAddAccountInNewWindow: function() {
        var cp = this.app.getMainScreen().getCenterPanel('Account');
        cp.onEditInNewWindow.call(cp, [{actionType: 'add'}]);
    },
    
    onSyncLine: function() {
        this.loadMask = new Ext.LoadMask(this.grid.getEl(), {
                        msg: _('Synchronizing...')
                    });
        this.loadMask.show();    
        var sel = this.grid.getSelectionModel().getSelections();
        var request = this.recordProxy.syncConnections(sel, this.onSyncSuccess, this.handleRequestException, this);
    },
    
    /**
     * opens the dial number dialog 
     */
    onDialPhoneNumber: function() {
        var lineId = Tine.Sipgate.registry.get('preferences').get('phoneId');
        Tine.Sipgate.DialNumberDialog.openWindow({lineId: lineId});
    },
    
    handleRequestException: function(exception) {
        Tine.Sipgate.handleRequestException(exception);
    },
    
    onSyncSuccess: function(response) {
        this.loadMask.hide();
    }
});
