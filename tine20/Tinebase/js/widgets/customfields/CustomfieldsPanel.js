/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.ns('Tine.Tinebase', 'Tine.Tinebase.widgets', 'Tine.Tinebase.widgets.customfields');

/**
 * Customfields Panel
 * 
 * @namespace   Tine.Tinebase.widgets.customfields
 * @class       Tine.Tinebase.widgets.customfields.CustomfieldsPanel
 * @extends     Ext.Panel
 * 
 * <p>Customfields Panel</p>
 * <p><pre>
 * TODO         remove 'quickHack': use onRecordLoad/Update or convert this to a plugin
 * </pre></p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Tinebase.widgets.customfields.CustomfieldsPanel
 */
Tine.Tinebase.widgets.customfields.CustomfieldsPanel = Ext.extend(Ext.Panel, {
    
    /**
     * @cfg {Tine.Tinebase.data.Record} recordClass
     * the recordClass this customfields panel is for
     */
    recordClass: null,
    
    /**
     * @property fieldset
     * @type Array of Ext.form.FieldSet
     */
    fieldset: null,
    
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
    
    /**
     * @private
     */
    initComponent: function() {
        this.title = _('Custom Fields');
        this.fieldset = [];
        
        var cfStore = this.getCustomFieldDefinition();
        var order = 1;
        if (cfStore) {
            this.items = [];
            this.getFieldSet(_('General'));
            cfStore.each(function(cfConfig) {
                var def = cfConfig.get('definition'),
                    uiConfig = def && def.uiconfig ? def.uiconfig : {},
                    fieldDef = {
                        fieldLabel: def.label,
                        name: 'customfield_' + cfConfig.get('name'),
                        xtype: (def.value_search == 1) ? 'customfieldsearchcombo' : uiConfig.xtype,
                        customfieldId: cfConfig.id,
                        anchor: '95%',
                        readOnly: cfConfig.get('account_grants').indexOf('writeGrant') < 0
                    };
                    
                    // auto xtype per data type
                    // @todo move this to a generic place
                    // @todo support array of scalars
                    // @todo suppot recordSets of model
                    if (! uiConfig.xtype && def.type) {
                        switch (Ext.util.Format.lowercase(def.type)) {
                            case 'keyfield':
                                var options = def.options ? def.options : {},
                                    keyFieldConfig = def.keyFieldConfig ? def.keyFieldConfig : null;
                                    
                                Ext.apply(fieldDef, {
                                    xtype: 'widget-keyfieldcombo',
                                    app: options.app ? options.app : this.recordClass.getMeta('appName'),
                                    keyFieldName: options.keyFieldName ? options.keyFieldName : cfConfig.get('name')
                                });
                                
                                if (keyFieldConfig) {
                                    // place keyFieldConfig in registry so we can use the standard widgets
                                    var app = Ext.isString(fieldDef.app) ? Tine.Tinebase.appMgr.get(fieldDef.app) : fieldDef.app;
                                    app.getRegistry().get('config')[fieldDef.keyFieldName] = keyFieldConfig;
                                }
                                
                                break;
                            case 'integer':
                            case 'int':
                                fieldDef.xtype = 'numberfield';
                                break;
                            case 'date':
                                fieldDef.xtype = 'datefield';
                                fieldDef.listAlign = 'tr-br?';
                                break;
                            case 'time':
                                fieldDef.xtype = 'timefield';
                                fieldDef.listAlign = 'tr-br?';
                                break;
                            case 'datetime':
                                fieldDef.xtype = 'datetimefield';
                                fieldDef.listAlign = 'tr-br?';
                                break;
                            case 'boolean':
                            case 'bool':
                                fieldDef.xtype = 'checkbox';
                                break;
                            case 'string':
                            default:
                                fieldDef.xtype = 'textfield';
                                break;
                        }
                    }
                    
                if (def.length) {
                    fieldDef.maxLength = def.length;
                }
                
                try {
                    var fieldObj = Ext.ComponentMgr.create(fieldDef);
                    order = (uiConfig.order) ? uiConfig.order : order++;
                    
                    if (! uiConfig.group || uiConfig.group == '') {
                        this.getFieldSet(_('General')).insert(order,fieldObj);
                    } else {
                        this.getFieldSet(uiConfig.group).insert(order,fieldObj);
                    }
                    
                    // ugh a bit ugly
                    cfConfig.fieldObj = fieldObj;
                } catch (e) {
                    Tine.log.debug(e);
                    Tine.log.err('Unable to create custom field "' + cfConfig.get('name') + '". Check definition!');
                    cfStore.remove(cfConfig);
                }
                
            }, this);
            
            this.formField = new Tine.Tinebase.widgets.customfields.CustomfieldsPanelFormField({
                cfStore: cfStore
            });
            
            this.items.push(this.formField);
            
        } else {
            this.html = '<div class="x-grid-empty">' + _('There are no custom fields yet') + "</div>";
        }
        
        Tine.Tinebase.widgets.customfields.CustomfieldsPanel.superclass.initComponent.call(this);
        
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
     * @param {String} name
     * @return {Ext.form.FieldSet}
     * @author Mihail Panayotov
     */
    getFieldSet: function(name) {
        var reg = /\s+/;
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
    
    /**
     * get cf definitions from registry
     * 
     * @return {Ext.data.JsonStore}
     */
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
    
    /**
     * set form field cf values
     * 
     * @param {Array} customfields
     */
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
Tine.Tinebase.widgets.customfields.CustomfieldsPanelFormField = Ext.extend(Ext.form.Field, {
    /**
     * @cfg {Ext.data.store} cfObject
     * Custom field Objects
     */
    cfStore: null,
    
    name: 'customfields',
    hidden: true,
    labelSeparator: '',
    
    /**
     * returns cf data of the current record
     */
    getValue: function() {
        var values = new Tine.Tinebase.widgets.customfields.Cftransport();
        this.cfStore.each(function(cfConfig) {
            values[cfConfig.get('name')] = cfConfig.fieldObj.getValue();
        }, this);
        
        return values;
    },
    
    /**
     * sets cfs from data
     */
    setValue: function(values){
        if (values) {
            this.cfStore.each(function(cfConfig) {
                var def = cfConfig.get('definition'),
                    uiconfig = def && def.uiconfig ? def.uiconfig : {};
                
                var value = values[cfConfig.get('name')];
                if (value) {
                    var datetimeTypes = ['date', 'datetime'];
                    if (datetimeTypes.indexOf(Ext.util.Format.lowercase(def.type)) != -1) {
                        value = Date.parseDate(value, Date.patterns.ISO8601Long);
                    }
                    cfConfig.fieldObj.setValue(value);
                }
            });
        }
    }

});

/**
 * @private helper class to workaround String Casts in record class
 * 
 * @class Tine.Tinebase.widgets.customfields.Cftransport
 * @extends Object
 */
Tine.Tinebase.widgets.customfields.Cftransport = Ext.extend(Object , {
    toString: function() {
        return Ext.util.JSON.encode(this);
    }
});
