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
 
Ext.namespace('Tine.Crm');

/**
 * Lead grid panel
 * 
 * @namespace   Tine.Crm
 * @class       Tine.Crm.LeadGridContactFilter
 * @extends     Tine.widgets.grid.FilterModel
 * 
 * <p>Contact Filter for Lead Grid Panel</p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:GridPanel.js 7170 2009-03-05 10:58:55Z p.schuele@metaways.de $
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Crm.LeadGridContactFilter
 */
Tine.Crm.LeadGridContactFilter = Ext.extend(Tine.widgets.grid.FilterModel, {
    isForeignFilter: true,
    foreignField: 'id',
    ownField: 'contact',
    
    /**
     * @private
     */
    initComponent: function() {
        Tine.widgets.tags.TagFilter.superclass.initComponent.call(this);
        
        this.subFilterModels = [];
        
        this.app = Tine.Tinebase.appMgr.get('Crm');
        this.label = this.app.i18n._("Contact");
        this.operators = ['equals'];
    },
    
    getSubFilters: function() {
        var filterConfigs = Tine.Addressbook.Model.Contact.getFilterModel();
        // TODO add some 'type' filter
        
        Ext.each(filterConfigs, function(config) {
            //if (config.field != 'query') {
                this.subFilterModels.push(Tine.widgets.grid.FilterToolbar.prototype.createFilterModel.call(this, config));
            //}
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
        var value = new Tine.Addressbook.SearchCombo({
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
Tine.widgets.grid.FilterToolbar.FILTERS['crm.contact'] = Tine.Crm.LeadGridContactFilter;
