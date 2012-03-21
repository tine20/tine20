/**
 * Tine 2.0
 * 
 * @package     Voipmanager
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.namespace('Tine.Voipmanager');

/**
 * Snom Setting Edit Dialog
 */
Tine.Voipmanager.SnomSettingEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {

    
    /**
     * @private
     */
    windowNamePrefix: 'SnomSettingEditWindow_',
    appName: 'Voipmanager',
    recordClass: Tine.Voipmanager.Model.SnomSetting,
    recordProxy: Tine.Voipmanager.SnomSettingBackend,
    evalGrants: false,
    
    /**
     * 
     * @type array 
     */
    writableFields: [
        "web_language_w",
        "language_w",
        "display_method_w",
        "call_waiting_w",
        "mwi_notification_w",
        "mwi_dialtone_w",
        "headset_device_w",
        "message_led_other_w",
        "global_missed_counter_w",
        "scroll_outgoing_w",
        "show_local_line_w",
        "show_call_status_w"
    ],
    
    /**
     * record load
     */
    onRecordLoad: function() {
        // set lock combos
        Ext.each(this.writableFields, function(_item, _index, _array) {
            var field = _item;
            field = field.replace(/_w/, '');
            if (this.record.get(_item) == '0') {
               this.getForm().findField(field).onTrigger2Click();
            }
        }, this);
        
        Tine.Voipmanager.SnomPhoneEditDialog.superclass.onRecordLoad.call(this);
    },
    
    /**
     * record update
     */
    onRecordUpdate: function() {
        Tine.Voipmanager.SnomPhoneEditDialog.superclass.onRecordUpdate.call(this);
        
        Ext.each(this.writableFields, function(_item, _index, _array) {
            if (Ext.getCmp(_item)) {
                var value = Ext.getCmp(_item).getValue();
            }

            this.record.set(_item, value);
        }, this);
    },
    
    /**
     * returns dialog
     * 
     * NOTE: when this method gets called, all initalisation is done.
     */
    getFormItems: function() {
        return {
            xtype: 'tabpanel',
            border: false,
            plain:true,
            activeTab: 0,
            items:[{
                title: this.app.i18n._('Settings'),
                frame: true,
                border: false,
                xtype: 'columnform',
                formDefaults: {
                    columnWidth: 0.333,
                    labelSeparator: '',
                    xtype:'lockCombo',
                    mode: 'local',
                    displayField: 'name',
                    valueField: 'id',
                    anchor: '100%',
                    triggerAction: 'all',
                    editable: false,
                    forceSelection: true
                },
                items: [
                    [{
                        columnWidth: 0.35,
                        xtype: 'textfield',
                        fieldLabel: this.app.i18n._('name'),
                        name: 'name',
                        maxLength: 150,
                        allowBlank: false,
                        editable: true
                    }, {
                        columnWidth: 0.65,
                        xtype: 'textfield',
                        name: 'description',
                        fieldLabel: this.app.i18n._('Description'),
                        maxLength: 255,
                        editable: true
                    }], [{
                        fieldLabel: this.app.i18n._('web_language'),
                        name: 'web_language',
                        hiddenFieldId: 'web_language_w',
                        store: new Ext.data.SimpleStore({
                            id: 'id',
                            fields: ['id', 'name'],
                            data: [
                                [ null,  this.app.i18n._('- factory default -')],                                        
                                ['English', Locale.getTranslationData('Language', 'en')],
                                ['Deutsch', Locale.getTranslationData('Language', 'de')],
                                ['Espanol', Locale.getTranslationData('Language', 'es')],
                                ['Francais', Locale.getTranslationData('Language', 'fr')],
                                ['Italiano', Locale.getTranslationData('Language', 'it')],
                                ['Nederlands', Locale.getTranslationData('Language', 'nl')],
                                ['Portugues', Locale.getTranslationData('Language', 'pt')],
                                ['Suomi', Locale.getTranslationData('Language', 'fi')],
                                ['Svenska', Locale.getTranslationData('Language', 'sv')],
                                ['Dansk', Locale.getTranslationData('Language', 'da')],
                                ['Norsk', Locale.getTranslationData('Language', 'no')]
                            ]
                        })
                    }, {
                        fieldLabel: this.app.i18n._('language'),
                        name: 'language',
                        hiddenFieldId: 'language_w',
                        store: new Ext.data.SimpleStore({
                            id: 'id',
                            fields: ['id', 'name'],
                            data: [
                                [ null,  this.app.i18n._('- factory default -')],                                                                                               
                                ['English', Locale.getTranslationData('Language', 'en')],
                                ['English(UK)', Locale.getTranslationData('Language', 'en_GB')],
                                ['Deutsch', Locale.getTranslationData('Language', 'de')],
                                ['Espanol', Locale.getTranslationData('Language', 'es')],
                                ['Francais', Locale.getTranslationData('Language', 'fr')],
                                ['Italiano', Locale.getTranslationData('Language', 'it')],
                                ['Cestina', Locale.getTranslationData('Language', 'cs')],
                                ['Nederlands', Locale.getTranslationData('Language', 'nl')],
                                ['Polski', Locale.getTranslationData('Language', 'pl')],
                                ['Portugues', Locale.getTranslationData('Language', 'pt')],
                                ['Slovencina', Locale.getTranslationData('Language', 'sl')],
                                ['Suomi', Locale.getTranslationData('Language', 'fi')],
                                ['Svenska', Locale.getTranslationData('Language', 'sv')],
                                ['Dansk', Locale.getTranslationData('Language', 'da')],
                                ['Norsk', Locale.getTranslationData('Language', 'no')],                                            
                                ['Japanese', Locale.getTranslationData('Language', 'ja')],
                                ['Chinese', Locale.getTranslationData('Language', 'zh')]
                            ]
                        })
                    }, {
                        fieldLabel: this.app.i18n._('display_method'),
                        name: 'display_method',
                        hiddenFieldId: 'display_method_w',
                        store: new Ext.data.SimpleStore({
                            id: 'id',
                            fields: ['id', 'name'],
                            data: [
                                [ null,  this.app.i18n._('- factory default -')],                                                                                
                                ['full_contact', this.app.i18n._('whole url')],
                                ['display_name', this.app.i18n._('name')],
                                ['display_number', this.app.i18n._('number')],
                                ['display_name_number', this.app.i18n._('name + number')],
                                ['display_number_name', this.app.i18n._('number + name')]
                            ]
                        })
                    }], [{
                        fieldLabel: this.app.i18n._('call_waiting'),
                        name: 'call_waiting',
                        hiddenFieldId: 'call_waiting_w',
                        store: new Ext.data.SimpleStore({
                            id: 'id',
                            fields: ['id', 'name'],
                            data: [
                                [ null,  this.app.i18n._('- factory default -')],                                        
                                ['on', this.app.i18n._('on')],
                                ['visual', this.app.i18n._('visual')],
                                ['ringer', this.app.i18n._('ringer')],
                                ['off', this.app.i18n._('off')]
                            ]
                        })
                    }, {
                        fieldLabel: this.app.i18n._('mwi_notification'),
                        name: 'mwi_notification',
                        hiddenFieldId: 'mwi_notification_w',
                        store: new Ext.data.SimpleStore({
                            id: 'id',
                            fields: ['id', 'name'],
                            data: [
                                [ null,  this.app.i18n._('- factory default -')],                                        
                                ['silent', this.app.i18n._('silent')],
                                ['beep', this.app.i18n._('beep')],
                                ['reminder', this.app.i18n._('reminder')]
                            ]
                        })
                    }, {
                        fieldLabel: this.app.i18n._('mwi_dialtone'),
                        name: 'mwi_dialtone',
                        hiddenFieldId: 'mwi_dialtone_w',
                        store: new Ext.data.SimpleStore({
                            id: 'id',
                            fields: ['id', 'name'],
                            data: [
                                [ null,  this.app.i18n._('- factory default -')],                                        
                                ['normal', this.app.i18n._('normal')],
                                ['stutter', this.app.i18n._('stutter')]
                            ]
                        })
                    }], [{
                        fieldLabel: this.app.i18n._('headset_device'),
                        name: 'headset_device',
                        hiddenFieldId: 'headset_device_w',
                        store: new Ext.data.SimpleStore({
                            id: 'id',
                            fields: ['id', 'name'],
                            data: [
                                [ null,  this.app.i18n._('- factory default -')],                                        
                                ['none', this.app.i18n._('none')],
                                ['headset_rj', this.app.i18n._('headset_rj')]
                            ]
                        })
                    }, {
                        fieldLabel: this.app.i18n._('message_led_other'),
                        name: 'message_led_other',
                        hiddenFieldId: 'message_led_other_w',
                        store: new Ext.data.SimpleStore({
                            id: 'id',
                            fields: ['id', 'name'],
                            data: [
                                [ null,  this.app.i18n._('- factory default -')],
                                ['1', this.app.i18n._('on')],
                                ['0', this.app.i18n._('off')]
                            ]
                        })
                    }, {
                        fieldLabel: this.app.i18n._('global_missed_counter'),
                        name: 'global_missed_counter',
                        hiddenFieldId: 'global_missed_counter_w',
                        store: new Ext.data.SimpleStore({
                            id: 'id',
                            fields: ['id', 'name'],
                            data: [
                                [ null,  this.app.i18n._('- factory default -')],
                                ['1', this.app.i18n._('on')], 
                                ['0', this.app.i18n._('off')]
                            ]
                        })
                    }], [{
                        fieldLabel: this.app.i18n._('scroll_outgoing'),
                        name: 'scroll_outgoing',
                        hiddenFieldId: 'scroll_outgoing_w',
                        store: new Ext.data.SimpleStore({
                            id: 'id',
                            fields: ['id', 'name'],
                            data: [
                                [ null,  this.app.i18n._('- factory default -')],
                                ['1', this.app.i18n._('on')],
                                ['0', this.app.i18n._('off')]
                            ]
                        })
                    }, {
                        fieldLabel: this.app.i18n._('show_local_line'),
                        name: 'show_local_line',
                        hiddenFieldId: 'show_local_line_w',
                        store: new Ext.data.SimpleStore({
                            id: 'id',
                            fields: ['id', 'name'],
                            data: [
                                [ null,  this.app.i18n._('- factory default -')],
                                ['1', this.app.i18n._('on')],
                                ['0', this.app.i18n._('off')]
                            ]
                        })
                    }, {
                        fieldLabel: this.app.i18n._('show_call_status'),
                        name: 'show_call_status',
                        hiddenFieldId: 'show_call_status_w',
                        store: new Ext.data.SimpleStore({
                            id: 'id',
                            fields: ['id', 'name'],
                            data: [
                                [ null,  this.app.i18n._('- factory default -')],
                                ['1', this.app.i18n._('on')],
                                ['0', this.app.i18n._('off')]
                            ]
                        })
                    }]
                ]
        }]};
    }
});

/**
 * Snom Setting Edit Popup
 */
Tine.Voipmanager.SnomSettingEditDialog.openWindow = function (config) {
    var id = (config.record && config.record.id) ? config.record.id : 0;
    var window = Tine.WindowFactory.getWindow({
        width: 800,
        height: 470,
        name: Tine.Voipmanager.SnomSettingEditDialog.prototype.windowNamePrefix + id,
        contentPanelConstructor: 'Tine.Voipmanager.SnomSettingEditDialog',
        contentPanelConstructorConfig: config
    });
    return window;
};
