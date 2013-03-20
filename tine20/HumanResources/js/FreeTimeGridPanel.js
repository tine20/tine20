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
    
    /**
     * inits this cmp
     * 
     * @private
     */
    initComponent: function() {
        this.bbar = [];
        
        Tine.HumanResources.FreeTimeGridPanel.superclass.initComponent.call(this);
        
        this.fillBottomToolbar();
        
        if (this.freetimeType) {
            if (this.freetimeType == 'SICKNESS') {
                this.setTitle(this.app.i18n._('Sickness'));
                this.i18nRecordName = this.app.i18n._('Sickness Day'),
                this.i18nRecordsName = this.app.i18n._('Sickness Days')
            } else {
                this.setTitle(this.app.i18n._('Vacation'));
                this.i18nRecordName = this.app.i18n._('Vacation Day');
                this.i18nRecordsName = this.app.i18n._('Vacation Days');
            }
            this.action_addInNewWindow.setText(String.format(_('Add {0}'), this.freetimeType == 'SICKNESS' ? this.app.i18n._('Sickness Days') : this.app.i18n._('Vacation Days')));
        }
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
     * @param {Array} addRelations
     */
    onEditInNewWindow: function(button, record, addRelations) {
        // the name 'button' should be changed as this can be called in other ways also
        button.fixedFields = {
            'employee_id': this.editDialog.record.data,
            'type':        this.freetimeType
        };
        
        Tine.HumanResources.FreeTimeGridPanel.superclass.onEditInNewWindow.call(this, button, record, addRelations);
    },
    
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