/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2013-2015 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.Sales');

/**
 * Address grid panel
 * 
 * @namespace   Tine.Sales
 * @class       Tine.Sales.AddressGridPanel
 * @extends     Tine.widgets.grid.BbarGridPanel
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
Tine.Sales.AddressGridPanel = Ext.extend(Tine.widgets.grid.BbarGridPanel, {
    
    /**
     * holds the type of the address currently handled, autoset by editDialog
     * 
     * @type {String}
     */
    addressType: null,

    defaultSortInfo: {field: 'countryname', direction: 'ASC'},
    
    /**
     * inits this cmp
     * 
     * @private
     */
    initComponent: function() {
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
        
        Ext.isArray(this.plugins) ? this.plugins.push(this.clipboardAction) : this.plugins = [this.clipboardAction];
        
        Tine.Sales.AddressGridPanel.superclass.initComponent.call(this);
    },

    /**
     * returns canonical path part
     * @returns {string}
     */
    getCanonicalPathSegment: function () {
        var pathSegment = '';
        if (this.canonicalName) {
            // simple segment e.g. when used in a dialog
            pathSegment = this.canonicalName;
        } else if (this.recordClass) {
            // auto segment
            pathSegment = [this.recordClass.getMeta('modelName'), Ext.util.Format.capitalize(this.addressType), 'Grid'].join(Tine.Tinebase.CanonicalPath.separator);
        }

        return pathSegment;
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
     * overwrites and calls superclass
     * 
     * @param {Object} button
     * @param {Tine.Tinebase.data.Record} record
     * @param {Array} plugins
     */
    onEditInNewWindow: function(button, record, plugins) {
        // the name 'button' should be changed as this can be called in other ways also
        button.fixedFields = {
            'customer_id':  this.editDialog.record.data,
            'type':         this.addressType,
            'parentRecord': this.editDialog.record.data
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
