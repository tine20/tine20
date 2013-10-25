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
 * @class       Tine.HumanResources.FreeTimeGridPanel
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
 * Create a new Tine.HumanResources.FreeTimeGridPanel
 */
Tine.HumanResources.FreeTimeGridPanel = Ext.extend(Tine.widgets.grid.GridPanel, {
    
    editDialogRecordProperty: null,
    editDialog: null,
    
    /**
     * set type before to diff vacation/sickness
     * @type 
     */
    freetimeType: null,
    
    usePagingToolbar: false,
    
    /**
     * inits this cmp
     * 
     * @private
     */
    initComponent: function() {
        this.bbar = [];
        
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
        
        Tine.HumanResources.FreeTimeGridPanel.superclass.initComponent.call(this);
        
        this.fillBottomToolbar();
    },
    
    /**
     * book sickness as vacation
     * 
     * @param {Ext.grid.GridPanel} grid
     * @param {Ext.EventObject} e
     */
    onBookSicknessAsVacation: function(grid, e) {
        var record = this.getGrid().getSelectionModel().getSelections()[0];
        
        this.store.remove(record);
        record.set('type', 'vacation');
        record.set('status', 'ACCEPTED');
        this.editDialog.vacationGridPanel.getStore().add(record);
        
        // set sickness of parent record (employee)
        var sickness = [];
        this.getGrid().getStore().each(function(item) {
            sickness.push(item.data);
        });
        this.editDialog.record.set('sickness', sickness);
        
        // set vacation of parent record (employee)
        var vacation = [];
        
        this.editDialog.vacationGridPanel.getStore().each(function(item) {
            vacation.push(item.data);
        });
        
        this.editDialog.record.set('vacation', vacation);
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
        
        return Tine.HumanResources.FreeTimeGridPanel.superclass.getContextMenu.call(this, grid, row, e);
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
            'type':        this.freetimeType
        };
        
        // collect free days not saved already
        var localFreedays = {}, localSicknessdays = {};
        
        this.editDialog.vacationGridPanel.store.each(function(record) {
            if (record.id && record.id.length == 13) {
                var accountId = Ext.isObject(record.get('account_id')) ? record.get('account_id').id : record.get('account_id');
                if (! localFreedays.hasOwnProperty(accountId)) {
                    localFreedays[accountId] = [];
                }
                localFreedays[accountId] = localFreedays[accountId].concat(record.data.freedays ? record.data.freedays : []);
            }
        }, this);
        
        this.editDialog.sicknessGridPanel.store.each(function(record) {
            if (record.id && record.id.length == 13) {
                var accountId = Ext.isObject(record.get('account_id')) ? record.get('account_id').id : record.get('account_id');
                if (! localSicknessdays.hasOwnProperty(accountId)) {
                    localSicknessdays[accountId] = [];
                }
                localSicknessdays[accountId] = localSicknessdays[accountId].concat(record.data.freedays ? record.data.freedays : []);
            }
        });
        
        var additionalConfig = {
            localFreedays: localFreedays,
            localSicknessdays: localSicknessdays
        };
        
        this.editDialogClass = (this.freetimeType == 'SICKNESS') ? Tine.HumanResources.SicknessEditDialog : Tine.HumanResources.VacationEditDialog;
            
        Tine.HumanResources.FreeTimeGridPanel.superclass.onEditInNewWindow.call(this, button, record, plugins, additionalConfig);
    },
    /**
     * overwrites the default function, no refactoring needed, this file will be deleted in the next release
     */
    initFilterPanel: function() {},
    
    /**
     * renders the different status keyfields
     * @param {String} value
     * @param {Object} b
     * @param {Tine.HumanResources.Model.FreeTime} record
     */
    renderStatus: function(value, row, record) {
        if (record.get('type') == 'sickness') {
            if (! this.sicknessStatusRenderer) {
                this.sicknessStatusRenderer = Tine.Tinebase.widgets.keyfield.Renderer.get('HumanResources', 'sicknessStatus');
            }
            return this.sicknessStatusRenderer(value, row, record);
        } else {
            if (! this.vacationStatusRenderer) {
                this.vacationStatusRenderer = Tine.Tinebase.widgets.keyfield.Renderer.get('HumanResources', 'vacationStatus');
            }
            return this.vacationStatusRenderer(value, row, record);
        }
    },
    /**
     * renders the type
     * @param {String} value
     * @param {Object} b
     * @param {Tine.HumanResources.Model.FreeTime} record
     */
    renderType: function(value, row, record) {
        if (! this.app) {
            this.app = Tine.Tinebase.appMgr.get('HumanResources');
        }
        
        if (record.get('type') == 'sickness') {
            return this.app.i18n._('Sickness');
        } else {
            return this.app.i18n._('Vacation');
        }
    },
    
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
        Tine.HumanResources.FreeTimeGridPanel.superclass.onStoreUpdate.call(this, store, record, operation);
    }
});

Tine.widgets.grid.RendererManager.register('HumanResources', 'FreeTime', 'status', Tine.HumanResources.FreeTimeGridPanel.prototype.renderStatus);
Tine.widgets.grid.RendererManager.register('HumanResources', 'FreeTime', 'type', Tine.HumanResources.FreeTimeGridPanel.prototype.renderType);
