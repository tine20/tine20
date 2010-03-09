/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */
Ext.ns('Tine.widgets.container');

/**
 * @namespace   Tine.widgets.container
 * @class       Tine.widgets.container.FilterModel
 * @extends     Tine.widgets.grid.FilterModel
 * 
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 */
Tine.widgets.container.FilterModel = Ext.extend(Tine.widgets.grid.FilterModel, {
    /**
     * @cfg {Tine.Tinebase.Application} app
     */
    app: null,
    
    /**
     * @cfg {Tine.Tinebase.data.Record} recordClass
     * record definition class  (required)
     */
    recordClass: null,
    
    /**
     * @cfg {Array} operators allowed operators
     */
    operators: ['equals'],
    //operators: ['personalNode', 'specialNode', 'equals', 'in'],
    
    /**
     * @cfg {String} field container field (defaults to container_id)
     */
    field: 'container_id',
    
    /**
     * @cfg {String} defaultOperator default operator, one of <tt>{@link #operators} (defaults to equals)
     */
    defaultOperator: 'equals',
    
    /**
     * @cfg {String} defaultValue default value (defaults to all)
     */
    defaultValue: 'all',
    
    /**
     * @private
     */
    initComponent: function() {
        Tine.widgets.tags.TagFilter.superclass.initComponent.call(this);
        
        this.containerName = this.app.i18n.n_hidden(this.recordClass.getMeta('containerName'), this.recordClass.getMeta('containersName'), 1);
        this.containersName = this.app.i18n._hidden(this.recordClass.getMeta('containersName'));
        
        this.label = this.containerName;
        
        /*
        // define custom operators
        this.customOperators = [
            {operator: 'specialNode',label: _('sub of')},
            {operator: 'personalNode',label: _('personal of')}
        ];
        */
        
        // legacy handling for tree panel filter plugin:
        // - atm. the TreeFilterPlugin is initialised as GridPlugin by the mainscreen
        //   we grep this plugin and rewrite it to work as plugin for this filterModel
        //console.log(this.ftb.onBeforeLoad);
        
        //this.ftb.onBeforeLoad = this.ftb.onBeforeLoad.createInterceptor(this.onBeforeFtbLoad, this);
    },
    
    /**
     * legacy handling for tree panel filter plugin
     *
    onBeforeFtbLoad: function(store, options) {
        options = options || {};
        options.params = options.params || {};
        options.params.filter = options.params.filter ? options.params.filter : [];
        
        // check if alrady a filter for "our" field is registered and remove it
        Ext.each(options.params.filter, function(filter) {
            if (filter.operator === this.field) {
                options.params.filter.remove(filter);
                return false;
            }
        }, this);
    },
    */
    
    /**
     * value renderer
     * 
     * @param {Ext.data.Record} filter line
     * @param {Ext.Element} element to render to 
     */
    valueRenderer: function(filter, el) {
        var defaultValue = this.defaultValue;
        
        var value = new Tine.widgets.container.selectionComboBox({
            app: this.app,
            filter: filter,
            width: 200,
            id: 'tw-ftb-frow-valuefield-' + filter.id,
            value: filter.data.value ? filter.data.value : this.defaultValue,
            renderTo: el,
            allowNodeSelect: true,
            appName: this.recordClass.getMeta('appName'),
            containerName: this.containerName,
            containersName: this.containersName,
            getValue: function() {
                return this.selectedContainer ? this.selectedContainer.path : null;
            }
        });
        value.on('specialkey', function(field, e){
             if(e.getKey() == e.ENTER){
                 this.onFiltertrigger();
             }
        }, this);
        value.on('select', this.onFiltertrigger, this);
        
        return value;
    }
});

Tine.widgets.grid.FilterToolbar.FILTERS['tine.widget.container.filtermodel'] = Tine.widgets.container.FilterModel;
