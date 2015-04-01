/*
 * Tine 2.0
 * Sales combo box and store
 * 
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2014-2015 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

Ext.ns('Tine.Sales');

/**
 * Supplier selection combo box
 * 
 * @namespace   Tine.Sales
 * @class       Tine.Sales.SupplierSearchCombo
 * @extends     Ext.form.ComboBox
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2014-2015 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Sales.SupplierSearchCombo
 */
Tine.Sales.SupplierSearchCombo = Ext.extend(Tine.Tinebase.widgets.form.RecordPickerComboBox, {
    
    allowBlank: false,
    minListWidth: 200,
    
    //private
    initComponent: function(){
        this.recordClass = Tine.Sales.Model.Supplier;
        this.recordProxy = Tine.Sales.supplierBackend;

        Tine.Sales.SupplierSearchCombo.superclass.initComponent.call(this);
        
        this.displayField = 'fulltext';
        this.sortBy = 'number';
    }
});

Tine.widgets.form.RecordPickerManager.register('Sales', 'Supplier', Tine.Sales.SupplierSearchCombo);
