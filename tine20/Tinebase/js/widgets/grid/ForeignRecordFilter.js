/*
 * Tine 2.0
 * 
 * @package     Crm
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:GridPanel.js 7170 2009-03-05 10:58:55Z p.schuele@metaways.de $
 *
 */
 
Ext.namespace('Tine.widgets.grid');

/**
 * Foreign Record Filter
 * 
 * @namespace   Tine.widgets.grid
 * @class       Tine.widgets.grid.ForeignRecordFilter
 * @extends     Tine.widgets.grid.FilterModel
 * 
 * <p>Filter for foreign records</p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:GridPanel.js 7170 2009-03-05 10:58:55Z p.schuele@metaways.de $
 * 
 * @param       {Object} config
 * @constructor
 */
Tine.widgets.grid.ForeignRecordFilter = Ext.extend(Tine.widgets.grid.FilterModel, {
    
    /**
     * @cfg {Application} app (required)
     */
    app: null,
    
    /**
     * @cfg {Record} foreignRecordClass (required)
     */
    foreignRecordClass : null,
    
    /**
     * @cfg {String} ownField (required)
     */
    ownField: null,
    
    
    isForeignFilter: true,
    
    /**
     * @private
     */
    initComponent: function() {
        this.foreignField = this.foreignRecordClass.getMeta('idProperty');
        
        this.label = Tine.Tinebase.appMgr.get(this.foreignRecordClass.getMeta('appName')).i18n.n_(
            this.foreignRecordClass.getMeta('recordName'), this.foreignRecordClass.getMeta('recordsName'), 1
        );
        
        this.subFilterModels = [];
        this.operators = ['equals'];
        
        Tine.widgets.grid.ForeignRecordFilter.superclass.initComponent.call(this);
    },
    
    getSubFilters: function() {
        var filterConfigs = this.foreignRecordClass.getFilterModel();
        
        Ext.each(filterConfigs, function(config) {
            this.subFilterModels.push(Tine.widgets.grid.FilterToolbar.prototype.createFilterModel.call(this, config));
        }, this);
        
        return this.subFilterModels;
    },
    
    /**
     * value renderer
     * 
     * @param {Ext.data.Record} filter line
     * @param {Ext.Element} element to render to 
     */
    valueRenderer: function(filter, el) {
        // value
        var value = new Tine.Tinebase.widgets.form.RecordPickerComboBox({
            recordClass: this.foreignRecordClass,
            filter: filter,
            blurOnSelect: true,
            width: 200,
            listWidth: 500,
            listAlign: 'tr-br',
            id: 'tw-ftb-frow-valuefield-' + filter.id,
            value: filter.data.value ? filter.data.value : this.defaultValue,
            renderTo: el
        });
        value.on('specialkey', function(field, e){
             if(e.getKey() == e.ENTER){
                 this.onFiltertrigger();
             }
        }, this);
        
        return value;
    }
});
Tine.widgets.grid.FilterToolbar.FILTERS['foreignrecord'] = Tine.widgets.grid.ForeignRecordFilter;
