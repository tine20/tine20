/*
 * Tine 2.0
 * Sales combo box and store
 * 
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2014 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

Ext.ns('Tine.Sales');

/**
 * Customer selection combo box
 * 
 * @namespace   Tine.Sales
 * @class       Tine.Sales.CustomerSearchCombo
 * @extends     Ext.form.ComboBox
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Sales.CustomerSearchCombo
 */
Tine.Sales.CustomerSearchCombo = Ext.extend(Tine.Tinebase.widgets.form.RecordPickerComboBox, {
    
    allowBlank: false,
    minListWidth: 200,
    
    //private
    initComponent: function(){
        this.recordClass = Tine.Sales.Model.Customer;
        this.recordProxy = Tine.Sales.customerBackend;

        Tine.Sales.CustomerSearchCombo.superclass.initComponent.call(this);
        
        this.displayField = 'fulltext';
        this.sortBy = 'number';
    }
});

Tine.widgets.form.RecordPickerManager.register('Sales', 'Customer', Tine.Sales.CustomerSearchCombo);
