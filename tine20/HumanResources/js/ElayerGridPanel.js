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

Tine.HumanResources.ElayerGridPanel = Ext.extend(Ext.ux.grid.QuickaddGridPanel, {
    frame: true,
    border: true,
    autoScroll: true,
    layout: 'fit',
    defaultSortInfo: null,
    autoExpandColumn: 'cost_centre',
    quickaddMandatory: 'workingtime_id',
    clicksToEdit: 1,
    enableColumnHide:false,
    enableColumnMove:false,
    
    recordClass: Tine.HumanResources.Model.Elayer,

    /*
     * config
     */
    app: null,
    editDialog: null,
    
    initComponent: function() {
        if (!this.app) {
            this.app = Tine.Tinebase.appMgr.get(this.appName);
        }  
        this.defaultSortInfo = {field: 'start_date', direction: 'DESC'};
        this.title = this.app.i18n._('Elayers');
        this.recordProxy = Tine.HumanResources.elayerBackend;
        
        this.cm = this.getColumnModel();

        this.on('afteredit', this.onAfterRowEdit, this);
        Tine.HumanResources.ElayerGridPanel.superclass.initComponent.call(this);
    },
    
    onClose: function() {
        this.fireEvent('cancel');
        this.purgeListeners();
        this.window.close();
    },
    
    onRecordLoad: function() {
        this.store = new Tine.Tinebase.data.RecordStore({
            recordClass: this.recordClass,
            remoteSort: false,
            sortInfo: this.defaultSortInfo
        }, this);
        
        Ext.each(this.editDialog.record.get('elayers'), function(ar) {
            this.store.addSorted(new this.recordClass(ar));
        }, this);
        
    },

    
    
    /**
     * returns column model
     * 
     * @return Ext.grid.ColumnModel
     * @private
     */
    getColumnModel: function() {
        return new Ext.grid.ColumnModel({
            defaults: {
                sortable: true,
//                menuDisabled: true,
                width: 160
            }, 
            columns: [
                {    dataIndex: 'workingtime_id',  id: 'workingtime_id',  type: Tine.HumanResources.Model.WorkingTime,  header: this.app.i18n._('Working Time Model'),
                     quickaddField: Tine.widgets.form.RecordPickerManager.get('HumanResources', 'WorkingTime'), renderer: Tine.widgets.form.RecordPickerManager.get('HumanResources', 'WorkingTime') 
                }, { dataIndex: 'working_hours', id: 'working_hours', type: 'int',    header: this.app.i18n._('Working Hours'),
                     quickaddField: new Ext.form.TextField(), width: 100
                }, { dataIndex: 'start_date',    id: 'start_date',    type: 'date',   header: this.app.i18n._('Start Date'),
                     quickaddField: new Ext.ux.form.ClearableDateField(), renderer: new Ext.ux.form.ClearableDateField()
                }, { dataIndex: 'vacation_days', id: 'vacation_days', type: 'int',    header: this.app.i18n._('Vacation Days'),
                     quickaddField: new Ext.form.TextField(), width: 100
                }, { dataIndex: 'cost_centre',   id: 'cost_centre',   type: 'string', header: this.app.i18n._('Cost Centre'),
                     quickaddField: new Ext.form.TextField()
                }, { dataIndex: 'end_date',      id: 'end_date',      type: 'date',   header: this.app.i18n._('End Date'),
                     renderer: Tine.Tinebase.common.dateRenderer
                }
           ]
       });
    }
    
//    onAfterRowEdit: function(o,r,s) {
//        console.warn(o,r,s);
//        Ext.each(this.editDialog.record.set('lines'), function(line) {
//            
//        }, this);
////        o.record.set('user_id', )
////        o.grid.store.save(o.record);
////        this.onUpdate(o.grid.store, o.record);
////        this.view.refresh();
//    }
    
//    onChange: function(combo, record,a,b) {
//        console.warn(combo, record,a,b);
////        this.recordProxy.saveRecord(record);
        
//    }
});

    
    
