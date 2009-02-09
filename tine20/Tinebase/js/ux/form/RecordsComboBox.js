/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
 
Ext.namespace('Ext.ux.form');

/**
 * A ComboBox with a store that loads its data from a record in this format
 * {
 *  value:1,
 *  records:[[0,'text 1'],[1,'text 2']]
 * }
 * 
 */ 
Ext.ux.form.RecordsComboBox = Ext.extend(Ext.form.ComboBox, {
	
	/**
	 * overwrite setValue() to get records
	 * 
	 * @param {} value
	 */
    setValue: function(value) {
        var val = value;
        if('object' === typeof value && '[object Object]' === Object.prototype.toString.call(value)) {
            if(undefined !== value['records']) {
                this.store.loadData(value['records']);
            }
            val = value['value'];
        }
        Ext.ux.form.PopCombo.superclass.setValue.call(this, val);
    }
});

// register xtype
Ext.reg('reccombo', Ext.ux.form.RecordsComboBox);
