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

Tine.HumanResources.ElayerGridPanel = Ext.extend(Tine.widgets.grid.QuickaddGridPanel, {
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
    enableHdMenu: false,
    
    initComponent: function() {
//        if (!this.app) {
//            this.app = Tine.Tinebase.appMgr.get(this.appName);
//        }  
//        this.defaultSortInfo = {field: 'start_date', direction: 'DESC'};
        this.title = this.app.i18n._('Elayers');
//        this.recordProxy = Tine.HumanResources.elayerBackend;
//        
//        this.cm = this.getColumnModel();
//
//        this.on('afteredit', this.onAfterRowEdit, this);
//        this.on('newentry', this.onNewEntry, this);
//        
        Tine.HumanResources.ElayerGridPanel.superclass.initComponent.call(this);
    },
//    
//    onNewEntry: function(recordData) {
//        recordData.employee_id = this.editDialog.record.get('id');
//        var record = new Tine.HumanResources.Model.WorkingTime(recordData);
//        this.store.add(record);
//        
//    },
    
//    onClose: function() {
//        this.fireEvent('cancel');
//        this.purgeListeners();
//        this.window.close();
//    },
    
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
                     quickaddField: Tine.widgets.form.RecordPickerManager.get('HumanResources', 'WorkingTime', {blurOnSelect: true}), //renderer: Tine.widgets.form.RecordPickerManager.get('HumanResources', 'WorkingTime'),
                     renderer: this.renderWorkingTime, scope: this
                }, { dataIndex: 'working_hours', id: 'working_hours', type: 'int',    header: this.app.i18n._('Working Hours'),
                     quickaddField: new Ext.form.TextField(), width: 100
                }, { dataIndex: 'start_date',    id: 'start_date',    type: 'date',   header: this.app.i18n._('Start Date'),
                     quickaddField : new Ext.ux.form.ClearableDateField(), renderer: Tine.Tinebase.common.dateRenderer
//								renderer : new Ext.ux.form.ClearableDateField()
                }, { dataIndex: 'vacation_days', id: 'vacation_days', type: 'int',    header: this.app.i18n._('Vacation Days'),
                     quickaddField: new Ext.form.TextField(), width: 100
                }, { dataIndex: 'cost_centre',   id: 'cost_centre',   type: 'string', header: this.app.i18n._('Cost Centre'),
                     quickaddField: new Ext.form.TextField()
                }, { dataIndex: 'end_date',      id: 'end_date',      type: 'date',   header: this.app.i18n._('End Date'),
                     renderer: Tine.Tinebase.common.dateRenderer
                }
           ]
       });
    },
    
    renderWorkingTime: function(value,a,b,c) {
        console.warn(a,b,c);
        console.warn(value);
        return Ext.util.Format.htmlEncode(value.title);
    }
    
});

