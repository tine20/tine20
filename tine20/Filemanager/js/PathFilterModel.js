/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2010-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */
Ext.ns('Tine.Filemanager');

/**
 * @namespace   Tine.widgets.container
 * @class       Tine.Filemanager.PathFilterModel
 * @extends     Tine.widgets.grid.FilterModel
 * 
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * 
 * @TODO make valueRenderer a path picker widget
 */
Tine.Filemanager.PathFilterModel = Ext.extend(Tine.widgets.grid.FilterModel, {
    /**
     * @cfg {Tine.Tinebase.Application} app
     */
    app: null,
    
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
    
    /**
     * @private
     */
    initComponent: function() {
        this.label = this.app.i18n._('path');
        
        Tine.Filemanager.PathFilterModel.superclass.initComponent.call(this);
    },
    
    /**
     * value renderer
     * 
     * @param {Ext.data.Record} filter line
     * @param {Ext.Element} element to render to 
     */
    valueRenderer: function(filter, el) {
        var setValue = function(value) {
            if (value && value.path) {
                value = value.path;
            }
            else if(Ext.isString(value) && (!value.charAt(0) || value.charAt(0) != '/')) {
                value = '/' + value;
            }

            return Ext.ux.form.ClearableTextField.prototype.setValue.call(this, value);
        };

        var value = new Ext.ux.form.ClearableTextField({
            filter: filter,
            renderTo: el,
            value: filter.data.value ? filter.data.value : this.defaultValue,
            emptyText: this.emptyText,
            setValue: setValue
        });
        
        value.on('specialkey', function(field, e){
            if(e.getKey() == e.ENTER){
                this.onFiltertrigger();
            }
        }, this);

        return value;
    }
});

Tine.widgets.grid.FilterToolbar.FILTERS['tine.filemanager.pathfiltermodel'] = Tine.Filemanager.PathFilterModel;

