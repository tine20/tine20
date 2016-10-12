/*
 * Tine 2.0
 * Addressbook industry combo box
 * 
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2016 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

Ext.ns('Tine.Addressbook');

/**
 * Contract selection combo box
 * 
 * @namespace   Tine.Addressbook
 * @class       Tine.Addressbook.IndustrySearchCombo
 * @extends     Ext.form.ComboBox
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>log
 * @copyright   Copyright (c) 2016 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Addressbook.IndustrySearchCombo
 */
Tine.Addressbook.IndustrySearchCombo = Ext.extend(Tine.Tinebase.widgets.form.RecordPickerComboBox, {
    
    initComponent: function(){
        this.recordClass = Tine.Addressbook.Model.Industry;

        Tine.Addressbook.IndustrySearchCombo.superclass.initComponent.call(this);

        this.displayField = 'name';
        this.sortBy = 'name';
    }
});

Tine.widgets.form.RecordPickerManager.register('Addressbook', 'Industry', Tine.Addressbook.IndustrySearchCombo);