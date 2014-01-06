/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2013-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.HumanResources');

/**
 * ExtraFreeTime grid panel
 * 
 * @namespace   Tine.HumanResources
 * @class       Tine.HumanResources.ExtraFreeTimeGridPanel
 * @extends     Tine.widgets.grid.GridPanel
 * 
 * <p>ExtraFreeTime Grid Panel</p>
 * <p><pre>
 * </pre></p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.HumanResources.ExtraFreeTimeGridPanel
 */
Tine.HumanResources.ExtraFreeTimeGridPanel = Ext.extend(Tine.widgets.grid.GridPanel, {
    /* local */
    storeRemoteSort: false,
    usePagingToolbar: false,
    
    /**
     * inits this cmp
     * 
     * @private
     */
    initComponent: function() {
        this.bbar = [];
        
        Tine.HumanResources.ExtraFreeTimeGridPanel.superclass.initComponent.call(this);
        this.fillBottomToolbar();
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
    onEditInNewWindow: function(button, record, plugins) {
        // the name 'button' should be changed as this can be called in other ways also
        button.fixedFields = {
            'account_id':  this.editDialog.record.data
        };
        Tine.HumanResources.ExtraFreeTimeGridPanel.superclass.onEditInNewWindow.call(this, button, record, plugins);
    },

    /**
     * called when the store gets updated, e.g. from editgrid
     * 
     * @param {Ext.data.store} store
     * @param {Tine.Tinebase.data.Record} record
     * @param {String} operation
     */
    onStoreUpdate: function(store, record, operation) {
        if (Ext.isObject(record.get('account_id'))) {
            record.set('account_id', record.get('account_id').id)
        }
        Tine.HumanResources.ExtraFreeTimeGridPanel.superclass.onStoreUpdate.call(this, store, record, operation);
    }
});

Tine.widgets.grid.RendererManager.register('HumanResources', 'ExtraFreeTime', 'status', Tine.HumanResources.ExtraFreeTimeGridPanel.prototype.renderStatus);