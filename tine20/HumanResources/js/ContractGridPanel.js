/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012-2013 Metaways Infosystems GmbH (http://www.metaways.de)
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

Tine.HumanResources.ContractGridPanel = Ext.extend(Tine.widgets.grid.GridPanel, {
    /*
     * config
     */
    editDialogRecordProperty: 'contracts',
    /**
     * the calling editDialog
     * @type {Tine.HumanResources.EmployeeEditDialog}
     */
    editDialog: null,
    usePagingToolbar: false,
    
    /**
     * initializes the component
     */
    initComponent: function() {
        this.title = this.app.i18n.ngettext('Contract', 'Contracts', 2);
        if (this.editDialog) {
            this.bbar = [];
        }

        this.initDetailsPanel();
        
        Tine.HumanResources.ContractGridPanel.superclass.initComponent.call(this);

        if (this.editDialog) {
            this.fillBottomToolbar();
        }
    },
    
    /**
     * @private
     */
    initDetailsPanel: function() {
        this.detailsPanel = new Tine.HumanResources.ContractDetailsPanel({
            modelConfig: this.modelConfig,
            grid: this,
            app: this.app
        });
    },
    /**
     * will be called in Edit Dialog Mode
     */
    fillBottomToolbar: function() {
        this.action_deleteRecord.initialConfig.actionUpdater = function(action) {
            var selection = this.getGrid().getSelectionModel().getSelections();
            if (selection.length != 1) {
                action.disable();
                return;
            } else {
                var record = selection[0];
            }
            if (! record) {
                action.disable();
                return;
            }
            
            if (record.id.length == 13) {
                action.enable();
                return;
            }
            
            action.setDisabled(! (record.get('is_editable') == true)); // may be undefined
        }
        
        var tbar = this.getBottomToolbar();
        tbar.addButton(new Ext.Button(this.action_editInNewWindow));
        tbar.addButton(new Ext.Button(this.action_addInNewWindow));
        tbar.addButton(new Ext.Button(this.action_deleteRecord));
    },
    /**
     * Opens the required EditDialog
     * @param {Object} actionButton the button the action was called from
     * @param {Tine.Tinebase.data.Record} record the record to display/edit in the dialog
     * @param {Array} plugins the plugins used for the edit dialog
     * @return {Boolean}
     */
    onEditInNewWindow: function(button, record, plugins) {
        Tine.HumanResources.ContractGridPanel.superclass.onEditInNewWindow.call(this, button, record, plugins);
    },
    
    /**
     * called when Records have been added to the Store
     */
    onStoreAdd: function(store, records, index) {
        var contract = records[0];
        
        // has a temporary id -> check vacation overlap
        if (contract.id.length == 13) {
            this.checkVacationOverlap(contract);
        }
        
        Tine.HumanResources.ContractGridPanel.superclass.onStoreAdd.call(this, store, records, index);
    },
    
    /**
     * shows message if a previous defined vacation is in the time periodof the added contract
     * 
     * @param {Tine.Sales.Model.Contract} contract
     */
    checkVacationOverlap: function(contract) {
        var startDate = contract.get('start_date');
        var store = this.editDialog.vacationGridPanel.getStore();

        // if vacation grid panel has not been loaded, fetch records from the parent record
        if (store.getCount() == 0) {
            store = new Ext.util.MixedCollection();
            if (this.editDialog.record.data.vacation && this.editDialog.record.data.vacation.length) {
                for (var index = 0; index < this.editDialog.record.data.vacation.length; index++) {
                    store.add(new Tine.HumanResources.Model.FreeDay(this.editDialog.record.data.vacation[index]));
                }
            }
            
        }
        
        var found = false;
        store.each(function(record, index) {
            if (record.get('firstday_date') >= startDate) {
                found = true;
            }
        });
        
        if (found) {
            Ext.MessageBox.show({
                title: this.app.i18n._('Vacation in same period'), 
                msg: this.app.i18n._('There are some vacation days matching the period of the contract you added. After saving this employee, changing the contract is not possible anymore.'),
                buttons: Ext.Msg.OK,
                scope: this,
                icon: Ext.MessageBox.WARNING
            });
        }
    }
});

