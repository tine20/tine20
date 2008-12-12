/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
 
Ext.ns('Tine.widgets', 'Tine.widgets.customfields');

/**
 * Customfields panel
 */
Tine.widgets.customfields.CustomfieldsPanel = Ext.extend(Ext.Panel, {
    
    /**
     * @cfg {Tine.Tinebase.Record} record
     * the record this customfields panel is for
     */
    record: null,
    
    //private
    layout: 'form',
    border: true,
    frame: true,
    labelAlign: 'top',
    autoScroll: true,
    defaults: {
        anchor: '100%',
        labelSeparator: ''
    },
    
    initComponent: function() {
        this.title = _('Custom Fields');
        
        var cfStore = this.getCustomFieldDefinition();
        if (cfStore) {
            this.items = [];
            cfStore.each(function(def) {
                var fieldDef = {
                    fieldLabel: def.get('label'),
                    name: 'customfield_' + def.get('name'),
                    xtype: def.get('type')
                };
                
                try {
                    var fieldObj = Ext.ComponentMgr.create(fieldDef);
                    this.items.push(fieldObj);
                    
                    // ugh a bit ugly
                    def.fieldObj = fieldObj;
                } catch (e) {
                    console.error('unable to create custom field "' + def.get('name') + '". Check definition!');
                    cfStore.remove(def);
                }
                
            }, this);
            this.items.push(new Tine.widgets.customfields.CustomfieldsPanelFormField({
                cfStore: cfStore
            }));
            
        } else {
            this.html = '<div class="x-grid-empty">' + _('There are no custom fields yet') + "</div>";
        }
        
        Tine.widgets.customfields.CustomfieldsPanel.superclass.initComponent.call(this);
    },
    
    getCustomFieldDefinition: function() {
        if (this.record && typeof(this.record.getTitle) == 'function') {
            var appName = this.record.appName;
            if (Tine[appName].registry.containsKey('customfields')) {
                var allCfs = Tine[appName].registry.get('customfields');
                var cfStore = new Ext.data.JsonStore({
                    fields: Tine.Tinebase.Model.Customfield,
                    data: allCfs
                });
                
                cfStore.filter('model', this.record.appName + '_Model_' + this.record.modelName);
                
                if (cfStore.getCount() > 0) {
                    return cfStore;
                }
            }
        } 
    }
});

/**
 * @private Helper class to have customfields processing in the standard form/record cycle
 */
Tine.widgets.customfields.CustomfieldsPanelFormField = Ext.extend(Ext.form.Field, {
    /**
     * @cfg {Ext.data.store} cfObject
     * Custom field Objects
     */
    cfStore: null,
    
    name: 'customfields',
    hidden: true,
    labelSeparator: '',
    /**
     * @private
     *
    initComponent: function() {
        Tine.widgets.customfields.CustomfieldsPanelFormField.superclass.initComponent.call(this);
        //this.hide();
    },*/
    
    /**
     * returns cf data of the current record
     */
    getValue: function() {
        var values = new Tine.widgets.customfields.Cftransport();
        this.cfStore.each(function(def) {
            values[def.get('name')] = def.fieldObj.getValue();
        }, this);
        
        return values;
    },
    
    /**
     * sets cfs from data
     */
    setValue: function(values){
        this.cfStore.each(function(def) {
            def.fieldObj.setValue(values[def.get('name')]);
        });
    }

});

/**
 * helper class to workaround String Casts in record class
 * 
 * @class Tine.widgets.customfields.Cftransport
 * @extends Object
 */
Tine.widgets.customfields.Cftransport = Ext.extend(Object , {
    toString: function() {
        return Ext.util.JSON.encode(this);
    }
});