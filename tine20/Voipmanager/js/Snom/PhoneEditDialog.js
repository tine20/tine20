/**
 * Tine 2.0
 * 
 * @package     Voipmanager
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 * @todo        add settings again
 * @todo        add lines again
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
     * @private
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
        
        var lines = this.record.get('lines') || [];
        this.linesStore.loadData({results: lines});
        
        Tine.Voipmanager.SnomPhoneEditDialog.superclass.onRecordLoad.call(this);
    },
    
    /**
     * record update (push rights into record property)
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
        
        var lines = [];
        this.linesStore.each(function(_record){
            lines.push(_record.data);
        });
        this.record.set('lines', lines);
    },
    
    /**
     * max lines
     * 
     * @todo this data is already in some data array in voipmanager.js
     * @param {} _val
     * @return {}
     */
    _maxLines: function(_val) {      
        var _data = new Object();
        _data.snom300 = '4';
        _data.snom320 = '12';
        _data.snom360 = '12';
        _data.snom370 = '12';   
         
        if(!_val) {
            return _data;
        }        
        return _data[_val];
    },    
    
    /**
     * update settings on template change
     * 
     * @param {} _combo
     * @param {} _record
     * @param {} _index
     * 
     * @todo add that again
     */
    onTemplateChange: function(_combo, _record, _index) {
    	
        /*
          Ext.Ajax.request({
                params: {
                    method: 'Voipmanager.getSnomSetting', 
                    settingId: _record.data.setting_id
                },
                success: function(_result, _request) {
                    _data = Ext.util.JSON.decode(_result.responseText);
                    _writableFields = new Array('web_language','language','display_method','mwi_notification','mwi_dialtone','headset_device','message_led_other','global_missed_counter','scroll_outgoing','show_local_line','show_call_status','call_waiting');
                    var _notWritable = new Object();
                    var _settingsData = new Object();

                    Ext.each(_writableFields, function(_item, _index, _all) {
                        _rwField = _item.toString() + '_writable';

                        if(_data[_rwField] == '0') {
                            _settingsData[_item] = _data[_item];                                                        
                        } else  if(_phoneData[_item]) {
                             _settingsData[_item] = _phoneData[_item];
                        } else {
                             _settingsData[_item] = _data[_item];                                                        
                        }                                                       

                        
                        if(_data[_rwField] == '0') {
                            _notWritable[_rwField.toString()] = 'true';                                                                                                       
                        } else  {
                             _notWritable[_rwField.toString()] = 'false';                                                        
                        }   
                    });                     
                    
                    Array.prototype.in_array = function(needle) {
                        for (var i = 0; i < this.length; i++) {
                            if (this[i] === needle) {
                                return true;
                            }
                        }
                        return false;
                    }; 

                    Ext.getCmp('voipmanager_editPhoneForm').cascade(function(_field) {
                        if(_writableFields.in_array(_field.id)) {
                            if(_notWritable[_field.id.toString()+'_writable'] == 'true') {
                                _field.disable();    
                            }
                            if(_notWritable[_field.id.toString()+'_writable'] == 'false') {
                                _field.enable();    
                            }            
                            _field.setValue(_settingsData[_field.id]);
                        }
                    });
                
                    Ext.getCmp('voipmanager_editPhoneForm').doLayout();
                },
                failure: function ( result, request) { 
                    Ext.MessageBox.alert('Failed', 'No settings data found.'); 
                },
                scope: this 
            });                                   
        }
        */ 
    },
    
    /**
     * on model change
     * 
     * @param {} _combo
     * @param {} _record
     * @param {} _index
     */
    onModelChange: function(_combo, _record, _index) {
        // @todo add that again
        /*
        _store = Ext.getCmp('Voipmanager_PhoneLines_Grid').getStore();

        while (_store.getCount() > _maxLines[_record.data.id] ) {
            var _id = _store.getCount();
            _store.remove(_store.getAt((_id-1)));
        }
  
        while (_store.getCount() < _maxLines[_record.data.id]) {
            _snomRecord = new Tine.Voipmanager.Model.Snom.Line({
                'asteriskline_id':'',
                'id':'',
                'idletext':'',
                'lineactive':0,
                'linenumber':_store.getCount()+1,
                'snomphone_id':''
            });         
            _store.add(_snomRecord);
        }                                        
        */
    },
    
    /**
     * 
     * @param {} _maxLines
     * @param {} _lines
     * @param {} _snomLines
     * @return {}
     * 
     * @todo make it work again
     */
    //editPhoneLinesDialog: function(/*_maxLines, _lines, _snomLines*/) {
    	
    	/*
        var linesText = [];
        var linesSIPCombo = [];
        var linesIdleText = [];
        var linesActive = [];
        
        var checkColumn = new Ext.ux.grid.CheckColumn({
           header: this.app.i18n._('lineactive'),
           dataIndex: 'lineactive',
           width: 25
        });         
        
        var linesDS = new Ext.data.JsonStore({
            autoLoad: true,
            id: 'id',
            fields: ['id','name'],
            data: _lines
        });
        
        while (snomLinesDS.getCount() < _maxLines) {
            _snomRecord = new Tine.Voipmanager.Model.Snom.Line({
                'asteriskline_id':'',
                'id':'',
                'idletext':'',
                'lineactive':0,
                'linenumber':snomLinesDS.getCount()+1,
                'snomphone_id':''
            });         
            snomLinesDS.add(_snomRecord);
        }
        
        Ext.namespace("Ext.ux");
        Ext.ux.comboBoxRenderer = function(combo) {
          return function(value) {
            var rec = combo.store.getById(value);
            return (rec == null ? '' : rec.get(combo.displayField) );
          };
        };          
        
        var combo = new Ext.form.ComboBox({
            typeAhead: true,
            triggerAction: 'all',
            lazyRender:true,
            mode: 'local',
            displayField:'name',
            valueField:'id',
            anchor:'98%',                    
            triggerAction: 'all',
            allowBlank: false,
            editable: false,
            store: linesDS
        }); 
        
        var columnModel = new Ext.grid.ColumnModel([
            { resizable: true, id: 'id', header: 'line', dataIndex: 'id', width: 20, hidden: true },
            {
                resizable: true,
                id: 'sipCombo',
                header: this.app.i18n._('sipCombo'),
                dataIndex: 'asteriskline_id',
                width: 80,
                editor: combo,
                renderer: Ext.ux.comboBoxRenderer(combo)
            },
            {
                resizable: true,
                id: 'idletext',
                header: this.app.i18n._('Idle Text'),
                dataIndex: 'idletext',
                width: 40,
                editor: new Ext.form.TextField({
                   allowBlank: false,
                   allowNegative: false,
                   maxLength: 60
               })  
            },
            checkColumn
        ]); 
        
        var gridPanel = new Ext.grid.EditorGridPanel({
            region: 'center',
            id: 'Voipmanager_PhoneLines_Grid',
            store: snomLinesDS,
            cm: columnModel,
            autoSizeColumns: false,
            plugins:checkColumn,
            clicksToEdit:1,
            enableColLock:false,
            loadMask: true,
            autoExpandColumn: 'idleText',
            border: false,
            view: new Ext.grid.GridView({
                autoFill: true,
                forceFit:true,
                ignoreAdd: true,
                emptyText: 'No software to display'
            })            
        });
    },*/             

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
            deferredRender: false,
            items:[
                this.getPhonePanel(), 
                this.getSettingsPanel(),
                this.getLinesPanel(),
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
                        listeners: {
                            select: this.onModelChange
                        },
                        store: Tine.Voipmanager.Data.loadPhoneModelData()
                    }], [{
                        xtype: 'textfield',
                        fieldLabel: this.app.i18n._('MAC Address'),
                        name: 'macaddress',
                        maxLength: 12,
                        allowBlank: false
                    }, {
                        xtype: 'combo',
                        fieldLabel: this.app.i18n._('Template'),
                        name: 'template_id',
                        displayField: 'name',
                        valueField: 'id',
                        triggerAction: 'all',
                        editable: false,
                        forceSelection: true,
                        store: new Ext.data.Store({
                            fields: Tine.Voipmanager.Model.SnomTemplate,
                            proxy: Tine.Voipmanager.SnomTemplateBackend,
                            remoteSort: true,
                            sortInfo: {field: 'name', dir: 'ASC'}
                        }),
                        listeners: {
                            select: this.onTemplateChange
                        }
                    }], [{
                        xtype: 'combo',
                        fieldLabel: this.app.i18n._('Location'),
                        name: 'location_id',
                        displayField: 'name',
                        valueField: 'id',
                        triggerAction: 'all',
                        editable: false,
                        forceSelection: true,
                        store: new Ext.data.Store({
                            fields: Tine.Voipmanager.Model.SnomLocation,
                            proxy: Tine.Voipmanager.SnomLocationBackend,
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
                }, {
                    title: this.app.i18n._('redirection'),
                    xtype: 'fieldset',
                    checkboxToggle: false,
                    autoHeight: true,
                    items: [{
                        xtype: 'columnform',
                        border: false,
                        formDefaults: {
                            columnWidth: 0.333,
                            anchor: '100%',
                            labelSeparator: ''
                        },
                        items: [
                            [{ 
                                xtype: 'combo',
                                fieldLabel: this.app.i18n._('redirect_event'),
                                name: 'redirect_event',
                                mode: 'local',
                                triggerAction: 'all',
                                editable: false,
                                forceSelection: true,
                                value: 'all',
                                listeners: {
                                    select: function(_combo, _record, _index) {
                                        if (_record.data.id == 'time') {
                                            Ext.getCmp('redirect_time').enable();
                                        }
                                        
                                        if(_record.data.id != 'time') {                                                   
                                            Ext.getCmp('redirect_time').disable();
                                        }
                                    }
                                },
                                store: [
                                    ['all', this.app.i18n._('all')],
                                    ['busy', this.app.i18n._('busy')],
                                    ['none', this.app.i18n._('none')],
                                    ['time', this.app.i18n._('time')]
                                ]
                            }, {
                                xtype: 'textfield',
                                fieldLabel: this.app.i18n._('redirect_number'),
                                name: 'redirect_number'
                            }, {
                                xtype: 'numberfield',
                                fieldLabel: this.app.i18n._('redirect_time'),
                                name: 'redirect_time',
                                id: 'redirect_time',
                                anchor: '100%'                                                                     
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
                    // @todo add that again
                    //disabled: _writable.web_language,
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
                    // @todo add that again
                    //disabled: _writable.language,                                    
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
                    // @todo add that again
                    //disabled: _writable.display_method,                                    
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
                    // @todo add that again
                    //disabled: _writable.call_waiting,                                    
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
                    // @todo add that again
                    //disabled: _writable.mwi_notification,                                    
                    store: [
                        [ null,  this.app.i18n._('- factory default -')],                                        
                        ['silent', this.app.i18n._('silent')],
                        ['beep', this.app.i18n._('beep')],
                        ['reminder', this.app.i18n._('reminder')]
                    ]
                }, {
                    fieldLabel: this.app.i18n._('mwi_dialtone'),
                    name: 'mwi_dialtone',
                    // @todo add that again
                    //disabled: _writable.mwi_dialtone,                                    
                    store: [
                        [ null,  this.app.i18n._('- factory default -')],                                        
                        ['normal', this.app.i18n._('normal')],
                        ['stutter', this.app.i18n._('stutter')]
                    ]
                }], [{
                    fieldLabel: this.app.i18n._('headset_device'),
                    name: 'headset_device',
                    // @todo add that again
                    //disabled: _writable.headset_device,                                    
                    store: [
                        [ null,  this.app.i18n._('- factory default -')],                                        
                        ['none', this.app.i18n._('none')],
                        ['headset_rj', this.app.i18n._('headset_rj')]
                    ]
                }, {
                    fieldLabel: this.app.i18n._('message_led_other'),
                    name: 'message_led_other',
                    // @todo add that again
                    //disabled: _writable.message_led_other,                                        
                    store: [
                        [ null,  this.app.i18n._('- factory default -')],
                        ['1', this.app.i18n._('on')],
                        ['0', this.app.i18n._('off')]
                    ]
                }, {
                    fieldLabel: this.app.i18n._('global_missed_counter'),
                    name: 'global_missed_counter',
                    // @todo add that again
                    //disabled: _writable.global_missed_counter,                                    
                    store: [
                        [ null,  this.app.i18n._('- factory default -')],
                        ['1', this.app.i18n._('on')], 
                        ['0', this.app.i18n._('off')]
                    ]
                }], [{
                    fieldLabel: this.app.i18n._('scroll_outgoing'),
                    name: 'scroll_outgoing',
                    // @todo add that again
                    //disabled: _writable.scroll_outgoing,                                    
                    store: [
                        [ null,  this.app.i18n._('- factory default -')],
                        ['1', this.app.i18n._('on')],
                        ['0', this.app.i18n._('off')]
                    ]
                }, {
                    fieldLabel: this.app.i18n._('show_local_line'),
                    name: 'show_local_line',
                    // @todo add that again
                    //disabled: _writable.show_local_line,                                    
                    store: [
                        [ null,  this.app.i18n._('- factory default -')],
                        ['1', this.app.i18n._('on')],
                        ['0', this.app.i18n._('off')]
                    ]
                }, {
                    fieldLabel: this.app.i18n._('show_call_status'),
                    name: 'show_call_status',
                    // @todo add that again
                    //disabled: _writable.show_call_status,                                    
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
     * returns the lines panel (thrid panel)
     * 
     * @return {Object}
     */
    getLinesPanel: function() {
        return {
            title: this.app.i18n._('Lines'),
            layout: 'fit',
            html: ''
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
            items: new Tine.widgets.account.ConfigGrid({
                accountPickerType: 'both',
                accountListTitle: this.app.i18n._('Rights'),
                configStore: this.rightsStore,
                hasAccountPrefix: true
                //configColumns: columns
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
        width: 700,
        height: 450,
        name: Tine.Voipmanager.SnomPhoneEditDialog.prototype.windowNamePrefix + id,
        contentPanelConstructor: 'Tine.Voipmanager.SnomPhoneEditDialog',
        contentPanelConstructorConfig: config
    });
    return window;
};
