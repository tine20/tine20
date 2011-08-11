/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */
Ext.ns('Tine.Filemanager');

/**
 * @namespace   Tine.widgets.container
 * @class       Tine.Filemanager.PathFilterModel
 * @extends     Tine.widgets.grid.FilterModel
 * 
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */
Tine.Filemanager.PathFilterModel = Ext.extend(Tine.widgets.container.FilterModel, {
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
    
    /**
     * @cfg {String} field path
     */
    field: 'path',
    
    /**
     * @cfg {String} defaultOperator default operator, one of <tt>{@link #operators} (defaults to equals)
     */
    defaultOperator: 'equals',
    
    /**
     * @cfg {String} defaultValue default value (defaults to all)
     */
    defaultValue: '/',
    
    
    treePanel: null,
    
    /**
     * @private
     */
    initComponent: function() {
        Tine.Filemanager.PathFilterModel.superclass.initComponent.call(this);
                
        this.label = this.app.i18n._('path');
    },
    
    /**
     * value renderer
     * 
     * @param {Ext.data.Record} filter line
     * @param {Ext.Element} element to render to 
     */
    valueRenderer: function(filter, el) {
        var value,
            fieldWidth = this.filterValueWidth,
            commonOptions = {
                filter: filter,
                width: fieldWidth,
                id: 'tw-ftb-frow-valuefield-' + filter.id,
                renderTo: el,
                value: filter.data.value ? filter.data.value : this.defaultValue
            };
        
        switch (this.valueType) {
            
            case 'string':
                value = new Ext.ux.form.ClearableTextField(Ext.apply(commonOptions, {
                    emptyText: this.emptyText,
                    listeners: {
                        scope: this,
                        specialkey: function(field, e){
                            if(e.getKey() == e.ENTER){
                                this.onFiltertrigger();
                            }
                        }
                    }
                }));
                var filterValue = filter.data.value;
                if(typeof filterValue == 'object') {
                    filterValue = filterValue.path;
                }
                else if(!filterValue.charAt(0) || filterValue.charAt(0) != '/') {
                    filterValue = '/' + filterValue;
                }
                value.setValue(filterValue);
                break;
                
            default:
                value = new Ext.ux.form.ClearableTextField(Ext.apply(commonOptions, {
                    emptyText: this.emptyText,
                    listeners: {
                        scope: this,
                        specialkey: function(field, e){
                            if(e.getKey() == e.ENTER){
                                this.onFiltertrigger();
                            }
                        }
                    }
                }));
                break;
        }
        
        return value;
    }


   
   
    
});

Tine.widgets.grid.FilterToolbar.FILTERS['tine.filemanager.pathfiltermodel'] = Tine.Filemanager.PathFilterModel;

