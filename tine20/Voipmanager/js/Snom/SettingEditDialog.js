/**
 * Tine 2.0
 * 
 * @package     Voipmanager
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 * @todo        make it more beautiful
 * @todo        add hiddenFieldData again
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
    loadRecord: false,
    tbarItems: [{xtype: 'widget-activitiesaddbutton'}],
    evalGrants: false,
    
    /**
     * overwrite update toolbars function (we don't have record grants yet)
     */
    updateToolbars: function(record) {
    	Tine.Voipmanager.SnomSettingEditDialog.superclass.updateToolbars.call(this, record, 'id');
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
                    layout: 'hfit',
                    containsScrollbar: false,
                    //margins: '0 18 0 5',
                    autoScroll: false,
                    id: 'editSettingMainDialog',
                    region: 'center',
                    items: [{
                        layout: 'column',
                        border: false,
                        anchor: '100%',
                        height: 50,
                        items: [{
                            columnWidth: 0.35,
                            layout: 'form',
                            border: false,
                            anchor: '100%',
                            items: [{
                                xtype: 'textfield',
                                fieldLabel: this.app.i18n._('name'),
                                name: 'name',
                                maxLength: 150,
                                anchor: '98%',
                                allowBlank: false
                            }]
                        }, {
                            columnWidth: 0.65,
                            layout: 'form',
                            border: false,
                            anchor: '98%',
                            autoHeight: true,
                            items: [{
                                xtype: 'textfield',
                                name: 'description',
                                        tooltip: 'bla',                                
                                fieldLabel: this.app.i18n._('Description'),
                                maxLength: 255,
                                anchor: '100%'
                            }]
                        }]
                    }, {
                        layout: 'form',
                        border: false,
                        anchor: '100%',
                        items: [{
                            layout: 'column',
                            border: false,
                            anchor: '100%',
                            items: [{
                                columnWidth: 0.33,
                                layout: 'form',
                                border: false,
                                anchor: '100%',
                                items: [
									{
									xtype: 'lockCombo',
                                    fieldLabel: this.app.i18n._('web_language'),
                                    name: 'web_language',
                                    id: 'web_language',
									hiddenFieldId: 'web_language_writable',
									// @todo what about that?
									//hiddenFieldData: _settingData.web_language_writable,
                                    mode: 'local',
                                    displayField: 'name',
                                    valueField: 'id',
                                    anchor: '95%',
                                    triggerAction: 'all',
                                    editable: false,
                                    forceSelection: true,
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
                                }]
                            }, {
                                columnWidth: 0.33,
                                layout: 'form',
                                border: false,
                                anchor: '100%',
                                items: [{
                                    xtype: 'lockCombo',
                                    fieldLabel: this.app.i18n._('language'),
                                    name: 'language',
                                    id: 'language',
									hiddenFieldId: 'language_writable',
                                    // @todo what about that?
									//hiddenFieldData: _settingData.language_writable,                                    
                                    mode: 'local',
                                    displayField: 'name',
                                    valueField: 'id',
                                    anchor: '95%',
                                    triggerAction: 'all',
                                    editable: false,
                                    forceSelection: true,
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
                                }]
                            },{
                                columnWidth: 0.33,
                                layout: 'form',
                                border: false,
                                anchor: '100%',
                                items: [{
                                    xtype: 'lockCombo',
                                    fieldLabel: this.app.i18n._('display_method'),
                                    name: 'display_method',
                                    id: 'display_method',
									hiddenFieldId: 'display_method_writable',
									// @todo what about that?
									//hiddenFieldData: _settingData.display_method_writable,                                    
                                    mode: 'local',
                                    displayField: 'name',
                                    valueField: 'id',
                                    anchor: '100%',
                                    triggerAction: 'all',
                                    editable: false,
                                    forceSelection: true,
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
                                }]
                            }]
                        },{          
                            layout: 'column',
                            border: false,
                            anchor: '100%',
                            items: [{
                                columnWidth: 0.33,
                                layout: 'form',
                                border: false,
                                anchor: '100%',
                                items: [{
                                    xtype: 'lockCombo',
                                    fieldLabel: this.app.i18n._('call_waiting'),
                                    name: 'call_waiting',
                                    id: 'call_waiting',
									hiddenFieldId: 'call_waiting_writable',
									// @todo what about that?
									//hiddenFieldData: _settingData.call_waiting_writable,                                    
                                    mode: 'local',
                                    displayField: 'name',
                                    valueField: 'id',
                                    anchor: '95%',
                                    triggerAction: 'all',
                                    editable: false,
                                    forceSelection: true,
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
                                }]
                            }, {
                                columnWidth: 0.33,
                                layout: 'form',
                                border: false,
                                anchor: '100%',
                                items: [{
                                    xtype: 'lockCombo',
                                    fieldLabel: this.app.i18n._('mwi_notification'),
                                    name: 'mwi_notification',
                                    id: 'mwi_notification',
									hiddenFieldId: 'mwi_notification_writable',
									// @todo what about that?
									//hiddenFieldData: _settingData.mwi_notification_writable,                                    
                                    mode: 'local',
                                    displayField: 'name',
                                    valueField: 'id',
                                    anchor: '95%',
                                    triggerAction: 'all',
                                    editable: false,
                                    forceSelection: true,
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
                                }]
                            }, {
                                columnWidth: 0.33,
                                layout: 'form',
                                border: false,
                                anchor: '100%',
                                items: [{
                                    xtype: 'lockCombo',
                                    fieldLabel: this.app.i18n._('mwi_dialtone'),
                                    name: 'mwi_dialtone',
                                    id: 'mwi_dialtone',
									hiddenFieldId: 'mwi_dialtone_writable',
									// @todo what about that?
									//hiddenFieldData: _settingData.mwi_dialtone_writable,                                    
                                    mode: 'local',
                                    displayField: 'name',
                                    valueField: 'id',
                                    anchor: '100%',
                                    triggerAction: 'all',
                                    editable: false,
                                    forceSelection: true,
                                    store: new Ext.data.SimpleStore({
                                        id: 'id',
                                        fields: ['id', 'name'],
                                        data: [
                                            [ null,  this.app.i18n._('- factory default -')],                                        
                                            ['normal', this.app.i18n._('normal')],
                                            ['stutter', this.app.i18n._('stutter')]
                                        ]
                                    })
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
                                    xtype: 'lockCombo',
                                    fieldLabel: this.app.i18n._('headset_device'),
                                    name: 'headset_device',
                                    id: 'headset_device',
									hiddenFieldId: 'headset_device_writable',
									// @todo what about that?
									//hiddenFieldData: _settingData.headset_device_writable,                                    
                                    mode: 'local',
                                    displayField: 'name',
                                    valueField: 'id',
                                    anchor: '95%',
                                    triggerAction: 'all',
                                    editable: false,
                                    forceSelection: true,
                                    store: new Ext.data.SimpleStore({
                                        id: 'id',
                                        fields: ['id', 'name'],
                                        data: [
                                            [ null,  this.app.i18n._('- factory default -')],                                        
                                            ['none', this.app.i18n._('none')],
                                            ['headset_rj', this.app.i18n._('headset_rj')]
                                        ]
                                    })
                                }]
                            }, {
                                columnWidth: 0.33,
                                layout: 'form',
                                border: false,
                                anchor: '100%',
                                items: [{
                                        xtype: 'lockCombo',
                                        fieldLabel: this.app.i18n._('message_led_other'),
                                        name: 'message_led_other',
                                        id: 'message_led_other',
	    								hiddenFieldId: 'message_led_other_writable',
	    								// @todo what about that?
    									//hiddenFieldData: _settingData.message_led_other_writable,                                        
                                        mode: 'local',
                                        displayField: 'name',
                                        valueField: 'id',
                                        anchor: '95%',
                                        triggerAction: 'all',
                                        editable: false,
                                        forceSelection: true,
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
                            }, {
                                columnWidth: 0.33,
                                layout: 'form',
                                border: false,
                                anchor: '100%',
                                items: [{
                                    xtype: 'lockCombo',
                                    fieldLabel: this.app.i18n._('global_missed_counter'),
                                    name: 'global_missed_counter',
                                    id: 'global_missed_counter',
									hiddenFieldId: 'global_missed_counter_writable',
									// @todo what about that?
									//hiddenFieldData: _settingData.global_missed_counter_writable,                                    
                                    mode: 'local',
                                    displayField: 'name',
                                    valueField: 'id',
                                    anchor: '100%',
                                    triggerAction: 'all',
                                    editable: false,
                                    forceSelection: true,
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
                                    xtype: 'lockCombo',
                                    fieldLabel: this.app.i18n._('scroll_outgoing'),
                                    name: 'scroll_outgoing',
                                    id: 'scroll_outgoing',
									hiddenFieldId: 'scroll_outgoing_writable',
									// @todo what about that?
									//hiddenFieldData: _settingData.scroll_outgoing_writable,                                    
                                    mode: 'local',
                                    displayField: 'name',
                                    valueField: 'id',
                                    anchor: '95%',
                                    triggerAction: 'all',
                                    editable: false,
                                    forceSelection: true,
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
                            }, {
                                columnWidth: 0.33,
                                layout: 'form',
                                border: false,
                                anchor: '100%',
                                items: [{
                                    xtype: 'lockCombo',
                                    fieldLabel: this.app.i18n._('show_local_line'),
                                    name: 'show_local_line',
                                    id: 'show_local_line',
									hiddenFieldId: 'show_local_line_writable',
									// @todo what about that?
									//hiddenFieldData: _settingData.show_local_line_writable,                                    
                                    mode: 'local',
                                    displayField: 'name',
                                    valueField: 'id',
                                    anchor: '95%',
                                    triggerAction: 'all',
                                    editable: false,
                                    forceSelection: true,
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
                            }, {
                                columnWidth: 0.33,
                                layout: 'form',
                                border: false,
                                anchor: '100%',
                                items: [{
                                    xtype: 'lockCombo',
                                    fieldLabel: this.app.i18n._('show_call_status'),
                                    name: 'show_call_status',
                                    id: 'show_call_status',
									hiddenFieldId: 'show_call_status_writable',
									// @todo what about that?
									//hiddenFieldData: _settingData.show_call_status_writable,                                        
                                    mode: 'local',
                                    displayField: 'name',
                                    valueField: 'id',
                                    anchor: '100%',
                                    triggerAction: 'all',
                                    editable: false,
                                    forceSelection: true,
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
                            }]
                        }]
                    }]   // form 
                }]
        };
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