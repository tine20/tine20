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

Tine.HumanResources.ContractGridPanel = Ext.extend(Tine.widgets.grid.QuickaddGridPanel, {
    /*
     * config
     */
    frame: true,
    border: true,
    autoScroll: true,
    layout: 'fit',
    defaultSortInfo: {field: 'start_date', direction: 'DESC'},
    autoExpandColumn: 'cost_center_id',
    quickaddMandatory: 'workingtime_id',
    clicksToEdit: 1,
    enableColumnHide:false,
    enableColumnMove:false,
    enableHdMenu: false,
    recordClass: Tine.HumanResources.Model.Contract,
    validate: true,
    /*
     * public
     */
    app: null,
    
    /**
     * the calling editDialog
     * Tine.HumanResources.EmployeeEditDialog
     */
    editDialog: null,
    
    /**
     * initializes the component
     */
    initComponent: function() {
        this.title = this.app.i18n.ngettext('Contract', 'Contracts', 2),
        Tine.HumanResources.ContractGridPanel.superclass.initComponent.call(this);
        this.store.sortInfo = this.defaultSortInfo;
        this.on('afteredit', this.onAfterEdit, this);
        this.store.sort();
    },
    
    /**
     * loads the existing contracts into the store
     */
    onRecordLoad: function() {
        var c = this.editDialog.record.get('contracts');
        if(Ext.isArray(c)) {
            Ext.each(c, function(ar) {
                this.store.addSorted(new this.recordClass(ar));
            }, this);
        }
    },
    doBlur: function() {
        Tine.HumanResources.ContractGridPanel.superclass.doBlur.call(this);
    },
    
    /**
     * new entry event -> add new record to store
     * @see Tine.widgets.grid.QuickaddGridPanel
     * @param {Object} recordData
     * @return {Boolean}
     */
    onNewentry: function(recordData) {
        recordData.workingtime_id = this.workingTimeQuickAdd.selectedRecord.data;
        recordData.employee_id = this.editDialog.record.get('id');
        recordData.feast_calendar_id = this.calendarQuickAdd.selectedContainer;
        this.store.addSorted(new this.recordClass(recordData));
    },
    
    /**
     * returns column model
     * 
     * @return Ext.grid.ColumnModel
     * @private
     */
    getColumnModel: function() {
        this.workingTimeQuickAdd = Tine.widgets.form.RecordPickerManager.get('HumanResources', 'WorkingTime', {blurOnSelect: true, allowBlank: false});
        this.workingTimeEditor = Tine.widgets.form.RecordPickerManager.get('HumanResources', 'WorkingTime', {blurOnSelect: true, allowBlank: false});
        var calConfig = {
            hideLabel: true,
            containerName: this.app.i18n._('Calendar'),
            containersName: this.app.i18n._('Calendars'),
            appName: 'Calendar',
            requiredGrant: 'readGrant',
            hideTrigger2: true,
            allowBlank: true,
            blurOnSelect: true
        };

        this.calendarQuickAdd = Tine.widgets.form.RecordPickerManager.get('Tinebase', 'Container', calConfig);
        this.calendarEditor = Tine.widgets.form.RecordPickerManager.get('Tinebase', 'Container', calConfig);
        this.costCenterEditor = Tine.widgets.form.RecordPickerManager.get('Sales', 'CostCenter');
        
        return new Ext.grid.ColumnModel({
            defaults: {
                sortable: true,
                width: 160,
                editable: true
            }, 
            columns: [
                {    dataIndex: 'workingtime_id',  id: 'workingtime_id',  type: Tine.HumanResources.Model.WorkingTime,  header: this.app.i18n._('Working Time Model'),
                     quickaddField: this.workingTimeQuickAdd, editor: this.workingTimeEditor,
                     renderer: this.renderWorkingTime, scope: this
                }, { dataIndex: 'vacation_days', id: 'vacation_days', type: 'integer', header: this.app.i18n._('Vacation Days'),
                     quickaddField: new Ext.form.TextField({allowBlank: false, regex: /^\d+$/ }), width: 90, editor: new Ext.form.TextField({allowBlank: false, regex: /^\d+$/})
                }, { dataIndex: 'cost_center_id', width:50,  id: 'cost_center_id', type: Tine.Sales.Model.CostCenter, header: this.app.i18n._('Cost Centre'),
                     quickaddField: Tine.widgets.form.RecordPickerManager.get('Sales', 'CostCenter'), renderer: this.renderCostCenter,
                     editor: this.costCenterEditor
                }, { dataIndex: 'start_date',    id: 'start_date',    type: 'date',   header: this.app.i18n._('Start Date'),
                     quickaddField : new Ext.ux.form.ClearableDateField(), renderer: Tine.Tinebase.common.dateRenderer,
                     editor: new Ext.ux.form.ClearableDateField()
                }, { dataIndex: 'end_date',      id: 'end_date',      type: 'date',   header: this.app.i18n._('End Date'),
                     quickaddField : new Ext.ux.form.ClearableDateField(), renderer: Tine.Tinebase.common.dateRenderer,
                     editor: new Ext.ux.form.ClearableDateField()
                }, { dataIndex: 'feast_calendar_id', width: 280, id: 'feast_calendar_id',  header: this.app.i18n._('Feast Calendar'),
                     renderer: Tine.Tinebase.common.containerRenderer, scope: this,
                     quickaddField: this.calendarQuickAdd, editor: this.calendarEditor
                }
           ]
       });
    },
    
    onBeforeEdit: function(o) {
        console.warn(o);
    },
    
    /**
     * is called on after edit to set related records
     * @param {} o
     */
    onAfterEdit: function(o) {
        switch (o.field) {
            case 'workingtime_id':
                    o.record.set('workingtime_id', this.workingTimeEditor.selectedRecord.data);
                break;
            case 'feast_calendar_id':
                o.record.set('feast_calendar_id', this.calendarEditor.selectedContainer);
                break;
            case 'cost_center_id':
                o.record.set('cost_center_id', this.costCenterEditor.selectedRecord.data);
                break;
        }
    },
    
    /**
     * renders the working time
     * @param {Object} value
     * @return {String}
     */
    renderWorkingTime: function(value) {
        if(value && Ext.isObject(value)) {
            return Ext.util.Format.htmlEncode(value.title);
        } else {
            return _('undefined');
        }
    },
    /**
     * renders the cost center
     * @param {Object} value
     * return {String}
     */
    renderCostCenter: function(value) {
        if(value && Ext.isObject(value)) {
            return Ext.util.Format.htmlEncode(value.number);
        } else {
            return _('undefined');
        }
    }
});

