/*
 * Tine 2.0
 * 
 * @package     Crm
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
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
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Crm.LeadGridContactFilter
 */
Tine.Crm.LeadGridContactFilter = Ext.extend(Tine.widgets.grid.ForeignRecordFilter, {
    
    /**
     * @cfg {Record} foreignRecordClass needed for explicit defined filters
     */
    foreignRecordClass : Tine.Addressbook.Model.Contact,
    
    /**
     * @cfg {String} ownField for explicit filterRow
     */
    ownField: 'contact',
    
    /**
     * @private
     */
    initComponent: function() {
        this.app = Tine.Tinebase.appMgr.get('Crm');
        this.label = this.app.i18n._("Contact");
        
        Tine.Crm.LeadGridContactFilter.superclass.initComponent.call(this);
    },
    
    getSubFilters: function() {
        var contactRoleFilter = new Tine.widgets.grid.FilterModel({
            label: this.app.i18n._('CRM Role'),
            field: 'relation_type',
            operators: ['equals'],
            valueRenderer: function(filter, el) {
                var value = new Tine.Crm.Contact.TypeComboBox({
                    filter: filter,
                    blurOnSelect: true,
                    listAlign: 'tr-br',
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
        
        return [contactRoleFilter];
    }
});
Tine.widgets.grid.FilterToolbar.FILTERS['crm.contact'] = Tine.Crm.LeadGridContactFilter;
