/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <alex@stintzing.net>
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.Sipgate');

/**
 * Connection grid panel
 * 
 * @namespace   Tine.Sipgate
 * @class       Tine.Sipgate.GridPanel
 * @extends     Tine.widgets.grid.GridPanel
 * 
 * <p>Connection Grid Panel</p>
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
Tine.Sipgate.ConnectionGridPanel = Ext.extend(Tine.widgets.grid.GridPanel, {
    /**
     * record class
     * @cfg {Tine.Sipgate.Model.Connection} recordClass
     */
    recordClass: Tine.Sipgate.Model.Connection,
    evalGrants: false,
    /**
     * grid specific
     * @private
     */
    defaultSortInfo: {field: 'timestamp', direction: 'ASC'},
    gridConfig: {
        autoExpandColumn: 'contact_name'
    },
     
    /**
     * inits this cmp
     * @private
     */
    initComponent: function() {
        this.recordProxy = Tine.Sipgate.connectionBackend;
        this.gridConfig.columns = this.getColumns();
        
        this.initFilterToolbar();
        this.plugins.push(this.filterToolbar);
        
        Tine.Sipgate.ConnectionGridPanel.superclass.initComponent.call(this);
    },
    
    /**
     * initializes filter toolbar
     */
    initFilterToolbar: function() {
        this.filterToolbar = new Tine.widgets.grid.FilterToolbar({
            filterModels: Tine.Sipgate.Model.Connection.getFilterModel(),
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
                header : this.app.i18n._('TOS'),
                dataIndex : 'tos',
                width : 35,
                renderer: Tine.Tinebase.widgets.keyfield.Renderer.get('Sipgate', 'connectionTos'),
                scope: this,
                sortable: true
            }, {
                id : 'status',
                header : this.app.i18n._('Status'),
                dataIndex : 'status',
                width : 35,
                renderer: Tine.Tinebase.widgets.keyfield.Renderer.get('Sipgate', 'connectionStatus'),
                scope: this,
                sortable: true
            }, {
                id : 'remote_number',
                header : this.app.i18n._('Remote Number'),
                dataIndex : 'remote_number',
                sortable: true,
                hidden : false
            }, {
                id : 'local_number',
                header : this.app.i18n._('Local Number'),
                dataIndex : 'local_number',
                sortable: true,
                hidden : false
            }, {
                id : 'line_id',
                header : this.app.i18n._('Line'),
                dataIndex : 'line_id',
                sortable: true,
                hidden : false,
                renderer: this.lineRenderer
            }, {
                id : 'timestamp',
                header : this.app.i18n._('Call started'),
                renderer: Tine.Tinebase.common.dateTimeRenderer,
                dataIndex : 'timestamp',
                sortable: true,
                hidden : false
            }, {
                id : 'contact_id',
                header : this.app.i18n._('Contact'),
                dataIndex : 'contact_id',
                sortable: true,
                renderer: this.contactRenderer,
                hidden:false
            }
        ];
    },

    /**
     * renders the contact assigned to this connection
     * @param {Object} value
     * @param {Object} cell
     * @param {Object} record
     * @return {String}
     */
    contactRenderer: function(value, cell, record) {
        if(value) {
            return value.n_fn;
        } else {
            return record.get('contact_name');
        }
    },

    /**
     * returns the context menu
     * @return {Ext.menu.Menu}
     */
    getContextMenu: function() {
        if (! this.contextMenu) {
            var items = [];
            
            items.push(this.actions_addNumber);
            items.push(this.actions_dialNumber);
            
            this.contextMenu = new Ext.menu.Menu({
                items: items,
                plugins: [{
                    ptype: 'ux.itemregistry',
                    key:   this.app.appName + '-GridPanel-ContextMenu'
                }]
            });
            
            this.actionUpdater.addActions(items);
        }
        return this.contextMenu;
    },

    /**
     * returns action toolbar items
     * @return {Array}
     */
    getActionToolbarItems: function() {
        return [
            Ext.apply(new Ext.Button(this.actions_dialNumber), {
                scale: 'medium',
                rowspan: 2,
                iconAlign: 'top'
            }),
            Ext.apply(new Ext.Button(this.actions_addNumber), {
                scale: 'medium',
                rowspan: 2,
                iconAlign: 'top'
            })
        ];
    },

    /**
     * initializes the actions
     */
    initActions: function() {
        this.actions_addNumber = new Ext.Action({
            text : this.app.i18n._('Save number'),
            tooltip : this.app.i18n._('Adds this number to the Addressbook'),
            handler : this.onAddNumber,
            iconCls : 'action_AddNumber',
            scope : this
        });
            
        this.actions_dialNumber = new Ext.Action({
            text : this.app.i18n._('Dial number'),
            tooltip : this.app.i18n._('Initiates a new outgoing call'),
            handler : this.onDialPhoneNumber,
            iconCls : 'action_DialNumber',
            scope : this
        });
 
        this.action_addInNewWindow = new Ext.Action({
            disabled: ! Tine.Tinebase.common.hasRight('manage_accounts', 'Sipgate'),
            actionType: 'add',
            text: this.app.i18n._('Add Account'),
            handler: this.onAddAccountInNewWindow,
            iconCls: (this.newRecordIcon !== null) ? this.newRecordIcon : this.app.appName + 'IconCls',
            scope: this
        });
        this.getActionToolbar();
    },

    onAddNumber: function() {
        // check if addressbook app is available
        if (!Tine.Addressbook || !Tine.Tinebase.common.hasRight('run', 'Addressbook')) {
            return;
        }
        var number = this.getGrid().getSelectionModel().getSelections()[0].get('remote_number');
        var window = Tine.Sipgate.SearchAddressDialog.openWindow({
            number : number
        });
    },

    onDialPhoneNumber: function() {
        var sel = this.grid.getSelectionModel().getSelections(),
            contact = null,
            number = null;

        if(sel.length > 0) {
            number = sel[0]['data']['remote_number'];
            contact = sel[0]['data']['contact_id'] ? new Tine.Addressbook.Model.Contact(sel[0]['data']['contact_id']) : null;
        }

        var lineId = Tine.Sipgate.registry.get('preferences').get('phoneId');

        if(lineId && number) {
            Tine.Sipgate.lineBackend.dialNumber(lineId, number, contact);
        } else {
            Tine.Sipgate.DialNumberDialog.openWindow({number: number, contact: contact});
        }
    },
    /**
     * calls the AccountEditDialog
     */
    onAddAccountInNewWindow: function() {
        var cp = this.app.getMainScreen().getCenterPanel('Account');
        cp.onEditInNewWindow.call(cp, [{actionType: 'add'}]);
    },
    /**
     * renders the line
     * @param {Object} value
     * @return {String}
     */
    lineRenderer: function(value) {
        return value.uri_alias;
    }
});
