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
    }
});

