/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine.widgets.customfields');

Tine.widgets.customfields.Field = {
//    this.cfConfig = Tine.widgets.customfields.ConfigManager.getConfig(this.app, this.model, this.name);
    get: function (app, cfConfig, config) {
        var def = cfConfig.get('definition'),
            uiConfig = def && def.uiconfig ? def.uiconfig : {},
            fieldDef = {
                fieldLabel: def.label,
                name: 'customfield_' + cfConfig.get('name'),
                xtype: (def.value_search == 1) ? 'customfieldsearchcombo' : uiConfig.xtype,
                customfieldId: cfConfig.id,
                readOnly: cfConfig.get('account_grants').indexOf('writeGrant') < 0
            };
            
            // auto xtype per data type
            // @todo support array of scalars
            // @todo suppot recordSets of model
            if (! uiConfig.xtype && def.type && ! def.value_search) {
                switch (Ext.util.Format.lowercase(def.type)) {
                    case 'keyfield':
                        var options = def.options ? def.options : {},
                            keyFieldConfig = def.keyFieldConfig ? def.keyFieldConfig : null;
                            
                        Ext.apply(fieldDef, {
                            xtype: 'widget-keyfieldcombo',
                            app: options.app ? options.app : app,
                            keyFieldName: options.keyFieldName ? options.keyFieldName : cfConfig.get('name')
                        });
                        break;
                   	case 'record':
                   		var options = def.options ? def.options : {},
                            recordConfig = def.recordConfig ? def.recordConfig : null;
                            
                        Ext.apply(fieldDef, {
                            xtype: 'tinerecordpickercombobox',
                            app: options.app ? options.app : app,
                            resizable: true,
                            recordClass: eval(recordConfig.value.records)
                        });
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
            var fieldObj = Ext.ComponentMgr.create(Ext.apply(fieldDef, config));
            return fieldObj;
        } catch (e) {
            Tine.log.debug(e);
            Tine.log.err('Unable to create custom field "' + cfConfig.get('name') + '". Check definition!');
        }
    }
};
