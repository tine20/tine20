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
    /*
     * config
     */
    frame: true,
    border: true,
    autoScroll: true,
    layout: 'fit',
    defaultSortInfo: {field: 'start_date', direction: 'DESC'},
    autoExpandColumn: 'cost_centre',
    quickaddMandatory: 'workingtime_id',
    clicksToEdit: 1,
    enableColumnHide:false,
    enableColumnMove:false,
    enableHdMenu: false,
    recordClass: Tine.HumanResources.Model.Elayer,
    
    /*
     * public
     */
    app: null,
    editDialog: null,
    
    initComponent: function() {
        this.title = this.app.i18n._('Elayers');
        Tine.HumanResources.ElayerGridPanel.superclass.initComponent.call(this);
        this.store.sortInfo = this.defaultSortInfo;
        this.on('afteredit', this.onAfterEdit, this);
        this.store.sort();
    },
    
    onRecordLoad: function() {
        Ext.each(this.editDialog.record.get('elayers'), function(ar) {
            this.store.addSorted(new this.recordClass(ar));
        }, this);
        
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
        
        this.workingTimeQuickAdd = Tine.widgets.form.RecordPickerManager.get('HumanResources', 'WorkingTime', {blurOnSelect: true});
        this.workingTimeEditor = Tine.widgets.form.RecordPickerManager.get('HumanResources', 'WorkingTime', {blurOnSelect: true});
        var calConfig = {
            hideLabel: true,
            containerName: this.app.i18n._('Calendar'),
            containersName: this.app.i18n._('Calendars'),
            appName: 'Calendar',
            requiredGrant: 'readGrant',
            hideTrigger2: true,
            allowBlank: false
        };

        this.calendarQuickAdd = Tine.widgets.form.RecordPickerManager.get('Tinebase', 'Container', calConfig);
        this.calendarEditor = Tine.widgets.form.RecordPickerManager.get('Tinebase', 'Container', calConfig);
        this.calendarEditor.on('blur', function() {
            if(this.calendarEditor.hasFocusedSubPanels) {
                return false;
            }
        }, this);
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
                }, { dataIndex: 'vacation_days', id: 'vacation_days', type: 'int',    header: this.app.i18n._('Vacation Days'),
                     quickaddField: new Ext.form.TextField(), width: 90, editor: true
                }, { dataIndex: 'cost_centre', width:50,  id: 'cost_centre',   type: 'string', header: this.app.i18n._('Cost Centre'),
                     quickaddField: new Ext.form.TextField(), editor: true
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
    
    onAfterEdit: function(o) {
        switch (o.field) {
            case 'workingtime_id':
                    o.record.set('workingtime_id', this.workingTimeEditor.selectedRecord.data);
                break;
                case 'feast_calendar_id':
                    o.record.set('feast_calendar_id', this.calendarEditor.selectedContainer);
                break;
        }
    },
    
    /**
     * renders the working time
     * @param {Object} value
     * @return {String}
     */
    renderWorkingTime: function(value) {
        return Ext.util.Format.htmlEncode(value.title);
    }
    
});

