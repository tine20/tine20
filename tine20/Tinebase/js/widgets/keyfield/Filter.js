/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.Tinebase.widgets.keyfield');

/**
 * @namespace   Tine.Tasks
 * @class       Tine.Tinebase.widgets.keyfield.Filter
 * @extends     Tine.widgets.grid.FilterModel
 * 
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */
Tine.Tinebase.widgets.keyfield.Filter = Ext.extend(Tine.widgets.grid.FilterModel, {
    /**
     * @property Tine.Tinebase.Application app
     */
    app: null,
    
    /**
     * @cfg
     * @type String
     */
    keyfieldName: null,
    
    defaultOperator: 'in',
    
    /**
     * @private
     */
    initComponent: function() {
        this.operators = ['in', 'notin'];
        
        Tine.Tinebase.widgets.keyfield.Filter.superclass.initComponent.call(this);
    },
    
    /**
     * value renderer
     * 
     * @param {Ext.data.Record} filter line
     * @param {Ext.Element} element to render to 
     */
    valueRenderer: function(filter, el) {
        var value = new Tine.Tinebase.widgets.keyfield.FilterValueField({
            app: this.app,
            filter: filter,
            width: 200,
            id: 'tw-ftb-frow-valuefield-' + filter.id,
            value: filter.data.value ? filter.data.value : this.defaultValue,
            renderTo: el,
            keyfieldName: this.keyfieldName
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

Tine.widgets.grid.FilterToolbar.FILTERS['tine.widget.keyfield.filter'] = Tine.Tinebase.widgets.keyfield.Filter;

/**
 * @namespace   Tine.Tasks
 * @class       Tine.Tinebase.widgets.keyfield.FilterValueField
 * @extends     Ext.ux.form.LayerCombo
 * 
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * 
 * TODO think about using Tine.widgets.grid.PickerFilterValueField
 */
Tine.Tinebase.widgets.keyfield.FilterValueField = Ext.extend(Ext.ux.form.LayerCombo, {
    hideButtons: false,
    formConfig: {
        labelAlign: 'left',
        labelWidth: 30
    },
    
    /**
     * @property Tine.Tinebase.Application app
     */
    app: null,
    
    /**
     * @cfg
     * @type String
     */
    keyfieldName: null,
    
    getFormValue: function() {
        var ids = [];
        var keyfieldStore = Tine.Tinebase.widgets.keyfield.StoreMgr.get(this.app.name, this.keyfieldName);
        
        var formValues = this.getInnerForm().getForm().getValues();
        for (var id in formValues) {
            if (formValues[id] === 'on' && keyfieldStore.getById(id)) {
                ids.push(id);
            }
        }
        
        return ids;
    },
    
    getItems: function() {
        var items = [];
        Tine.Tinebase.widgets.keyfield.StoreMgr.get(this.app.name, this.keyfieldName).each(function(keyfieldRecord) {
            var checkbox = {
                xtype: 'checkbox',
                boxLabel: keyfieldRecord.get('i18nValue'),
                icon: keyfieldRecord.get('icon'),
                name: keyfieldRecord.get('id')
            };
            
            if (checkbox.icon) {
                checkbox.boxLabel = '<img src="' + keyfieldRecord.get('icon') + '" class="tine-keyfield-icon"/>' + checkbox.boxLabel;
            }
            
            items.push(checkbox);
        }, this);
        
        return items;
    },
    
    /**
     * @param {String} value
     * @return {Ext.form.Field} this
     */
    setValue: function(value) {
        value = Ext.isArray(value) ? value : [value];
        
        var keyfieldRecordText = [];
        this.currentValue = [];
        
        Tine.Tinebase.widgets.keyfield.StoreMgr.get(this.app.name, this.keyfieldName).each(function(keyfieldRecord) {
            var id = keyfieldRecord.get('id');
            var name = keyfieldRecord.get('i18nValue');
            if (value.indexOf(id) >= 0) {
                keyfieldRecordText.push(name);
                this.currentValue.push(id);
            }
        }, this);
        
        this.setRawValue(keyfieldRecordText.join(', '));
        
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
