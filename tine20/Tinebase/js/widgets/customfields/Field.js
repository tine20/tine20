/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine.widgets.customfields');

/**
 * wrap for lazy cf init
 *
 * use like this:
 * {
 *    columnWidth: 0.5,
 *    xtype: 'customfield',
 *    modelName: 'Addressbook_Model_Contact',
 *    customFieldName: 'test'
 * }
 */
Tine.widgets.customfields.Field = Ext.extend(Ext.Panel, {
    layout: 'form',
    autoHeight: true,
    border: false,
    initComponent: function() {
        this.appName = this.appName ? this.appName : this.modelName.split('_')[0];

        var fieldConfig = Tine.widgets.customfields.ConfigManager.getConfig(this.appName, this.modelName, this.customFieldName),
            field = Tine.widgets.customfields.Field.get(this.appName, fieldConfig, {
                anchor: '100%',
                labelSeparator: ''
            });

        this.items = [field];
        Tine.widgets.customfields.Field.superclass.initComponent.call(this);
    }
});

    /**
     * get the form field 
     *
     * @static
     * @param {Tine.Tinebase.Application} app the application the customfield belongs to
     * @param {Tine.Tinebase.data.Record} cfConfig customfields config record
     * @param {Object} config additional field config
     * @param {Tine.widgets.dialog.EditDialog} editDialog the calling editdialog - just needed if record must not link itself
     * 
     * @return {Ext.form.Field}
     */
    Tine.widgets.customfields.Field.get = function (app, cfConfig, config, editDialog) {
        if (! cfConfig) {
            Tine.log.error('cfConfig empty -> skipping field');
            return Ext.ComponentMgr.create({xtype: 'hidden'});
        }
        
        var _ = window.lodash,
            def = cfConfig.get('definition'),
            uiConfig = def && def.uiconfig ? def.uiconfig : {},
            fieldDef = {
                fieldLabel: def.label,
                name: 'customfield_' + cfConfig.get('name'),
                xtype: (def.value_search == 1) ? 'customfieldsearchcombo' : uiConfig.xtype,
                customfieldId: cfConfig.id,
                readOnly: cfConfig.get('account_grants').indexOf('writeGrant') < 0,
                requiredGrant: 'editGrant'
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
                            keyFieldName: options.keyFieldName ? options.keyFieldName : cfConfig.get('name'),
                            sortBy: 'id'
                        });
                        break;
                    case 'record':
                        var options = def.options ? def.options : {},
                           recordConfig = def.recordConfig ? def.recordConfig : null;
                        if(!_.get(window, recordConfig.value.records)) return Ext.ComponentMgr.create({xtype: 'hidden'});
                        Ext.apply(fieldDef, {
                            xtype: 'tinerecordpickercombobox',
                            app: options.app ? options.app : app,
                            resizable: true,
                            recordClass: eval(recordConfig.value.records),
                            allowLinkingItself: false,
                            editDialog: editDialog,
                            additionalFilterSpec: recordConfig.additionalFilterSpec
                        });
                        break;
                    case 'recordlist':
                        var options = def.options ? def.options : {},
                            recordListConfig = def.recordListConfig ? def.recordListConfig : null;

                        if(!_.get(window, recordListConfig.value.records)) return Ext.ComponentMgr.create({xtype: 'hidden'});

                        Ext.apply(fieldDef, {
                            xtype: 'tinerecordspickercombobox',
                            app: options.app ? options.app : app,
                            resizable: true,
                            recordClass: eval(recordListConfig.value.records),
                            allowLinkingItself: false,
                            editDialog: editDialog
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
                        fieldDef.hideLabel = true;
                        fieldDef.boxLabel = fieldDef.fieldLabel;
                        break;
                    case 'textarea':
                        fieldDef.xtype = 'textarea';
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
        
        if (def.required) {
            fieldDef.allowBlank = false;
        }

        // custom code overrides
        var overwritesKey = cfConfig.get('model').replace(/_/g, '.') + '.' + cfConfig.get('name');
        var overwrites = _.get(Tine, overwritesKey, {});

        try {
            fieldDef = _.assign(fieldDef, config, overwrites);
            return Ext.ComponentMgr.create(fieldDef);
        } catch (e) {
            Tine.log.debug(e);
            Tine.log.error('Unable to create custom field "' + cfConfig.get('name') + '". Check definition:');
            Tine.log.error(fieldDef);
            return Ext.ComponentMgr.create({xtype: 'hidden'});
        }
    };

Ext.reg('customfield', Tine.widgets.customfields.Field);
