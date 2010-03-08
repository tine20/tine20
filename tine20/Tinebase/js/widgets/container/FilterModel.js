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
            id: 'tw-ftb-frow-valuefield-' + filter.id,
            value: filter.data.value ? filter.data.value : this.defaultValue,
            renderTo: el,
            appName: this.recordClass.getMeta('appName'),
            containerName: this.containerName,
            containersName: this.containersName,
            getValue: function() {
                return this.selectedContainer.path;
            },
            setValue: function(value) {
                if (Ext.isString(value)) {
                    var container = {id : value};
                    if (this.filter.data.operator == 'personalNode') {
                        if (value == Tine.Tinebase.registry.get('currentAccount').accountId) {
                            container.name = String.format(_('My {0}'), this.containersName);
                        }
                        // todo: resolve user at server time!
                        container.name = value;
                    } else if (this.filter.data.operator == 'specialNode') {
                        switch (value) {
                            case 'all':
                                container.name = String.format(_('All {0}'), this.containersName);
                                break;
                            case 'shared':
                                container.name = String.format(_('Shared {0}'), this.containersName);
                                break;
                            case 'otherUsers':
                                container.name = String.format(_('Other Users {0}'), this.containersName);
                                break;
                            case 'internal':
                                container.name = String.format(_('Internal {0}'), this.containerName);
                                break;
                        }
                    } else {
                        container = value;
                    }
                } else {
                    container = value;
                }
                
                return Tine.widgets.container.selectionComboBox.prototype.setValue.call(this, container);
                
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
