/**
 * Tine 2.0
 * 
 * @package     Voipmanager
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.namespace('Tine.Voipmanager');

/**
 * Snom Location Edit Dialog
 */
Tine.Voipmanager.SnomLocationEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {

    /**
     * @private
     */
    windowNamePrefix: 'SnomLocationEditWindow_',
    appName: 'Voipmanager',
    recordClass: Tine.Voipmanager.Model.SnomLocation,
    recordProxy: Tine.Voipmanager.SnomLocationBackend,
    evalGrants: false,
    
    /**
     * returns dialog
     * 
     * NOTE: when this method gets called, all initalisation is done.
     */
    getFormItems: function() {
        return {
            layout: 'form',
            border: false,
            defaults: {
                anchor: '100%'
            },
            items:[{
                xtype: 'textfield',
                fieldLabel: this.app.i18n._('Name'),
                name: 'name',
                maxLength: 80,
                allowBlank: false
            }, {
                xtype: 'textarea',
                name: 'description',
                fieldLabel: this.app.i18n._('Description'),
                grow: false,
                preventScrollbars: false,
                height: 30
            }, {
                xtype: 'textfield',
                fieldLabel: this.app.i18n._('Registrar'),
                name: 'registrar',
                maxLength: 255,
                allowBlank: false
            }, {
                xtype: 'textfield',
                vtype: 'url',
                fieldLabel: this.app.i18n._('Base Download URL'),
                name: 'base_download_url',
                maxLength: 255,
                allowBlank: false
            }, {
                layout: 'column',
                border: false,
                anchor: '100%',
                items: [{
                    columnWidth: 0.5,
                    layout: 'form',
                    border: false,
                    anchor: '100%',
                    items: [{
                        xtype: 'combo',
                        fieldLabel: this.app.i18n._('Update Policy'),
                        name: 'update_policy',
                        mode: 'local',
                        displayField: 'policy',
                        valueField: 'key',
                        anchor: '98%',
                        triggerAction: 'all',
                        allowBlank: false,
                        editable: false,
                        store: new Ext.data.SimpleStore({
                            fields: ['key', 'policy'],
                            data: [['auto_update', 'auto update'], ['ask_for_update', 'ask for update'], ['never_update_firm', 'never update firm'], ['never_update_boot', 'never update boot'], ['settings_only', 'settings only'], ['never_update', 'never update']]
                        })
                    }]
                }, {
                    columnWidth: 0.5,
                    layout: 'form',
                    border: false,
                    anchor: '100%',
                    items: [{
                        xtype: 'numberfield',
                        fieldLabel: this.app.i18n._('Firmware Interval'),
                        name: 'firmware_interval',
                        maxLength: 11,
                        anchor: '100%',
                        allowBlank: false
                    }]
                }]
            }, {
               layout: 'column',
               border: false,
               anchor: '100%',
               items: [{
                   columnWidth: 0.33,
                   layout: 'form',
                   border: false,
                   anchor: '100%',
                   items: [{
                       xtype: 'combo',
                       fieldLabel: this.app.i18n._('tone_scheme'),
                       name: 'tone_scheme',
                       id: 'tone_scheme',
                       mode: 'local',
                       anchor: '98%',
                       triggerAction: 'all',
                       editable: false,
                       forceSelection: true,
                       value: 'GER',
                       store: [
                           ['AUS', Locale.getTranslationData('CountryList', 'AU')],
                           ['AUT', Locale.getTranslationData('CountryList', 'AT')],
                           ['CHN', Locale.getTranslationData('CountryList', 'CN')],
                           ['DNK', Locale.getTranslationData('CountryList', 'DK')],
                           ['FRA', Locale.getTranslationData('CountryList', 'FR')],
                           ['GER', Locale.getTranslationData('CountryList', 'DE')],
                           ['GBR', Locale.getTranslationData('CountryList', 'GB')],
                           ['IND', Locale.getTranslationData('CountryList', 'IN')],
                           ['ITA', Locale.getTranslationData('CountryList', 'IT')],
                           ['JPN', Locale.getTranslationData('CountryList', 'JP')],
                           ['MEX', Locale.getTranslationData('CountryList', 'MX')],
                           ['NLD', Locale.getTranslationData('CountryList', 'NL')],
                           ['NOR', Locale.getTranslationData('CountryList', 'NO')],
                           ['NZL', Locale.getTranslationData('CountryList', 'NZ')],
                           ['ESP', Locale.getTranslationData('CountryList', 'ES')],
                           ['SWE', Locale.getTranslationData('CountryList', 'SE')],
                           ['SWI', Locale.getTranslationData('CountryList', 'CH')],
                           ['USA', Locale.getTranslationData('CountryList', 'US')]
                       ]
                   }]
               }, {
                       columnWidth: 0.33,
                       layout: 'form',
                       border: false,
                       anchor: '100%',
                       items: [{
                           xtype: 'combo',
                           fieldLabel: this.app.i18n._('date_us_format'),
                           name: 'date_us_format',
                           id: 'date_us_format',
                           mode: 'local',
                           anchor: '98%',
                           triggerAction: 'all',
                           editable: false,
                           forceSelection: true,
                           value: '1',
                           store: [
                               ['1', this.app.i18n._('on')], 
                               ['0', this.app.i18n._('off')]
                           ]
                       }]
                   }, {
                       columnWidth: 0.33,
                       layout: 'form',
                       border: false,
                       anchor: '100%',
                       items: [{
                           xtype: 'combo',
                           fieldLabel: this.app.i18n._('time_24_format'),
                           name: 'time_24_format',
                           id: 'time_24_format',
                           mode: 'local',
                           anchor: '100%',
                           triggerAction: 'all',
                           editable: false,
                           forceSelection: true,
                           value: '1',
                           store: [
                               ['1', this.app.i18n._('on')], 
                               ['0', this.app.i18n._('off')]
                           ]
                       }]
                   }]
               },{
                xtype: 'fieldset',
                checkboxToggle: false,
                checkboxName: 'ntpSetting',
                id: 'ntp_setting',
                title: this.app.i18n._('NTP Server'),
                autoHeight: true,
                anchor: '100%',
                defaults: {
                    anchor: '100%'
                },
                items: [{
                    layout: 'column',
                    border: false,
                    anchor: '100%',
                    items: [{
                        columnWidth: 0.7,
                        layout: 'form',
                        border: false,
                        anchor: '100%',
                        items: [{
                            xtype: 'textfield',
                            fieldLabel: this.app.i18n._('NTP Server Address'),
                            name: 'ntp_server',
                            maxLength: 255,
                            anchor: '98%',
                            allowBlank: false
                        }]
                    }, {
                        columnWidth: 0.3,
                        layout: 'form',
                        border: false,
                        anchor: '100%',
                        items: [{
                            xtype: 'numberfield',
                            fieldLabel: this.app.i18n._('NTP Refresh'),
                            name: 'ntp_refresh',
                            maxLength: 20,
                            anchor: '100%'
                        }]
                    }]
                }, new Ext.form.ComboBox({
                    fieldLabel: this.app.i18n._('Timezone'),
                    id: 'timezone',
                    name: 'timezone',
                    mode: 'local',
                    displayField: 'timezone',
                    valueField: 'key',
                    anchor: '98%',
                    triggerAction: 'all',
                    allowBlank: false,
                    editable: false,
                    store: Tine.Voipmanager.Data.loadTimezoneData()
                })]
            }, {
                xtype: 'fieldset',
                checkboxToggle: true,
                checkboxName: 'admin_mode',
                id: 'admin_mode_switch',
                listeners: {
                    expand: function(){
                        Ext.getCmp('admin_mode').setValue('true');
                    },
                    collapse: function(){
                        Ext.getCmp('admin_mode').setValue('false');
                    }
                },
                title: this.app.i18n._('Enable admin mode'),
                autoHeight: true,
                anchor: '100%',
                defaults: {
                    anchor: '100%'
                },
                items: [{
                    xtype: 'hidden',
                    name: 'admin_mode',
                    id: 'admin_mode'
                }, {
                    xtype: 'numberfield',
                    fieldLabel: this.app.i18n._('Admin Mode Password'),
                    name: 'admin_mode_password',
                    /*inputType: 'password',*/
                    maxLength: 20,
                    anchor: '100%'
                }]
            }, {
                xtype: 'fieldset',
                checkboxToggle: true,
                checkboxName: 'enableWebserver',
                title: this.app.i18n._('Enable webserver'),
                autoHeight: true,
                id: 'enable_webserver_switch',
                listeners: {
                    collapse: function(){
                        Ext.getCmp('webserver_type').setValue('off');
                    },
                    expand: function(){
                        if (Ext.getCmp('webserver_type').getValue() == 'off') {
                            Ext.getCmp('webserver_type').setValue('http_https');
                        }
                    }
                },
                defaults: {
                    anchor: '100%'
                },
                items: [{
                    layout: 'column',
                    border: false,
                    anchor: '100%',
                    items: [{
                        columnWidth: 0.5,
                        layout: 'form',
                        border: false,
                        anchor: '100%',
                        items: [{
                            xtype: 'combo',
                            fieldLabel: this.app.i18n._('Webserver Type'),
                            name: 'webserver_type',
                            id: 'webserver_type',
                            mode: 'local',
                            displayField: 'wwwtype',
                            valueField: 'key',
                            listeners: {
                                select: function(_field, _newValue, _oldValue){
                                    if (_newValue.data.key == 'https') {
                                        Ext.getCmp('http_port').disable();
                                        Ext.getCmp('https_port').enable();
                                    }
                                    if (_newValue.data.key == 'http') {
                                        Ext.getCmp('http_port').enable();
                                        Ext.getCmp('https_port').disable();
                                    }
                                    if (_newValue.data.key == 'http_https') {
                                        Ext.getCmp('http_port').enable();
                                        Ext.getCmp('https_port').enable();
                                    }
                                }
                            },
                            anchor: '98%',
                            triggerAction: 'all',
                            allowBlank: false,
                            editable: false,
                            store: new Ext.data.SimpleStore({
                                fields: ['key', 'wwwtype'],
                                data: [['https', 'https'], ['http', 'http'], ['http_https', 'http https']]
                            })
                        }]
                    }, {
                        columnWidth: 0.5,
                        layout: 'form',
                        border: false,
                        anchor: '100%',
                        items: [{
                            layout: 'column',
                            border: false,
                            anchor: '100%',
                            items: [{
                                columnWidth: 0.5,
                                layout: 'form',
                                border: false,
                                anchor: '100%',
                                items: [{
                                    xtype: 'textfield',
                                    fieldLabel: this.app.i18n._('HTTP Port'),
                                    name: 'http_port',
                                    id: 'http_port',
                                    maxLength: 6,
                                    anchor: '98%',
                                    allowBlank: true
                                }]
                            }, {
                                columnWidth: 0.5,
                                layout: 'form',
                                border: false,
                                anchor: '100%',
                                items: [{
                                    xtype: 'textfield',
                                    fieldLabel: this.app.i18n._('HTTPS Port'),
                                    name: 'https_port',
                                    id: 'https_port',
                                    maxLength: 6,
                                    anchor: '100%',
                                    allowBlank: true
                                }]
                            }]
                        }]
                    }]
                }, {
                    layout: 'column',
                    border: false,
                    anchor: '100%',
                    items: [{
                        columnWidth: 0.5,
                        layout: 'form',
                        border: false,
                        anchor: '100%',
                        items: [{
                            xtype: 'textfield',
                            fieldLabel: this.app.i18n._('HTTP User'),
                            name: 'http_user',
                            maxLength: 20,
                            anchor: '98%'
                        }]
                    }, {
                        columnWidth: 0.5,
                        layout: 'form',
                        border: false,
                        anchor: '100%',
                        items: [{
                            xtype: 'textfield',
                            fieldLabel: this.app.i18n._('HTTP Password'),
                            name: 'http_pass',
                            inputType: 'textfield',
                            maxLength: 20,
                            anchor: '100%'
                        }]
                    }]
                }]
            }]
        };
    }
});

/**
 * Snom Location Edit Popup
 */
Tine.Voipmanager.SnomLocationEditDialog.openWindow = function (config) {
    var id = (config.record && config.record.id) ? config.record.id : 0;
    var window = Tine.WindowFactory.getWindow({
        width: 800,
        height: 470,
        name: Tine.Voipmanager.SnomLocationEditDialog.prototype.windowNamePrefix + id,
        contentPanelConstructor: 'Tine.Voipmanager.SnomLocationEditDialog',
        contentPanelConstructorConfig: config
    });
    return window;
};
