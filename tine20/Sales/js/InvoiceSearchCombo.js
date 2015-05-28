/*
 * Tine 2.0
 * Sales combo box and store
 * 
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Michael Spahn <kontakt@michaelspahn.de>
 * @copyright   Copyright (c) 2015 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

Ext.ns('Tine.Sales');

/**
 * Contract selection combo box
 * 
 * @namespace   Tine.Sales
 * @class       Tine.Sales.InvoiceSearchCombo
 * @extends     Ext.form.ComboBox
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Michael Spahn <kontakt@michaelspahn.de>
 * @copyright   Copyright (c) 2015 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Sales.InvoiceSearchCombo
 */
Tine.Sales.InvoiceSearchCombo = Ext.extend(Tine.Tinebase.widgets.form.RecordPickerComboBox, {
    
    allowBlank: false,
    minListWidth: 200,
    
    initComponent: function(){
        this.recordClass = Tine.Sales.Model.Invoice;
        this.recordProxy = Tine.Sales.invoiceBackend;

        Tine.Sales.InvoiceSearchCombo.superclass.initComponent.call(this);

        this.displayField = 'fulltext';
        this.sortBy = 'number';
    }
});

Tine.widgets.form.RecordPickerManager.register('Sales', 'Invoice', Tine.Sales.InvoiceSearchCombo);
