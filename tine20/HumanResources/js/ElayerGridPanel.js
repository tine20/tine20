/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.HumanResources');

/**
 * @namespace   Tine.HumanResources
 * @class       Tine.HumanResources.EmployeeEditDialog
 * @extends     Tine.widgets.dialog.EditDialog
 * 
 * <p>Employee Compose Dialog</p>
 * <p></p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.HumanResources.EmployeeEditDialog
 */

Tine.HumanResources.ElayerGridPanel = Ext.extend(Ext.grid.EditorGridPanel, {
    
    /* private */
    
    /**
     * 
     * @type Ext.data.store 
     */
    store: null,
    recordClass: Tine.HumanResources.Model.Elayer,
    
    /* config */
    
    height: 100,
    
    /* public */
    
    app: null,
    record: null,
    editDialog: null,
    enableHdMenu: false,
    
    initComponent: function() {
        this.initStore();
        this.colModel = this.getColumnModel();
        
        this.initActions();
        this.contextMenu = new Ext.menu.Menu({
            items: [this.actionAdd, this.actionUnlink, this.actionEdit]
        });
        this.plugins = this.plugins || [];
    this.plugins.push(new Ext.ux.grid.GridViewMenuPlugin({}));
        // on rowcontextmenu handler
        this.listeners = {
            rowcontextmenu: function(grid, row, e) {
                alert('asd');
                e.stopEvent();
                var selModel = grid.getSelectionModel();
                if(!selModel.isSelected(row)) {
                    selModel.selectRow(row);
                }
                
                this.contextMenu.showAt(e.getXY());
            },
            scope:this
        };
        
        Tine.HumanResources.ElayerGridPanel.superclass.initComponent.call(this);
    },
    
    initActions: function() {
        this.actionAdd = new Ext.Action({
            text: _('Add Elayer'),
            tooltip: _('Add a new elayer to this employee'),
            iconCls: 'action_add',
            scope: this,
            handler: Ext.emptyFn
        });
        
        this.actionEdit = new Ext.Action({
            text: _('Add Elayer'),
            tooltip: _('Add a new elayer to this employee'),
            iconCls: 'actionEdit',
            scope: this,
            handler: Ext.emptyFn
        });
        
        this.actionUnlink = new Ext.Action({
            text: _('Unlink Elayer'),
            tooltip: _('Removes Elayer from this employee'),
            iconCls: 'action_remove',
            scope: this,
            handler: Ext.emptyFn
        });
        
        this.actions = [
            this.actionAdd,
            this.actionEdit,
            this.actionUnlink
        ];
    },
    
    getColumnModel: function() {
        return new Ext.grid.ColumnModel({
            defaults: {
                width: 120,
                sortable: true
            },
            columns: [
                {id: 'start_date', type: 'date', header: this.app.i18n._('Start Date')},
                {id: 'end_date', type: 'date', header: this.app.i18n._('End Date')},
                {id: 'vacation_days', type: 'int', header: this.app.i18n._('Vacation Days')},
                {id: 'cost_centre', type: 'string', header: this.app.i18n._('Cost Centre')},
                {id: 'working_hours', type: 'int', header: this.app.i18n._('Working Hours')}
                ]
        })
    },
    
    initStore: function() {
        this.store = new Ext.data.JsonStore({
        fields: (this.storeFields) ? this.storeFields : this.recordClass
    });

    // focus+select new record
    this.store.on('add', function(store, records, index) {
        (function() {
            if (this.rendered) {
                this.getView().focusRow(index);
                this.getSelectionModel().selectRow(index);
            }
        }).defer(300, this);
    }, this);
    }
    
});