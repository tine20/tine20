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
     * @cfg {Tine.Tinebase.data.Record} recordClass
     * the recordClass this customfields panel is for
     */
    recordClass: null,
    
    //private
    layout: 'form',
    border: true,
    frame: true,
    labelAlign: 'top',
    autoScroll: true,
    fieldset: null,
    defaults: {
        anchor: '100%',
        labelSeparator: ''
    },
    
    initComponent: function() {
        this.title = _('Custom Fields');
        this.fieldset = [];
        
        var cfStore = this.getCustomFieldDefinition();
        var order = 1;
        if (cfStore) {
            this.items = [];
            this.getFieldSet(_('General'));
            cfStore.each(function(def) {
                var fieldDef = {
                    fieldLabel: def.get('label'),
                    name: 'customfield_' + def.get('name'),
                    xtype: def.get('type'),
                    width: '90%'
                };
                
                try {
                    var fieldObj = Ext.ComponentMgr.create(fieldDef);
                    order = (def.get('order')) ? def.get('order') : order++;
                    
                    if (! def.get('group') || def.get('group') == '') {
                        this.getFieldSet(_('General')).insert(order,fieldObj);
                    } else {
                        this.getFieldSet(def.get('group')).insert(order,fieldObj);
                    }
                    
                    // ugh a bit ugly
                    def.fieldObj = fieldObj;
                } catch (e) {
                    //console.log(e);
                    console.error('unable to create custom field "' + def.get('name') + '". Check definition!');
                    cfStore.remove(def);
                }
                
            }, this);
            
            this.formField = new Tine.widgets.customfields.CustomfieldsPanelFormField({
                cfStore: cfStore
            });
            
            this.items.push(this.formField);
            
        } else {
            this.html = '<div class="x-grid-empty">' + _('There are no custom fields yet') + "</div>";
        }
        
        Tine.widgets.customfields.CustomfieldsPanel.superclass.initComponent.call(this);
        
        // added support for defered rendering as a quick hack: it would be better to 
        // let cfpanel be a plugin of editDialog
        this.on('render', function() {
            // fill data from record into form wich is not done due to defered rendering
            this.setAllCfValues(this.quickHack.record.get('customfields'));
        }, this);
        
    },

    /**
     * sort custom fields into groups
     * 
     * @param {} name
     * @return {}
     * @author Mihail Panayotov
     */
    getFieldSet: function(name) {
        reg = /\s+/;
        var system_name = name.replace(reg,'_');
        if (! this.fieldset[system_name]) {
            this.fieldset[system_name] = new Ext.form.FieldSet({
                title: name,
                autoHeight:true,
                autoWidth:true,
                labelAlign: 'top',
                labelWidth: '90%',
                collapsible:true,
                name:system_name,
                id: Ext.id() + system_name
            });
            this.items.push(this.fieldset[system_name]);
        }
        return this.fieldset[system_name];
    },    
    
    getCustomFieldDefinition: function() {
        var appName = this.recordClass.getMeta('appName');
        var modelName = this.recordClass.getMeta('modelName');
        if (Tine[appName].registry.containsKey('customfields')) {
            var allCfs = Tine[appName].registry.get('customfields');
            var cfStore = new Ext.data.JsonStore({
                fields: Tine.Tinebase.Model.Customfield,
                data: allCfs
            });
            
            cfStore.filter('model', appName + '_Model_' + modelName);
            
            if (cfStore.getCount() > 0) {
                return cfStore;
            }
        }
    },
    
    setAllCfValues: function(customfields) {
        // check if all cfs are already rendered
        var allRendered = false;
        this.items.each(function(item) {
            allRendered |= item.rendered;
        }, this);
        
        if (! allRendered) {
            this.setAllCfValues.defer(100, this, [customfields]);
        } else {
            this.formField.setValue(customfields);
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
        if (values) {
            this.cfStore.each(function(def) {
                def.fieldObj.setValue(values[def.get('name')]);
            });
        }
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
