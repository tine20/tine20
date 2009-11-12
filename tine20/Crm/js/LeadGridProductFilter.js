/*
 * Tine 2.0
 * 
 * @package     Crm
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:GridPanel.js 7170 2009-03-05 10:58:55Z p.schuele@metaways.de $
 *
 * TODO         make it work
 */
 
Ext.namespace('Tine.Crm');

/**
 * Lead grid product filter
 * 
 * @namespace   Tine.Crm
 * @class       Tine.Crm.LeadGridProductFilter
 * @extends     Tine.widgets.grid.FilterModel
 * 
 * <p>Product Filter for Lead Grid Panel</p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:GridPanel.js 7170 2009-03-05 10:58:55Z p.schuele@metaways.de $
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Crm.LeadGridProductFilter
 */
Tine.Crm.LeadGridProductFilter = Ext.extend(Tine.widgets.grid.FilterModel, {
    isForeignFilter: true,
    foreignField: 'id',
    ownField: 'product',
    //field: 'query',
    
    /**
     * @private
     */
    initComponent: function() {
        Tine.widgets.tags.TagFilter.superclass.initComponent.call(this);
        
        this.subFilterModels = [];
        
        this.app = Tine.Tinebase.appMgr.get('Crm');
        this.label = this.app.i18n._("Product"); // add 'Quick search' to label for main filter
        this.operators = ['contains'];
    },
    
    getSubFilters: function() {
        var filterConfigs = Tine.Sales.Model.Product.getFilterModel();
        
        Ext.each(filterConfigs, function(config) {
            //if (config.field != 'query') {
                this.subFilterModels.push(Tine.widgets.grid.FilterToolbar.prototype.createFilterModel.call(this, config));
            //}
        }, this);
        
        return this.subFilterModels;
    }
});
Tine.widgets.grid.FilterToolbar.FILTERS['crm.product'] = Tine.Crm.LeadGridProductFilter;
