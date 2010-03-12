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
    },
    
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
            listWidth: 200,
            id: 'tw-ftb-frow-valuefield-' + filter.id,
            value: filter.data.value ? filter.data.value : this.defaultValue,
            renderTo: el,
            allowNodeSelect: true,
            recordClass: this.recordClass,
            appName: this.recordClass.getMeta('appName'),
            containerName: this.containerName,
            containersName: this.containersName,
            getValue: function() {
                return this.selectedContainer ? this.selectedContainer.path : null;
            },
            setValue: function(value) {
                var operatorText = this.filter.data.operator === 'personalNode' ? _('is personal of') : _('is equal to');
                
                // use equals for node 'My containers'
                if (value.path && value.path === '/personal/' + Tine.Tinebase.registry.get('currentAccount').accountId) {
                    operatorText = _('is equal to')
                }
                
                this.filter.formFields.operator.setText(operatorText);
                return Tine.widgets.container.selectionComboBox.prototype.setValue.call(this, value);
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
