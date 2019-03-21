/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

Ext.ns('Tine.Admin');

/**
 * @namespace   Tine.widgets.tags
 * @class       Tine.Admin.ApplicationFilter
 * @extends     Tine.widgets.grid.FilterModel
 */
Tine.Admin.ApplicationFilter = Ext.extend(Tine.widgets.grid.FilterModel, {
    /**
     * @cfg {Tine.Tinebase.Application} app
     */
    app: null,
    
    field: 'application_id',
    defaultOperator: 'equals',
    
    /**
     * @private
     */
    initComponent: function() {
        Tine.Admin.ApplicationFilter.superclass.initComponent.call(this);
        
        this.operators = ['equals', 'not'];
        this.label = this.app.i18n._('Application');
    },
    
    /**
     * value renderer
     * 
     * @param {Ext.data.Record} filter line
     * @param {Ext.Element} element to render to 
     */
    valueRenderer: function(filter, el) {
        this.appStore = new Ext.data.JsonStore({
            root: 'results',
            totalProperty: 'totalcount',
            fields: Tine.Admin.Model.Application
        });
        this.appStore.loadData({
            results:    Tine.Tinebase.registry.get('userApplications'),
            totalcount: Tine.Tinebase.registry.get('userApplications').length
        });
        
        var value = new Ext.form.ComboBox({
            app: this.app,
            name: 'application_id',
            store: this.appStore,
            displayField: 'name',
            valueField: 'id',
            filter: filter,
            value: filter.data.value ? filter.data.value : this.defaultValue,
            mode: 'local',
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
Tine.widgets.grid.FilterToolbar.FILTERS['admin.application'] = Tine.Admin.ApplicationFilter;

