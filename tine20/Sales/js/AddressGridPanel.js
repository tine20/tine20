/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.Sales');

/**
 * Address grid panel
 * 
 * @namespace   Tine.Sales
 * @class       Tine.Sales.AddressGridPanel
 * @extends     Tine.widgets.grid.GridPanel
 * 
 * <p>Address Grid Panel</p>
 * <p><pre>
 * </pre></p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>    
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Sales.AddressGridPanel
 */
Tine.Sales.AddressGridPanel = Ext.extend(Tine.widgets.grid.GridPanel, {
    
    editDialogRecordProperty: null,
    editDialog: null,
    
    /**
     * holds the type of the address currently handled, autoset by editDialog
     * 
     * @type {String}
     */
    addressType: null,
    
    storeRemoteSort: false,
    defaultSortInfo: {field: 'countryname', direction: 'ASC'},
    
    usePagingToolbar: false,
    
    /**
     * inits this cmp
     * 
     * @private
     */
    initComponent: function() {
        this.bbar = [];
        
        this.i18nEmptyText = this.i18nEmptyText || String.format(this.app.i18n._("There could not be found any {0}. Please try to change your filter-criteria or view-options."), this.i18nRecordsName);

        this.clipboardAction = new Ext.ux.grid.ActionColumnPlugin({
            header: this.app.i18n._('Clipboard'),
            keepSelection: false,
            actions: [{
                iconIndex: 'copy_clipboard',
                iconCls: 'clipboard',
                tooltip: this.app.i18n._('Copy address to the clipboard'),
                callback: this.onCopyToClipboard,
                name: 'clipboard'
            }]
        });
        
        this.plugins = Ext.isArray(this.plugins) ? this.plugins.push(this.clipboardAction) : [this.clipboardAction];
        
        Tine.Sales.AddressGridPanel.superclass.initComponent.call(this);
        
        this.fillBottomToolbar();
        
        this.store.on('add', this.updateTitle, this);
        this.store.on('remove', this.updateTitle, this);
        
        this.updateTitle(((this.editDialog.record.data.hasOwnProperty(this.editDialogRecordProperty) && this.editDialog.record.data[this.editDialogRecordProperty]) ? this.editDialog.record.data[this.editDialogRecordProperty].length : 0));
    },
    
    /**
     * called from this.clipboardAction
     * 
     * @param {Number} rowIndex
     */
    onCopyToClipboard: function(rowIndex) {
        var record = this.store.getAt(rowIndex);
        var companyName = this.editDialog.record.get('name');
        Tine.Sales.addToClipboard(record, companyName);
    },
    
    /**
     * overwrites the default function, no refactoring needed, this file will be deleted in the next release
     */
    initFilterPanel: function() {},
    
    /**
     * updates the title ot the tab
     * 
     * @param {Number} count
     */
    updateTitle: function(count) {
        count = Ext.isNumber(count) ? count : this.store.getCount();
        this.setTitle((count > 1 ? this.i18nRecordsName : this.i18nRecordName) + ' (' + count + ')');
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
            'customer_id': this.editDialog.record.data,
            'type':        this.addressType
        };
        
        var additionalConfig = {addressType: this.addressType};
        
        Tine.Sales.AddressGridPanel.superclass.onEditInNewWindow.call(this, button, record, plugins, additionalConfig);
    },
    
    
    /**
     * template method to allow adding custom columns
     * 
     * @return {Array}
     */
    getCustomColumns: function() {
        return [ this.clipboardAction ];
    }
});