//    
//    /* private */
//    
//    /**
//     * 
//     * @type Ext.data.store 
//     */
//    store: null,
//    recordClass: Tine.HumanResources.Model.Elayer,
//    
//    /* config */
//    
//    height: 100,
//    defaultSortInfo: {field: 'start_date', direction: 'ASC'},
//    autoExpandColumn: 'cost_centre',
//
//    /* public */
//    
//    app: null,
//    record: null,
//    editDialog: null,
//    enableHdMenu: false,
//    
//    initComponent: function() {
////        this.initStore();
//        this.colModel = this.getColumnModel();
//        
//        this.initActions();
//        this.contextMenu = new Ext.menu.Menu({
//            items: [this.actionAdd, this.actionUnlink, this.actionEdit]
//        });
//        this.plugins = this.plugins || [];
//        this.plugins.push(new Ext.ux.grid.GridViewMenuPlugin({}));
//        // on rowcontextmenu handler
////        this.listeners = {
////            rowcontextmenu: function(grid, row, e) {
////                alert('asd');
////                e.stopEvent();
////                var selModel = grid.getSelectionModel();
////                if(!selModel.isSelected(row)) {
////                    selModel.selectRow(row);
////                }
////                
////                this.contextMenu.showAt(e.getXY());
////            },
////            scope:this
////        };
//        
//        Tine.HumanResources.ElayerGridPanel.superclass.initComponent.call(this);
//    },
//    
//    initActions: function() {
//        this.actionAdd = new Ext.Action({
//            text: _('Add Elayer'),
//            tooltip: _('Add a new elayer to this employee'),
//            iconCls: 'action_add',
//            scope: this,
//            handler: Ext.emptyFn
//        });
//        
//        this.actionEdit = new Ext.Action({
//            text: _('Add Elayer'),
//            tooltip: _('Add a new elayer to this employee'),
//            iconCls: 'actionEdit',
//            scope: this,
//            handler: Ext.emptyFn
//        });
//        
//        this.actionUnlink = new Ext.Action({
//            text: _('Unlink Elayer'),
//            tooltip: _('Removes Elayer from this employee'),
//            iconCls: 'action_remove',
//            scope: this,
//            handler: Ext.emptyFn
//        });
//        
//        this.actions = [
//            this.actionAdd,
//            this.actionEdit,
//            this.actionUnlink
//        ];
//    },
//    
//    getColumnModel: function() {
//        return new Ext.grid.ColumnModel({
//            defaults: {
//                width: 120,
//                sortable: true
//            },
//            columns: [
//                {id: 'start_date', type: 'date', header: this.app.i18n._('Start Date')},
//                {id: 'end_date', type: 'date', header: this.app.i18n._('End Date')},
//                {id: 'vacation_days', type: 'int', header: this.app.i18n._('Vacation Days')},
//                {id: 'cost_centre', type: 'string', header: this.app.i18n._('Cost Centre')},
//                {id: 'working_hours', type: 'int', header: this.app.i18n._('Working Hours')}
//                ]
//        })
//    },
//    
//    onRecordLoad: function() {
////        this.store = new Tine.Tinebase.data.RecordStore({
////                recordClass: this.recordClass
////            });
//        Ext.each(this.record.get('elayers'), function(record) {
//            console.warn(record);
//        }, this);
//    }
//    
////    initStore: function() {
////        this.store = new Ext.data.JsonStore({
////        fields: (this.storeFields) ? this.storeFields : this.recordClass
////    });
////
////    // focus+select new record
////    this.store.on('add', function(store, records, index) {
////        (function() {
////            if (this.rendered) {
////                this.getView().focusRow(index);
////                this.getSelectionModel().selectRow(index);
////            }
////        }).defer(300, this);
////    }, this);
////    }
//    
//});