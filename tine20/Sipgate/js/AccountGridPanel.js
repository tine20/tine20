/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <alex@stintzing.net>
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.Sipgate');

/**
 * Account grid panel
 * 
 * @namespace   Tine.Sipgate
 * @class       Tine.Sipgate.GridPanel
 * @extends     Tine.widgets.grid.GridPanel
 * 
 * <p>Account Grid Panel</p>
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
Tine.Sipgate.AccountGridPanel = Ext.extend(Tine.widgets.grid.GridPanel, {
    /**
     * record class
     * @cfg {Tine.Sipgate.Model.Account} recordClass
     */
    recordClass: Tine.Sipgate.Model.Account,
    evalGrants: false,
    /**
     * grid specific
     * @private
     */
    defaultSortInfo: {field: 'accounttype', direction: 'DESC'},
    gridConfig: {
        autoExpandColumn: 'description'
    },
    /**
     * possible TOS
     * @type {Object}
     */
    lineTypes: null,
    /**
     * inits this cmp
     * @private
     */
    initComponent: function() {
        this.lineTypes = {
            voice:  this.app.i18n._('Telephone'),
            fax:    this.app.i18n._('Fax')
        };
        this.recordProxy = Tine.Sipgate.accountBackend;
        this.gridConfig.columns = this.getColumns();
        
        this.initFilterToolbar();
        this.plugins.push(this.filterToolbar);
        
        Tine.Sipgate.AccountGridPanel.superclass.initComponent.call(this);
    },
    
    /**
     * initializes filter toolbar
     */
    initFilterToolbar: function() {
        this.filterToolbar = new Tine.widgets.grid.FilterToolbar({
            filterModels: Tine.Sipgate.Model.Account.getFilterModel(),
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
                id : 'type',
                header : this.app.i18n._('Type'),
                dataIndex : 'type',
                sortable: true,
                hidden: false,
                renderer: Tine.Tinebase.widgets.keyfield.Renderer.get('Sipgate', 'accountType'),
                scope: this,
                width: 100
            }, {
                id : 'accounttype',
                header : this.app.i18n._('Account Type'),
                dataIndex : 'accounttype',
                sortable: true,
                hidden: false,
                renderer: Tine.Tinebase.widgets.keyfield.Renderer.get('Sipgate', 'accountAccountType'),
                scope: this,
                width: 100
            }, {
                id : 'mobile_number',
                header : this.app.i18n._('Mobile Number'),
                dataIndex : 'mobile_number',
                sortable: true,
                hidden: false,
                scope: this,
                width: 100
            }, {
                id : 'description',
                header : this.app.i18n._('Uri Alias'),
                dataIndex : 'description',
                sortable: true,
                hidden : false
            }].concat(this.getModlogColumns());
    },
    
    initActions: function() {
        this.actions_dialNumber = new Ext.Action({
            text : this.app.i18n._('Dial number'),
            tooltip : this.app.i18n._('Initiates a new outgoing call'),
            handler : this.onDialPhoneNumber,
            iconCls : 'action_DialNumber',
            scope : this
        });
        Tine.Sipgate.AccountGridPanel.superclass.initActions.call(this);
    },
    
    getActionToolbarItems: function() {
        return [
            Ext.apply(new Ext.Button(this.actions_dialNumber), {
                scale: 'medium',
                rowspan: 2,
                iconAlign: 'top'
            })
        ];
    },
    /**
     * opens the dial number dialog 
     */
    onDialPhoneNumber: function() {
        var lineId = Tine.Sipgate.registry.get('preferences').get('phoneId');
        Tine.Sipgate.DialNumberDialog.openWindow({lineId: lineId});
    }
});
