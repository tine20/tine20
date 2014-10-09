/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Michael Spahn <m.spahn@metaways.de>
 * @copyright   Copyright (c) 2014 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.Crm');

/**
 * @namespace   Tine.Crm
 * @class       Tine.Crm.LeadSourceFilterModel
 * @extends     Tine.widgets.grid.FilterModel
 */
Tine.Crm.LeadSourceFilterModel = Ext.extend(Tine.widgets.grid.FilterModel, {
    /**
     * @property Tine.Tinebase.Application app
     */
    app: null,
    
    field: 'leadsource_id',
    defaultOperator: 'in',
    
    /**
     * @private
     */
    initComponent: function() {
        this.label = this.app.i18n._('Leadsource');
        this.operators = ['in', 'notin'];

        this.supr().initComponent.call(this);
    },
    
    /**
     * value renderer
     * 
     * @param {Ext.data.Record} filter line
     * @param {Ext.Element} element to render to 
     */
    valueRenderer: function(filter, el) {
        var value = new Tine.Crm.LeadSourceFilterModelValueField({
            app: this.app,
            filter: filter,
            width: 200,
            id: 'tw-ftb-frow-valuefield-' + filter.id,
            value: filter.data.value ? filter.data.value : this.defaultValue,
            renderTo: el
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

Tine.widgets.grid.FilterToolbar.FILTERS['crm.leadsource'] = Tine.Crm.LeadSourceFilterModel;

/**
 * @namespace   Tine.Crm
 * @class       Tine.Crm.LeadSourceFilterModelValueField
 * @extends     Ext.ux.form.LayerCombo
 * 
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @author      Michael Spahn <m.spahn@metaways.de>
 */
Tine.Crm.LeadSourceFilterModelValueField = Ext.extend(Ext.ux.form.LayerCombo, {
    hideButtons: false,
    formConfig: {
        labelAlign: 'left',
        labelWidth: 30
    },
    
    getFormValue: function() {
        var ids = [];
        var statusStore = Tine.Crm.LeadSource.getStore();
        
        var formValues = this.getInnerForm().getForm().getValues();
        for (var id in formValues) {
            if (formValues[id] === 'on' && statusStore.getById(id)) {
                ids.push(id);
            }
        }
        
        return ids;
    },

    getItems: function() {
        var items = [];
        
        Tine.Crm.LeadSource.getStore().each(function(status) {
            items.push({
                xtype: 'checkbox',
                boxLabel: status.get('leadsource'),
                icon: status.get('status_icon'),
                name: status.get('id')
            });
        }, this);
        
        return items;
    },
    
    /**
     * @param {String} value
     * @return {Ext.form.Field} this
     */
    setValue: function(value) {
        value = Ext.isArray(value) ? value : [value];
        
        var statusStore = Tine.Crm.LeadSource.getStore();
        var statusText = [];
        this.currentValue = [];
        
        Tine.Crm.LeadSource.getStore().each(function(status) {
            var id = status.get('id');
            var name = status.get('leadsource');
            Ext.each(value, function(valueId) {
                // NOTE: no type match id's might be int or string and should match anyway!
                if (valueId == id) {
                    statusText.push(name);
                    this.currentValue.push(id);
                }
            }, this);
        }, this);
        
        this.setRawValue(statusText.join(', '));
        
        return this;
    },
    
    /**
     * sets values to innerForm
     */
    setFormValue: function(value) {
        this.getInnerForm().getForm().items.each(function(item) {
            item.setValue(value.indexOf(item.name) >= 0 ? 'on' : 'off');
        }, this);
    }
});
