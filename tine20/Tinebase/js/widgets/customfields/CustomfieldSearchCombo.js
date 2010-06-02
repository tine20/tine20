/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
 
Ext.ns('Tine.widgets', 'Tine.widgets.customfields');

/**
 * CustomfieldsSearchCombo
 * 
 * @namespace   Tine.Tinebase.widgets.customfields
 * @class       Tine.Tinebase.widgets.customfields.CustomfieldSearchCombo
 * @extends     Ext.form.ComboBox
 * 
 * <p>Customfields Search Combo</p>
 * <p>
 * searches for custom field values 
 * 
 * <pre>
 * </pre></p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Tinebase.widgets.customfields.CustomfieldSearchCombo
 */
Tine.widgets.customfields.CustomfieldSearchCombo = Ext.extend(Ext.form.ComboBox, {
    
    /**
     * @property customfieldId
     * @type String
     */
    customfieldId: null,
    
    /**
     * config
     */
    typeAhead: true,
    forceSelection: false,
    triggerAction: 'all',
    minChars: 1,
    pageSize: 10,
    displayField: 'value',
    valueField: 'value',
    blurOnSelect: false,
    
    /**
     * @private
     */
	initComponent: function() {
        this.store = new Ext.data.JsonStore({
            fields: Tine.Tinebase.Model.CustomfieldValue,
            baseParams: {
                method: 'Tinebase.searchCustomFieldValues',
                sort: 'value',
                dir: 'ASC'
            },
            root: 'results',
            totalProperty: 'totalcount'
        });
        
        this.on('beforequery', this.onBeforeQuery, this);
        
        Tine.widgets.customfields.CustomfieldSearchCombo.superclass.initComponent.call(this);
    },
    
    /**
     * prepare paging
     * 
     * @param {Ext.data.Store} store
     * @param {Object} options
     */
    onBeforeLoad: function(store, options) {
        options.params.paging = {
            start: options.params.start,
            limit: options.params.limit
        };
    },
    
    /**
     * use beforequery to set query filter
     * 
     * @param {Object} qevent
     */
    onBeforeQuery: function(qevent){
        this.store.baseParams.filter = [
            {field: 'customfield_id',   operator: 'equals',     value: this.customfieldId },
            {field: 'value',            operator: 'group',      value: '' },
            {field: 'value',            operator: 'startswith', value: qevent.query }
        ];
    }
});

Ext.reg('customfieldsearchcombo', Tine.widgets.customfields.CustomfieldSearchCombo);
