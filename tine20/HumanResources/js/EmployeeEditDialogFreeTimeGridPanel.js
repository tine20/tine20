/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.HumanResources');

/**
 * FreeTime grid panel
 * 
 * @namespace   Tine.HumanResources
 * @class       Tine.HumanResources.EmployeeEditDialogFreeTimeGridPanel
 * @extends     Tine.widgets.grid.GridPanel
 * 
 * <p>FreeTime Grid Panel</p>
 * <p><pre>
 * </pre></p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>    
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.HumanResources.EmployeeEditDialogFreeTimeGridPanel
 */
Tine.HumanResources.EmployeeEditDialogFreeTimeGridPanel = Ext.extend(Tine.widgets.grid.GridPanel, {
    
    editDialogRecordProperty: null,
    editDialog: null,
    storeRemoteSort: true,
    defaultSortInfo: {field: 'firstday_date', direction: 'DESC'},

    /**
     * set type before to diff vacation/sickness
     * @type
     */
    freetimeType: null,
    app: 'HumanResources',
    stateId: 'HumanResources-FreeTime-EEDGridPanel',

    /**
     * inits this cmp
     * 
     * @private
     */
    initComponent: function() {
        this.bbar = [];
        
        this.cls = 'tine-hr-freetimegrid-type-' + this.freetimeType;
        
        this.action_bookSicknessAsVacation = new Ext.Action({
            text: this.app.i18n._('Book as vacation'),
            hidden: true,
            handler: this.onBookSicknessAsVacation,
            scope: this,
            allowMultiple: false
        });
        
        this.contextMenuItems = [this.action_bookSicknessAsVacation];
        
        if (this.freetimeType) {
            if (this.freetimeType == 'SICKNESS') {
                this.setTitle(this.app.i18n._('Sickness'));
                this.i18nRecordName = this.app.i18n._('Sickness Days');
                this.i18nRecordsName = this.app.i18n._('Sickness Days');
            } else {
                this.setTitle(this.app.i18n._('Vacation'));
                this.i18nRecordName = this.app.i18n._('Vacation Days');
                this.i18nRecordsName = this.app.i18n._('Vacation Days');
            }
        }
        
        this.i18nEmptyText = this.i18nEmptyText || String.format(this.app.i18n._("There could not be found any {0}. Please try to change your filter-criteria or view-options."), this.i18nRecordsName);        

        this.recordClass = Tine.HumanResources.Model.FreeTime;
        this.modelConfig = this.recordClass.getModelConfiguration();

        Tine.HumanResources.EmployeeEditDialogFreeTimeGridPanel.superclass.initComponent.call(this);
        
        this.fillBottomToolbar();
    },

    onStoreBeforeload: function(store, options) {
        Tine.HumanResources.EmployeeEditDialogFreeTimeGridPanel.superclass.onStoreBeforeload.apply(this, arguments);
        options.params.filter.push({field: 'type', operator: 'equals', value: this.freetimeType});
        options.params.filter.push({field: 'employee_id', operator: 'equals', value: this.editDialog.record.id});
    },
    
    /**
     * book sickness as vacation
     * 
     * @param {Ext.grid.GridPanel} grid
     * @param {Ext.EventObject} e
     */
    onBookSicknessAsVacation: function(grid, e) {
        var record = this.getGrid().getSelectionModel().getSelections()[0];

        // check if enough vacation days are available
        var request = Ext.Ajax.request({
            url : 'index.php',
            params : { 
                method : 'HumanResources.getFeastAndFreeDays',
                _employeeId: record.get('employee_id'),
                _year: parseInt(record.get('firstday_date').format('Y')), 
                _freeTimeId: null,
                _accountId: record.get('account_id') ? record.get('account_id').id : null
            },
            success : function(_result, _request) {
                var response = Ext.decode(_result.responseText);
                this.onBookSicknessAsVacationSuccess(response.results, record);
            },
            failure : function(exception) {
                Tine.Tinebase.ExceptionHandler.handleRequestException(exception);
            },
            scope: this
        });
    },
    
    /**
     * callback if the request from onBookSicknessAsVacation doesn't fail
     * 
     * @param {Object} results
     * @param {Tine.HumanResources.Model.Freetime} record
     */
    onBookSicknessAsVacationSuccess: function(results, record) {
        // if there are not enough vacation days left
        if (results.remainingVacation < record.get('days_count')) {
            Ext.MessageBox.show({
                title: this.app.i18n._('Could not book as vacation'), 
                msg: this.app.i18n._('The unexcused sickness days could not be booked as vacation. There are not enough days left!'),
                buttons: Ext.Msg.OK,
                icon: Ext.MessageBox.WARNING
            });
            return;
        } 
        
        this.store.remove(record);
        record.set('type', 'vacation');
        record.set('status', 'ACCEPTED');
        
        Tine.HumanResources.saveFreeTime(record.data).then((response) => {
            this.editDialog.vacationGridPanel.getStore().reload();
        });
    },
    
    /**
     * returns rows context menu
     * 
     * @param {Ext.grid.GridPanel} grid
     * @param {Number} row
     * @param {Ext.EventObject} e
     */
    getContextMenu: function(grid, row, e) {
        var selModel = grid.getSelectionModel();
        this.action_bookSicknessAsVacation.setHidden(true);
        if (selModel.getSelections().length == 1) {
            if (selModel.getSelections()[0].data.status == 'UNEXCUSED') {
                this.action_bookSicknessAsVacation.setHidden(false);
            }
        }
        
        return Tine.HumanResources.EmployeeEditDialogFreeTimeGridPanel.superclass.getContextMenu.call(this, grid, row, e);
    },
    
    /**
     * will be called in Edit Dialog Mode
     */
    fillBottomToolbar: function() {
        var tbar = this.getBottomToolbar();
        tbar.addButton(new Ext.Button(this.action_editInNewWindow));
        tbar.addButton(new Ext.Button(this.action_addInNewWindow));
        tbar.addButton(new Ext.Button(this.action_deleteRecord));
    },
    
    /**
     * overwrites and calls superclass
     * 
     * @param {Object} button
     * @param {Tine.Tinebase.data.Record} record
     * @param {Array} plugins
     */
    onEditInNewWindow: function(button, record, plugins) {
        // the name 'button' should be changed as this can be called in other ways also
        button.fixedFields = {
            'employee_id': this.editDialog.record.data,
            'type':        this.freetimeType.toLowerCase() // we need the type obj here
        };
        // have division grants in dlg
        button.fixedFields.employee_id.division_id = this.editDialog.getForm().findField('division_id').selectedRecord.data;

        Tine.HumanResources.EmployeeEditDialogFreeTimeGridPanel.superclass.onEditInNewWindow.call(this, button, record, plugins);
    },

    createNewRecord: function() {
        const record = Tine.HumanResources.EmployeeEditDialogFreeTimeGridPanel.superclass.createNewRecord.call(this);
        record.set('type', this.freetimeType.toLowerCase());
        record.set('status', this.freetimeType === 'VACATION' ? 'ACCEPTED' : 'EXCUSED' );
        return record;
    },
    
    /**
     * overwrites the default function, no refactoring needed, this file will be deleted in the next release
     */
    initFilterPanel: function() {},
    
    // /**
    //  * renders the different status keyfields
    //  * @param {String} value
    //  * @param {Object} b
    //  * @param {Tine.HumanResources.Model.FreeTime} record
    //  */
    // renderStatus: function(value, row, record) {
    //     if (_.get(record, 'data.type.id', _.get(record, 'data.type')) === 'sickness') {
    //         if (! this.typeStatusPicker) {
    //             this.typeStatusPicker = Tine.Tinebase.widgets.keyfield.Renderer.get('HumanResources', 'freeTimeTypeStatus');
    //         }
    //         return this.typeStatusPicker(value, row, record);
    //     } else {
    //         if (! this.processStatusPicker) {
    //             this.processStatusPicker = Tine.Tinebase.widgets.keyfield.Renderer.get('HumanResources', 'freeTimeProcessStatus');
    //         }
    //         return this.processStatusPicker(value, row, record);
    //     }
    // },
    
    /**
     * called when the store gets updated, e.g. from editgrid
     * 
     * @param {Ext.data.store} store
     * @param {Tine.Tinebase.data.Record} record
     * @param {String} operation
     */
    onStoreUpdate: function(store, record, operation) {
        if (Ext.isObject(record.get('employee_id'))) {
            record.set('employee_id', record.get('employee_id').id)
        }
        
        Tine.HumanResources.EmployeeEditDialogFreeTimeGridPanel.superclass.onStoreUpdate.call(this, store, record, operation);
    }
});

// Tine.widgets.grid.RendererManager.register('HumanResources', 'FreeTime', 'status', Tine.HumanResources.EmployeeEditDialogFreeTimeGridPanel.prototype.renderStatus);
