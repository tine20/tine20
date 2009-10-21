/*
 * Tine 2.0
 * search combo box and store
 * 
 * @package     form
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

Ext.namespace('Tine.Tinebase.widgets.form');

/**
 * record search/selection combo box
 * 
 * @namespace   Tine.Tinebase.widgets.form
 * @class       Tine.Tinebase.widgets.form.SearchCombo
 * @extends     Ext.form.ComboBox
 * 
 * <p>Search Combobox</p>
 * <p><pre></pre></p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Tinebase.widgets.form.SearchCombo
 */
Tine.Tinebase.widgets.form.SearchCombo = Ext.extend(Ext.form.ComboBox, {

    /**
     * combobox cfg
     * @private
     */
    typeAhead: false,
    triggerAction: 'all',
    pageSize: 10,
    itemSelector: 'div.search-item',
    store: null,
    minChars: 3,
    
    /**
     * @cfg {Boolean} blurOnSelect
     */
    blurOnSelect: false,
    
    /**
     * @property additionalFilters
     * @type Array
     */
    additionalFilters: null,
    
    /**
     * @property selectedRecord
     * @type Tine.Tinebase.data.Record
     */
    selectedRecord: null,
    
    /**
     * @property valueField
     * @type String
     */
    valueField: null,
    
    /**
     * @property recordFields
     * @type Array
     */
    recordFields: null,
    
    /**
     * @property searchMethod
     * @type String
     */
    searchMethod: null,
    
    /**
     * @private
     */
    initComponent: function(){
        
        this.loadingText = _('Searching...');
    	
        this.initTemplate();
        this.initStore();
        
        Tine.Tinebase.widgets.form.SearchCombo.superclass.initComponent.call(this);        

        this.on('beforequery', this.onBeforeQuery, this);
    },
    
    /**
     * use beforequery to set query filter
     * 
     * @param {Event} qevent
     */
    onBeforeQuery: function(qevent){
        var filter = [
            {field: 'query', operator: 'contains', value: qevent.query }
        ];
        
        if (this.additionalFilters !== null && this.additionalFilters.length > 0) {
            for (var i = 0; i < this.additionalFilters.length; i++) {
                filter.push(this.additionalFilters[i]);
            }
        }
        
        this.store.baseParams.filter = Ext.util.JSON.encode(filter);
    },
    
    /**
     * on select handler
     * - this needs to be overwritten in most cases
     * 
     * @param {Tine.Addressbook.Model.Contact} record
     */
    onSelect: function(record){
        this.selectedRecord = record;
        this.setValue(record.get(this.valueField));
        this.collapse();
        
        this.fireEvent('select', this, record);
        if (this.blurOnSelect) {
            this.fireEvent('blur', this);
        }
    },
    
    /**
     * on keypressed("enter") event to add record
     * 
     * @param {Tine.Addressbook.SearchCombo} combo
     * @param {Event} event
     */ 
    onSpecialkey: function(combo, event){
        if(event.getKey() == event.ENTER){
         	var id = combo.getValue();
            var record = this.store.getById(id);
            this.onSelect(record);
        }
    },
    
    /**
     * init template (overwrite this if you want a custom template)
     * @private
     */
    initTemplate: function() {
    },
    
    /**
     * get contact store
     *
     * @return Ext.data.JsonStore with contacts
     * @private
     */
    initStore: function() {
        
        if (! this.store) {
            
            // create store
            this.store = new Ext.data.JsonStore({
                fields: this.recordFields,
                baseParams: {
                    method: this.searchMethod
                },
                root: 'results',
                totalProperty: 'totalcount',
                id: 'id',
                remoteSort: true,
                sortInfo: {
                    field: this.valueField,
                    direction: 'ASC'
                }            
            });
    
            // prepare filter / get paging from combo
            this.store.on('beforeload', function(store, options){
                options.params.paging = Ext.util.JSON.encode({
                    start: options.params.start,
                    limit: options.params.limit,
                    sort: this.valueField,
                    dir: 'ASC'
                });
            }, this);
        }
        
        return this.store;
    }
});
