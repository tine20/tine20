/**
 * Tine 2.0
 * 
 * @package     Voipmanager
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 * TODO         perhaps we should load the settings only if settings tab is clicked
 * TODO         don't use json stores for lines/rights
 */
 
Ext.namespace('Tine.Voipmanager');

/**
 * Snom Phone Edit Dialog
 */
Tine.Voipmanager.SnomPhoneEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {

    /**
     * @private
     */
    windowNamePrefix: 'SnomPhoneEditWindow_',
    appName: 'Voipmanager',
    recordClass: Tine.Voipmanager.Model.SnomPhone,
    recordProxy: Tine.Voipmanager.SnomPhoneBackend,
    evalGrants: false,
    
    /**
     * @property {Ext.data.JsonStore}
     */
    rightsStore: null,
    
    /**
     * @property {Ext.data.JsonStore}
     */
    linesStore: null,

    /**
     * @property {Tine.Voipmanager.LineGridPanel}
     */
    linesGrid: null,
    
    /**
     * max lines (depending on phone model)
     * 
     * @type Number
     */
    maxLines: 4,
    
    /**
     * writeable fields (from phone settings)
     * 
     * @type Object
     */
    writeableFields: null,
    
    /**
     * @private
     * 
     */
    initComponent: function() {
        
        // why the hack is this a jsonStore???
        this.rightsStore =  new Ext.data.JsonStore({
            root: 'results',
            totalProperty: 'totalcount',
            id: 'id',
            fields: Tine.Voipmanager.Model.SnomPhoneRight
        });
        
        // why the hack is this a jsonStore???
        this.linesStore = new Ext.data.JsonStore({
            root: 'results',
            totalProperty: 'totalcount',
            id: 'id',
            fields: Tine.Voipmanager.Model.SnomLine
        });
        
        Tine.Voipmanager.SnomPhoneEditDialog.superclass.initComponent.call(this);
    },
    
    /**
     * record load (get rights and put them into the store)
     */
    onRecordLoad: function() {
    	
        var rights = this.record.get('rights') || [];
        this.rightsStore.loadData({results: rights});
        
        // copy fields from asteriskline_id for lines grid
        var lines = this.record.get('lines') || [];
        var fields = ['cfi_mode','cfi_number','cfb_mode','cfb_number','cfd_mode','cfd_number','cfd_time' ];
        console.log(lines);
        for (var j=0; j < lines.length; j++) {
            for (var i=0; i < fields.length; i++) {
                lines[j][fields[i]] = lines[j].asteriskline_id[fields[i]];
            }
        }
        this.linesStore.loadData({results: lines});

        if (this.record.get('setting_id')) {
            this.getWriteableFields(this.record.get('setting_id'));
        }
        
        Tine.Voipmanager.SnomPhoneEditDialog.superclass.onRecordLoad.call(this);
    },
    
    /**
     * record update (push rights and lines into record property)
     */
    onRecordUpdate: function() {
        Tine.Voipmanager.SnomPhoneEditDialog.superclass.onRecordUpdate.call(this);
        
        this.record.set('rights', '');
        this.record.set('lines', '');
        
        var rights = [];
        this.rightsStore.each(function(_record){
            rights.push(_record.data);
        });
        this.record.set('rights', rights);
        
        // save lines / copy fields to asteriskline_id
        var lines = [];
        var fields = ['cfi_mode','cfi_number','cfb_mode','cfb_number','cfd_mode','cfd_number','cfd_time' ];
        this.linesStore.each(function(_record){
            var data = _record.data;
            for (var i=0; i < fields.length; i++) {
                data.asteriskline_id[fields[i]] = data[fields[i]];
            }
            lines.push(data);
        });
        this.record.set('lines', lines);
    },
    
    /**
     * 
     * @param {} setting_id
     */
    getWriteableFields: function(setting_id) {
    	
        Ext.Ajax.request({
            params: {
                method: 'Voipmanager.getSnomSetting', 
                id: setting_id
            },
            success: function(_result, _request) {
                _data = Ext.util.JSON.decode(_result.responseText);
                _writableFields = new Array('web_language','language','display_method','mwi_notification','mwi_dialtone','headset_device','message_led_other','global_missed_counter','scroll_outgoing','show_local_line','show_call_status','call_waiting');
                this.writeableFields = new Object();
                var _settingsData = new Object();

                Ext.each(_writableFields, function(_item, _index, _all) {
                    _rwField = _item.toString() + '_writable';

                    // update record
                    if(_data[_rwField] == '0' || !this.record.get(_item)) {
                        this.record.set(_item, _data[_item]);                                                        
                    }                                                       

                    // set writeable fields
                    this.getForm().findField(_item).setDisabled(_data[_rwField] == '0');
                    this.getForm().findField(_item).setValue(this.record.get(_item));
                }, this);                                     
            },
            failure: function ( result, request) { 
                Ext.MessageBox.alert('Failed', 'No settings data found.'); 
            },
            scope: this 
        });                                   
    	
    },
    
    /**
     * update settings on template change
     * 
     * @param {} _combo
     * @param {} _record
     * @param {} _index
     */
    onTemplateChange: function(_combo, _record, _index) {

    	var setting_id = false;
    	if (_record.data && _record.data.setting_id) {
    		setting_id = _record.data.setting_id;
    	}
    	
    	if (! setting_id && this.getForm().findField('template_id').store.getById(this.getForm().findField('template_id').getValue())) {
    	    setting_id = this.getForm().findField('template_id').store.getById(this.getForm().findField('template_id').getValue()).data.setting_id;
    	}
    	
    	if (setting_id) {
            this.getWriteableFields(setting_id);
    	}
    },
    
    /**
     * returns dialog
     * 
     * NOTE: when this method gets called, all initalisation is done.
     */
    getFormItems: function() {
        this.linesGrid = new Tine.Voipmanager.LineGridPanel({
            title: this.app.i18n._('Lines'),
            store: this.linesStore,
            app: this.app
        });
        
        return {
            xtype: 'tabpanel',
            border: false,
            plain:true,
            activeTab: 0,
            deferredRender: false,
            items:[
                this.getPhonePanel(),
                this.linesGrid,
                this.getSettingsPanel(),
                this.getRightsPanel()
            ]
        };
    },
    
    /**
     * returns general phone panel (first panel)
     * 
     * @return {Object}
     */
    getPhonePanel: function() {
        return {
            title: this.app.i18n._('Phone'),
            layout: 'hfit',
            frame: true,
            border: false,
            items: [{
                xtype: 'columnform',
                formDefaults: {
                    columnWidth: 0.5,
                    anchor: '100%',
                    labelSeparator: ''
                },
                items: [
                    [{
                        xtype: 'textfield',
                        name: 'description',
                        fieldLabel: this.app.i18n._('Name'),
                        allowBlank: false
                    }, {
                        xtype: 'combo',
                        fieldLabel: this.app.i18n._('Phone Model'),
                        name: 'current_model',
                        mode: 'local',
                        displayField: 'model',
                        valueField: 'id',
                        triggerAction: 'all',
                        editable: false,
                        forceSelection: true,
                        store: Tine.Voipmanager.Data.loadPhoneModelData()
                    }], [{
                        xtype: 'textfield',
                        fieldLabel: this.app.i18n._('MAC Address'),
                        name: 'macaddress',
                        maxLength: 12,
                        allowBlank: false
                    }, {
                        xtype:'reccombo',
                        name: 'template_id',
                        fieldLabel: this.app.i18n.n_('Template', 'Templates', 1),
                        displayField: 'name',
                        store: new Ext.data.Store({
                            fields: Tine.Voipmanager.Model.SnomTemplate,
                            proxy: Tine.Voipmanager.SnomTemplateBackend,
                            reader: Tine.Voipmanager.SnomTemplateBackend.getReader(),
                            remoteSort: true,
                            sortInfo: {field: 'name', dir: 'ASC'}
                        }),
                        listeners: {
                            scope: this,
                            select: this.onTemplateChange
                        }
                    }], [{
                        xtype:'reccombo',
                        name: 'location_id',
                        fieldLabel: this.app.i18n.n_('Location', 'Locations', 1),
                        displayField: 'name',
                        store: new Ext.data.Store({
                        	fields: Tine.Voipmanager.Model.SnomLocation,
                            proxy: Tine.Voipmanager.SnomLocationBackend,
                            reader: Tine.Voipmanager.SnomLocationBackend.getReader(),
                            remoteSort: true,
                            sortInfo: {field: 'name', dir: 'ASC'}
                        })
                    }]
                ]}, {
                    title: this.app.i18n._('infos'),
                    autoHeight: true,
                    xtype: 'fieldset',
                    checkboxToggle: false,
                    items: [{
                        xtype: 'columnform',
                        border: false,
                        formDefaults: {
                            columnWidth: 0.5,
                            anchor: '100%',
                            labelSeparator: ''
                        },
                        items: [
                            [{
                                xtype: 'textfield',
                                fieldLabel: this.app.i18n._('Current IP Address'),
                                name: 'ipaddress',
                                maxLength: 20,
                                anchor: '98%',
                                readOnly: true
                            }, {
                                xtype: 'datetimefield',
                                fieldLabel: this.app.i18n._('Firmware last checked at'),
                                name: 'firmware_checked_at',
                                anchor: '100%',
                                emptyText: 'never',
                                hideTrigger: true,
                                readOnly: true
                            }], [{
                                xtype: 'textfield',
                                fieldLabel: this.app.i18n._('Current Software Version'),
                                name: 'current_software',
                                maxLength: 20,
                                anchor: '98%',
                                readOnly: true
                            }, {
                                xtype: 'datetimefield',
                                fieldLabel: this.app.i18n._('Settings Loaded at'),
                                name: 'settings_loaded_at',
                                anchor: '100%',
                                emptyText: 'never',
                                hideTrigger: true,
                                readOnly: true
                            }]
                        ]
                    }]
                }]
            };
    },
    
    /**
     * returns settings panel (second panel)
     * 
     * @return {Object}
     */
    getSettingsPanel: function() {
        return {
            title: this.app.i18n._('Settings'),
            id: 'settingsBorderLayout',
            frame: true,
            border: false,
            xtype: 'columnform',
            formDefaults: {
                xtype:'combo',
                anchor: '100%',
                labelSeparator: '',
                columnWidth: .333,
                mode: 'local',
                triggerAction: 'all',
                editable: false,
                forceSelection: true,
                value: null
            },
            items: [
                [{
                    fieldLabel: this.app.i18n._('web_language'),
                    name: 'web_language',
                    disabled: (this.writeableFields) ? this.writeableFields.web_language : true,
                    store: [
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
                }, {
                    fieldLabel: this.app.i18n._('language'),
                    name: 'language',
                    disabled: (this.writeableFields) ? this.writeableFields.language : true,                                    
                    store: [
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
                }, {
                    fieldLabel: this.app.i18n._('display_method'),
                    name: 'display_method',
                    disabled: (this.writeableFields) ? this.writeableFields.display_method : true,
                    store: [
                        [ null,  this.app.i18n._('- factory default -')],                                                                                
                        ['full_contact', this.app.i18n._('whole url')],
                        ['display_name', this.app.i18n._('name')],
                        ['display_number', this.app.i18n._('number')],
                        ['display_name_number', this.app.i18n._('name + number')],
                        ['display_number_name', this.app.i18n._('number + name')]
                    ]
                }], [{
                    fieldLabel: this.app.i18n._('call_waiting'),
                    name: 'call_waiting',
                    disabled: (this.writeableFields) ? this.writeableFields.call_waiting : true,
                    store: [
                        [ null,  this.app.i18n._('- factory default -')],                                        
                        ['on', this.app.i18n._('on')],
                        ['visual', this.app.i18n._('visual')],
                        ['ringer', this.app.i18n._('ringer')],
                        ['off', this.app.i18n._('off')]
                    ]
                }, {
                    fieldLabel: this.app.i18n._('mwi_notification'),
                    name: 'mwi_notification',
                    disabled: (this.writeableFields) ? this.writeableFields.mwi_notification : true,
                    store: [
                        [ null,  this.app.i18n._('- factory default -')],                                        
                        ['silent', this.app.i18n._('silent')],
                        ['beep', this.app.i18n._('beep')],
                        ['reminder', this.app.i18n._('reminder')]
                    ]
                }, {
                    fieldLabel: this.app.i18n._('mwi_dialtone'),
                    name: 'mwi_dialtone',
                    disabled: (this.writeableFields) ? this.writeableFields.mwi_dialtone : true,
                    store: [
                        [ null,  this.app.i18n._('- factory default -')],                                        
                        ['normal', this.app.i18n._('normal')],
                        ['stutter', this.app.i18n._('stutter')]
                    ]
                }], [{
                    fieldLabel: this.app.i18n._('headset_device'),
                    name: 'headset_device',
                    disabled: (this.writeableFields) ? this.writeableFields.headset_device : true,
                    store: [
                        [ null,  this.app.i18n._('- factory default -')],                                        
                        ['none', this.app.i18n._('none')],
                        ['headset_rj', this.app.i18n._('headset_rj')]
                    ]
                }, {
                    fieldLabel: this.app.i18n._('message_led_other'),
                    name: 'message_led_other',
                    disabled: (this.writeableFields) ? this.writeableFields.message_led_other : true,
                    store: [
                        [ null,  this.app.i18n._('- factory default -')],
                        ['1', this.app.i18n._('on')],
                        ['0', this.app.i18n._('off')]
                    ]
                }, {
                    fieldLabel: this.app.i18n._('global_missed_counter'),
                    name: 'global_missed_counter',
                    disabled: (this.writeableFields) ? this.writeableFields.global_missed_counter : true,
                    store: [
                        [ null,  this.app.i18n._('- factory default -')],
                        ['1', this.app.i18n._('on')], 
                        ['0', this.app.i18n._('off')]
                    ]
                }], [{
                    fieldLabel: this.app.i18n._('scroll_outgoing'),
                    name: 'scroll_outgoing',
                    disabled: (this.writeableFields) ? this.writeableFields.scroll_outgoing : true,
                    store: [
                        [ null,  this.app.i18n._('- factory default -')],
                        ['1', this.app.i18n._('on')],
                        ['0', this.app.i18n._('off')]
                    ]
                }, {
                    fieldLabel: this.app.i18n._('show_local_line'),
                    name: 'show_local_line',
                    disabled: (this.writeableFields) ? this.writeableFields.show_local_line : true,
                    store: [
                        [ null,  this.app.i18n._('- factory default -')],
                        ['1', this.app.i18n._('on')],
                        ['0', this.app.i18n._('off')]
                    ]
                }, {
                    fieldLabel: this.app.i18n._('show_call_status'),
                    name: 'show_call_status',
                    disabled: (this.writeableFields) ? this.writeableFields.show_call_status : true,
                    store: [
                        [ null,  this.app.i18n._('- factory default -')],
                        ['1', this.app.i18n._('on')],
                        ['0', this.app.i18n._('off')]
                    ]
                }]
            ]
        };
    },
    
    /**
     * returns right panel (fourth panel)
     * 
     * @return {Object}
     */
    getRightsPanel: function() {
        return {
            title: this.app.i18n._('Users'),
            layout: 'fit',
            items: new Tine.widgets.account.PickerGridPanel({
                accountPickerType: 'both',
                store: this.rightsStore,
                hasAccountPrefix: true
            })
        };
    }
});


/**
 * Snom Phone Edit Popup
 */
Tine.Voipmanager.SnomPhoneEditDialog.openWindow = function (config) {
    var id = (config.record && config.record.id) ? config.record.id : 0;
    var window = Tine.WindowFactory.getWindow({
        width: 800,
        height: 350,
        name: Tine.Voipmanager.SnomPhoneEditDialog.prototype.windowNamePrefix + id,
        contentPanelConstructor: 'Tine.Voipmanager.SnomPhoneEditDialog',
        contentPanelConstructorConfig: config
    });
    return window;
};
