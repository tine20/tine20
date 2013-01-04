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
    quickaddMandatory: 'workingtime_id',
    clicksToEdit: 1,
    enableColumnHide:false,
    enableColumnMove:false,
    enableHdMenu: false,
    recordClass: Tine.HumanResources.Model.Contract,
    validate: true,
    autoExpandColumn: 'workingtime_id',
    /*
     * public
     */
    app: null,
    
    /**
     * the calling editDialog
     * @type {Tine.HumanResources.EmployeeEditDialog}
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
        if (Ext.isArray(c)) {
            Ext.each(c, function(ar) {
                this.store.addSorted(new this.recordClass(ar));
            }, this);
        }
    },
    
    /**
     * new entry event -> add new record to store
     * @see Tine.widgets.grid.QuickaddGridPanel
     * @param {Object} recordData
     * @return {Boolean}
     */
    onNewentry: function(recordData) {
        
        if (this.store.getCount() == 0) {
            recordData.start_date = recordData.start_date ? recordData.start_date : this.editDialog.record.get('employment_begin') ? this.editDialog.record.get('employment_begin') : this.editDialog.form.findField('employment_begin').getValue();
            recordData.end_date = recordData.end_date ? recordData.end_date : this.editDialog.record.get('employment_end') ? this.editDialog.record.get('employment_end') : this.editDialog.form.findField('employment_end').getValue();
        }
        
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
            blurOnSelect: true,
            value: Tine.HumanResources.registry.get('defaultFeastCalendar')
        };

        this.calendarQuickAdd = Tine.widgets.form.RecordPickerManager.get('Tinebase', 'Container', calConfig);
        this.calendarEditor = Tine.widgets.form.RecordPickerManager.get('Tinebase', 'Container', calConfig);
        
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
        }
    },
    
    /**
     * renders the working time
     * @param {Object} value
     * @return {String}
     */
    renderWorkingTime: function(value) {
        return value && Ext.isObject(value) ? Ext.util.Format.htmlEncode(value.title) : '';
    },
    
    initActions: function() {
        Tine.HumanResources.ContractGridPanel.superclass.initActions.call(this);
        this.editWorkingTimeAction = new Ext.Action({
            text: this.app.i18n._('Edit Working Time'),
            iconCls: 'HumanResourcesWorkingTime',
            handler : this.onEditWorkingTime,
            scope: this,
            disabled: false
        });
    },
    
    /**
     * creates and returns the context  menu
     * @return {Ext.menu.Menu}
     */
    getContextMenu: function() {
        if (! this.contextMenu) {
            var items = [this.deleteAction, this.editWorkingTimeAction];
            
            this.contextMenu = new Ext.menu.Menu({
                items: items
            });
        }
        
        return this.contextMenu;
    },
    
    /**
     * this.editWorkingtimeAction handler
     */
    onEditWorkingTime: function() {
        var selectedRow = this.getSelectionModel().getSelections()[0];
        if (! selectedRow || ! selectedRow.hasOwnProperty('data')) {
            return;
        }
        
        var config = { record: Ext.encode(selectedRow.data.workingtime_id), contract: Ext.encode(selectedRow.data), employeeName: this.editDialog.record.get('n_fn')};
        var window = Tine.HumanResources.WorkingTimeEditDialog.openWindow(config);
        
        this.editDialog.openSubPanels.push(window);
        
        window.on('saveAndClose', function(updateJson, workingTimeJson) {
            
            var record = this.store.getById(selectedRow.data.id);
            var workingTime = Ext.decode(workingTimeJson);
            
            record.set('workingtime_json', updateJson);
            record.set('workingtime_id', workingTime);
            
            this.editDialog.openSubPanels.splice(this.editDialog.openSubPanels.indexOf(window), 1);
            
            window.purgeListeners();
            window.close();
        }, this);
    }
});

