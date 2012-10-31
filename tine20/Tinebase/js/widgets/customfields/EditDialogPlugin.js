/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine.widgets.customfields');

Tine.widgets.customfields.EditDialogPlugin = function (config) {
    Ext.apply(this, config);
};

Tine.widgets.customfields.EditDialogPlugin.prototype = {
    /**
     * @type Tine.Tinebase.Application app
     */
    app: null,
    
    /**
     * @type Tine.widgets.dialog.EditDialog editDialog
     */
    editDialog: null,
    
    init: function(editDialog) {
        this.editDialog = editDialog;
        
        // edit dialog without recordClass cannot have custom fields
        if(!this.editDialog.recordClass) {
            return;
        }
        
        this.app = Tine.Tinebase.appMgr.get(this.editDialog.app);
        this.customfieldsValue = [];
        
        // compute cf's for cf tab and add a cf tab on demand
        this.editDialog.on('beforerender', this.onBeforeRender, this);
        
        // fill/buffer all cf's with values
        this.editDialog.on('load', this.onRecordLoad, this);
        
        // get all cf values
        this.editDialog.onRecordUpdate = this.editDialog.onRecordUpdate.createSequence(this.onRecordUpdate, this);
    },
    
    /**
     * dispatch values from customfield property
     */
    onRecordLoad: function() {
        var form = this.editDialog.getForm(),
            modelName = this.editDialog.recordClass.getMeta('appName') + '_Model_' + this.editDialog.recordClass.getMeta('modelName'),
            name,
            field,
            cfConfig;

        this.customfieldsValue = this.editDialog.record.get('customfields') || {};
        
        for (name in this.customfieldsValue) {
            field = form.findField('customfield_' + name);
            cfConfig = Tine.widgets.customfields.ConfigManager.getConfig(this.app, modelName, name);
            
            if (cfConfig) {
                // transform datetime values
                if (['date', 'datetime'].indexOf(Ext.util.Format.lowercase(cfConfig.get('definition').type)) != -1) {
                    this.customfieldsValue[name] = Date.parseDate(this.customfieldsValue[name], Date.patterns.ISO8601Long);
                }
                
                if (field) {
                    if(field.isXType('combo') && Ext.isObject(this.customfieldsValue[name])) {
                        var phpClassName = cfConfig.get('model').split('_'),
                            recordClass = Tine[phpClassName[0]].Model[phpClassName[2]],
                            record = new recordClass(this.customfieldsValue[name]);
                            
                        field.setValue(record.getId());
                        field.selectedRecord = record.data; 
                    } else {
                        field.setValue(this.customfieldsValue[name]);
                    }
                }
            }
        }
    },
    
    /**
     * combile cf values in customfield property
     */
    onRecordUpdate: function() {
        var form = this.editDialog.getForm();
        
        form.items.each(function(f) {
            var name = f.getName();
            
            if (name.match(/^customfield_(.+)$/)) {
                name = name.match(/^customfield_(.+)$/)[1];
                
                this.customfieldsValue[name] = f.getValue();
            }
        }, this);
        
        this.customfieldsValue.toString = function() {
            return Ext.util.JSON.encode(this.customfieldsValue);
        }
        
        this.editDialog.record.set('customfields', this.customfieldsValue);
    },
    
    /**
     * create cf tab on demand
     */
    onBeforeRender: function() {
        var modelName = this.editDialog.recordClass.getMeta('appName') + '_Model_' + this.editDialog.recordClass.getMeta('modelName'),
            allCfConfigs = Tine.widgets.customfields.ConfigManager.getConfigs(this.app, modelName),
            form = this.editDialog.getForm(),
            cfConfigs = new Ext.util.MixedCollection();
        
        // remove already applied cfs / fill the mixed collection
        Ext.each(allCfConfigs, function(cfConfig) {
            if (! form.findField('customfield_' + cfConfig.get('name'))) {
                cfConfigs.add(cfConfig.id, cfConfig);
            }
        }, this);
        
        // auto add cf tab
        if (cfConfigs.getCount()) {
            this.addCFTab(cfConfigs);
        }
    },
    
    /**
     * create a cf tab
     * 
     * @param {Ext.util.MixedCollection} cfConfigs
     */
    addCFTab: function(cfConfigs) {
        this.cfTabItems = [];
        this.fieldsets = {};
        
        cfConfigs.sort('ASC', function(r1, r2) {
            var o1 = (r1.get('definition').uiconfig && r1.get('definition').uiconfig.order) ? r1.get('definition').uiconfig.order : 0,
                o2 = (r2.get('definition').uiconfig && r2.get('definition').uiconfig.order) ? r2.get('definition').uiconfig.order : 0;
            return o1 > o2 ? 1 : 0;
        });

        var definition = null,
            fieldObj = null,
            uiConfig = null,
            group = null;
        
        cfConfigs.each(function(cfConfig) {
            try {
                definition = cfConfig.get('definition');
                fieldObj = Tine.widgets.customfields.Field.get(this.app, cfConfig, {anchor: '95%'}, this.editDialog);
                uiConfig = definition.uiconfig ? definition.uiconfig : {};
                group = uiConfig.group ? uiConfig.group : _('General');
                
                Tine.log.debug('Tine.widgets.customfields.EditDialogPlugin::addCFTab() - Adding ' + cfConfig.get('name') + ' to group ' + group);
                this.getFieldSet(group).add(fieldObj);
                
            } catch (e) {
                Tine.log.debug(e);
                Tine.log.err('Unable to create custom field "' + cfConfig.get('name') + '". Check definition!');
            }
        }, this);
        
        this.cfTab  = new Ext.Panel({
            title: _('Custom Fields'),
            layout: 'form',
            border: true,
            frame: true,
            labelAlign: 'top',
            autoScroll: true,
            items: this.cfTabItems,
            defaults: {
                anchor: '100%',
                labelSeparator: ''
            }
        });
        
        // find the first tabPanel and add it there
        var tabPanel = this.editDialog.items.find(function(item) {
            return Ext.isObject(item) && Ext.isFunction(item.getXType) && item.getXType() == 'tabpanel';
        });
        
        if (tabPanel) {
            tabPanel.add(this.cfTab);
        }
    },
    
    /**
     * sort custom fields into groups
     * 
     * @param {String} name
     * @return {Ext.form.FieldSet}
     */
    getFieldSet: function(name) {
        var reg = /\s+/;
        var system_name = name.replace(reg,'_');
        if (! this.fieldsets[system_name]) {
            this.fieldsets[system_name] = new Ext.form.FieldSet({
                title: name,
                autoHeight:true,
                autoWidth:true,
                labelAlign: 'top',
                labelWidth: '90%',
                collapsible:true,
                name:system_name,
                id: Ext.id() + system_name
            });
            this.cfTabItems.push(this.fieldsets[system_name]);
        }
        return this.fieldsets[system_name];
    }
};

Ext.preg('tinebase.widgets.customfield.editdialogplugin', Tine.widgets.customfields.EditDialogPlugin)