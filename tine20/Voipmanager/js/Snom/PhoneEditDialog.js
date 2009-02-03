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
 * @todo        add user & lines again
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
    loadRecord: false,
    tbarItems: [{xtype: 'widget-activitiesaddbutton'}],
    evalGrants: false,
    
    /**
     * overwrite update toolbars function (we don't have record grants yet)
     */
    updateToolbars: function(record) {
    	Tine.Voipmanager.SnomPhoneEditDialog.superclass.updateToolbars.call(this, record, 'id');
    },
    
    /**
     * max lines
     * 
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
     * 
     * @param {} _maxLines
     * @param {} _lines
     * @param {} _snomLines
     * @return {}
     * 
     * @todo make it work again
     */
    editPhoneLinesDialog: function(/*_maxLines, _lines, _snomLines*/) {
        
    	/*
        var linesText = new Array();
        var linesSIPCombo = new Array();
        var linesIdleText = new Array();
        var linesActive = new Array();
        
        Ext.grid.CheckColumn = function(config){
            Ext.apply(this, config);
            if(!this.id){
                this.id = Ext.id();
            }
            this.renderer = this.renderer.createDelegate(this);
        };
        
        Ext.grid.CheckColumn.prototype ={
            init : function(grid){
                this.grid = grid;
                this.grid.on('render', function(){
                    var view = this.grid.getView();
                    view.mainBody.on('mousedown', this.onMouseDown, this);
                }, this);
            },
        
            onMouseDown : function(e, t){
                if(t.className && t.className.indexOf('x-grid3-cc-'+this.id) != -1){
                    e.stopEvent();
                    var index = this.grid.getView().findRowIndex(t);
                    var record = this.grid.store.getAt(index);
                    record.set(this.dataIndex, !record.data[this.dataIndex]);
                }
            },
        
            renderer : function(v, p, record){
                p.css += ' x-grid3-check-col-td'; 
                return '<div class="x-grid3-check-col'+(v?'-on':'')+' x-grid3-cc-'+this.id+'">&#160;</div>';
            }
        };
        
        var checkColumn = new Ext.grid.CheckColumn({
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
        
        var snomLinesDS = new Ext.data.JsonStore({
            autoLoad: true,
            storeId: 'Voipmanger_EditPhone_SnomLines',
            id: 'id',
            fields: ['asteriskline_id','id','idletext','lineactive','linenumber','snomphone_id'],
            data: _snomLines
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
        */
        var _phoneLinesDialog = {
            title: 'Lines',
            id: 'phoneLines',
            layout: 'border',
            anchor: '100% 100%',
            layoutOnTabChange: true,
            defaults: {
                border: true,
                frame: false
            },
            items: [
            //    gridPanel
            ]
        };
        
        return _phoneLinesDialog;
//          return gridPanel;
    },                

    /**
     * 
     * @param {} _groupMembers
     * @return {}
     * 
     * @todo make it work again
     */
    editPhoneOwnerSelection: function(/*_groupMembers*/){
    
    	/*
    

        this.actions = {
            addAccount: new Ext.Action({
                text: this.app.i18n._('add account'),
                disabled: true,
                scope: this,
                handler: this.handlers.addAccount,
                iconCls: 'action_addContact'
            }),
            removeAccount: new Ext.Action({
                text: this.app.i18n._('remove account'),
                disabled: true,
                scope: this,
                handler: this.handlers.removeAccount,
                iconCls: 'action_deleteContact'
            })
        };

        var accountPicker =  new Tine.widgets.account.PickerPanel ({            
            enableBbar: true,
            region: 'west',
            height: 200,
            //bbar: this.userSelectionBottomToolBar,
            selectAction: function() {              
                this.account = account;
                this.handlers.addAccount(account);
            }  
        });
                
        accountPicker.on('accountdblclick', function(account){
            this.account = account;
            this.handlers.addAccount(account);
        }, this);
            
        this.dataStore = new Ext.data.JsonStore({
            id: 'account_id',
            fields: Tine.Voipmanager.Model.Snom.Owner
        });

        Ext.StoreMgr.add('GroupMembersStore', this.dataStore);
        
        this.dataStore.setDefaultSort('accountDisplayName', 'asc');        
        
        if (_groupMembers.length === 0) {
            this.dataStore.removeAll();
        } else {
            this.dataStore.loadData(_groupMembers);    
        }

        var columnModel = new Ext.grid.ColumnModel([{ 
            resizable: true, id: 'accountDisplayName', header: this.app.i18n._('Name'), dataIndex: 'accountDisplayName', width: 30 
        }]);

        var rowSelectionModel = new Ext.grid.RowSelectionModel({multiSelect:true});

        rowSelectionModel.on('selectionchange', function(_selectionModel) {
            var rowCount = _selectionModel.getCount();

            if(rowCount < 1) {
                // no row selected
                this.actions.removeAccount.setDisabled(true);
            } else {
                // only one row selected
                this.actions.removeAccount.setDisabled(false);
            }
        }, this);
       


        var usersBottomToolbar = new Ext.Toolbar({
            items: [
                this.actions.removeAccount
            ]
        });


        
        var phoneUsersGridPanel = new Ext.grid.EditorGridPanel({
            id: 'phoneUsersGrid',
            region: 'center',
            title: this.app.i18n._('Owner'),
            store: this.dataStore,
            cm: columnModel,
            autoSizeColumns: false,
            selModel: rowSelectionModel,
            enableColLock:false,
            loadMask: true,
            //autoExpandColumn: 'accountLoginName',
            autoExpandColumn: 'accountDisplayName',
            bbar: usersBottomToolbar,
            border: true
        }); 
        

                */
        
        var editGroupDialog = {
            layout:'border',
            title: this.app.i18n._('Users'),
            border:false,
            width: 600,
            height: 500,
            items:[
                //accountPicker, 
                //phoneUsersGridPanel
            ]
        };            
        
        return editGroupDialog;   
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
                title: this.app.i18n._('Phone'),
                layout: 'border',
                anchor: '100% 100%',
                layoutOnTabChange: true,
                defaults: {
                    border: true,
                    frame: false
                },
                items: [{
                    region: 'center',
                    autoScroll: true,
                    autoHeight: true,
                    items: [{
                        layout: 'column',
                        border: false,
                        anchor: '100%',
                        height: 130,
                        items: [{
                            columnWidth: 0.5,
                            layout: 'form',
                            border: false,
                            anchor: '100%',
                            items: [
                            {
                                xtype: 'textfield',
                                name: 'description',
                                fieldLabel: this.app.i18n._('Name'),
                                anchor: '98%',
                                allowBlank: false
                                /*
                                grow: false,
                                preventScrollbars: false,
                                height: 70
                                */
                            },{
                                xtype: 'textfield',
                                fieldLabel: this.app.i18n._('MAC Address'),
                                name: 'macaddress',
                                maxLength: 12,
                                anchor: '98%',
                                allowBlank: false
                            }, {
                                xtype: 'combo',
                                fieldLabel: this.app.i18n._('Template'),
                                name: 'template_id',
                                id: 'template_id',
                                mode: 'local',
                                displayField: 'name',
                                valueField: 'id',
                                anchor: '98%',
                                triggerAction: 'all',
                                editable: false,
                                forceSelection: true,
                                listeners: {
                                	// @todo add that again
                                	/*
                                    select: function(_combo, _record, _index) {

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
                                store: new Ext.data.JsonStore({
                                    storeId: 'Voipmanger_EditPhone_Templates',
                                    id: 'id',
                                    fields: ['id', 'name', 'setting_id']
                                })
                            }]
                        }, {
                            columnWidth: 0.5,
                            layout: 'form',
                            border: false,
                            anchor: '98%',
                            autoHeight: true,
                            items: [new Ext.form.ComboBox({
                                fieldLabel: this.app.i18n._('Phone Model'),
                                name: 'current_model',
                                id: 'current_model',
                                mode: 'local',
                                displayField: 'model',
                                valueField: 'id',
                                anchor: '100%',
                                triggerAction: 'all',
                                editable: false,
                                forceSelection: true,
                                listeners: {
                                	// @todo add that again
                                	/*
                                    select: function(_combo, _record, _index) {
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
                                    }
                                    */
                                },
                                store: Tine.Voipmanager.Data.loadPhoneModelData()
                            }),
                            {
                                xtype: 'combo',
                                fieldLabel: this.app.i18n._('Location'),
                                name: 'location_id',
                                id: 'location_id',
                                mode: 'local',
                                displayField: 'name',
                                valueField: 'id',
                                anchor: '98%',
                                triggerAction: 'all',
                                editable: false,
                                forceSelection: true,
                                store: new Ext.data.JsonStore({
                                    storeId: 'Voipmanger_EditPhone_Locations',
                                    id: 'id',
                                    fields: ['id', 'name']
                                })
                            }                            
                            /*, {
                                xtype: 'textarea',
                                name: 'description',
                                fieldLabel: this.app.i18n._('Description'),
                                grow: false,
                                preventScrollbars: false,
                                anchor: '100%',
                                height: 70
                            } */]
                        }]
                    }, {
                        layout: 'form',
                        border: false,
                        anchor: '100%',
                        items: [{
                            xtype: 'fieldset',
                            checkboxToggle: false,
                            id: 'infos',
                            title: this.app.i18n._('infos'),
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
                                    columnWidth: 0.5,
                                    layout: 'form',
                                    border: false,
                                    anchor: '100%',
                                    items: [{
                                        xtype: 'textfield',
                                        fieldLabel: this.app.i18n._('Current IP Address'),
                                        name: 'ipaddress',
                                        maxLength: 20,
                                        anchor: '98%',
                                        readOnly: true
                                    }, {
                                        xtype: 'textfield',
                                        fieldLabel: this.app.i18n._('Current Software Version'),
                                        name: 'current_software',
                                        maxLength: 20,
                                        anchor: '98%',
                                        readOnly: true
                                    }]
                                }, {
                                    columnWidth: 0.5,
                                    layout: 'form',
                                    border: false,
                                    anchor: '100%',
                                    items: [{
                                        xtype: 'datetimefield',
                                        fieldLabel: this.app.i18n._('Settings Loaded at'),
                                        name: 'settings_loaded_at',
                                        anchor: '100%',
                                        emptyText: 'never',
                                        hideTrigger: true,
                                        readOnly: true
                                    }, {
                                        xtype: 'datetimefield',
                                        fieldLabel: this.app.i18n._('Firmware last checked at'),
                                        name: 'firmware_checked_at',
                                        anchor: '100%',
                                        emptyText: 'never',
                                        hideTrigger: true,
                                        readOnly: true
                                    }]
                                }]
                            }]
                        }]
                    },{
                        xtype: 'fieldset',
                        checkboxToggle: false,
                        id: 'redirectionFieldset',
                        title: this.app.i18n._('redirection'),
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
                                columnWidth: 0.33,
                                layout: 'form',
                                border: false,
                                anchor: '100%',
                                items: [{
                                    xtype: 'combo',
                                    fieldLabel: this.app.i18n._('redirect_event'),
                                    name: 'redirect_event',
                                    id: 'redirect_event',                                                                       
                                    mode: 'local',
                                    anchor: '95%',
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
                                }]
                            }, {
                                columnWidth: 0.33,
                                layout: 'form',
                                border: false,
                                anchor: '100%',
                                items: [{
                                    xtype: 'textfield',
                                    fieldLabel: this.app.i18n._('redirect_number'),
                                    name: 'redirect_number',
                                    id: 'redirect_number',
                                    anchor: '95%'                                
                               }]
                            }, {
                                columnWidth: 0.33,
                                layout: 'form',
                                border: false,
                                anchor: '100%',
                                items: [{
                                    xtype: 'numberfield',
                                    fieldLabel: this.app.i18n._('redirect_time'),
                                    name: 'redirect_time',
                                    id: 'redirect_time',
                                    anchor: '100%'                                                                     
                               }]
                            }]  
                        }]
                    }]
                }]
            }, {
                title: this.app.i18n._('Settings'),
                layout: 'border',
                id: 'settingsBorderLayout',
                anchor: '100% 100%',
                layoutOnTabChange: true,
                defaults: {
                    border: true,
                    frame: false
                },
                items: [{
                    layout: 'hfit',
                    containsScrollbar: false,
                    //margins: '0 18 0 5',
                    autoScroll: false,
                    id: 'editSettingMainDialog',
                    region: 'center',
                    items: [{
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
                                    xtype: 'combo',
                                    fieldLabel: this.app.i18n._('web_language'),
                                    name: 'web_language',
                                    id: 'web_language',
                                    // @todo add that again
                                    //disabled: _writable.web_language,
                                    mode: 'local',
                                    anchor: '95%',
                                    triggerAction: 'all',
                                    editable: false,
                                    forceSelection: true,
                                    value: null,
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
                                }]
                            }, {
                                columnWidth: 0.33,
                                layout: 'form',
                                border: false,
                                anchor: '100%',
                                items: [{
                                    xtype: 'combo',
                                    fieldLabel: this.app.i18n._('language'),
                                    name: 'language',
                                    id: 'language',
                                    // @todo add that again
                                    //disabled: _writable.language,                                    
                                    mode: 'local',
                                    anchor: '95%',
                                    triggerAction: 'all',
                                    editable: false,
                                    forceSelection: true,
                                    value: null,
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
                                }]
                            },{
                                columnWidth: 0.33,
                                layout: 'form',
                                border: false,
                                anchor: '100%',
                                items: [{
                                    xtype: 'combo',
                                    fieldLabel: this.app.i18n._('display_method'),
                                    name: 'display_method',
                                    id: 'display_method',
                                    // @todo add that again
                                    //disabled: _writable.display_method,                                    
                                    mode: 'local',
                                    anchor: '100%',
                                    triggerAction: 'all',
                                    editable: false,
                                    forceSelection: true,
                                    value: null,
                                    store: [
                                        [ null,  this.app.i18n._('- factory default -')],                                                                                
                                        ['full_contact', this.app.i18n._('whole url')],
                                        ['display_name', this.app.i18n._('name')],
                                        ['display_number', this.app.i18n._('number')],
                                        ['display_name_number', this.app.i18n._('name + number')],
                                        ['display_number_name', this.app.i18n._('number + name')]
                                    ]
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
                                    xtype: 'combo',
                                    fieldLabel: this.app.i18n._('call_waiting'),
                                    name: 'call_waiting',
                                    id: 'call_waiting',
                                    // @todo add that again
                                    //disabled: _writable.call_waiting,                                    
                                    mode: 'local',
                                    anchor: '95%',
                                    triggerAction: 'all',
                                    editable: false,
                                    forceSelection: true,
                                    value: null,
                                    store: [
                                        [ null,  this.app.i18n._('- factory default -')],                                        
                                        ['on', this.app.i18n._('on')],
                                        ['visual', this.app.i18n._('visual')],
                                        ['ringer', this.app.i18n._('ringer')],
                                        ['off', this.app.i18n._('off')]
                                    ]
                                }]
                            }, {
                                columnWidth: 0.33,
                                layout: 'form',
                                border: false,
                                anchor: '100%',
                                items: [{
                                    xtype: 'combo',
                                    fieldLabel: this.app.i18n._('mwi_notification'),
                                    name: 'mwi_notification',
                                    id: 'mwi_notification',
                                    // @todo add that again
                                    //disabled: _writable.mwi_notification,                                    
                                    mode: 'local',
                                    anchor: '95%',
                                    triggerAction: 'all',
                                    editable: false,
                                    forceSelection: true,
                                    value: null,
                                    store: [
                                        [ null,  this.app.i18n._('- factory default -')],                                        
                                        ['silent', this.app.i18n._('silent')],
                                        ['beep', this.app.i18n._('beep')],
                                        ['reminder', this.app.i18n._('reminder')]
                                    ]
                                }]
                            }, {
                                columnWidth: 0.33,
                                layout: 'form',
                                border: false,
                                anchor: '100%',
                                items: [{
                                    xtype: 'combo',
                                    fieldLabel: this.app.i18n._('mwi_dialtone'),
                                    name: 'mwi_dialtone',
                                    id: 'mwi_dialtone',
                                    // @todo add that again
                                    //disabled: _writable.mwi_dialtone,                                    
                                    mode: 'local',
                                    anchor: '100%',
                                    triggerAction: 'all',
                                    editable: false,
                                    forceSelection: true,
                                    value: null,
                                    store: [
                                        [ null,  this.app.i18n._('- factory default -')],                                        
                                        ['normal', this.app.i18n._('normal')],
                                        ['stutter', this.app.i18n._('stutter')]
                                    ]
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
                                    fieldLabel: this.app.i18n._('headset_device'),
                                    name: 'headset_device',
                                    id: 'headset_device',
                                    // @todo add that again
                                    //disabled: _writable.headset_device,                                    
                                    mode: 'local',
                                    anchor: '95%',
                                    triggerAction: 'all',
                                    editable: false,
                                    forceSelection: true,
                                    value: null,
                                    store: [
                                        [ null,  this.app.i18n._('- factory default -')],                                        
                                        ['none', this.app.i18n._('none')],
                                        ['headset_rj', this.app.i18n._('headset_rj')]
                                    ]
                                }]
                            }, {
                                columnWidth: 0.33,
                                layout: 'form',
                                border: false,
                                anchor: '100%',
                                items: [{
                                        xtype: 'combo',
                                        fieldLabel: this.app.i18n._('message_led_other'),
                                        name: 'message_led_other',
                                        id: 'message_led_other',
                                        // @todo add that again
                                        //disabled: _writable.message_led_other,                                        
                                        mode: 'local',
                                        anchor: '95%',
                                        triggerAction: 'all',
                                        editable: false,
                                        forceSelection: true,
                                        value: null,
                                        store: [
                                            [ null,  this.app.i18n._('- factory default -')],
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
                                    fieldLabel: this.app.i18n._('global_missed_counter'),
                                    name: 'global_missed_counter',
                                    id: 'global_missed_counter',
                                    // @todo add that again
                                    //disabled: _writable.global_missed_counter,                                    
                                    mode: 'local',
                                    anchor: '100%',
                                    triggerAction: 'all',
                                    editable: false,
                                    forceSelection: true,
                                    value: null,
                                    store: [
                                        [ null,  this.app.i18n._('- factory default -')],
                                        ['1', this.app.i18n._('on')], 
                                        ['0', this.app.i18n._('off')]
                                    ]
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
                                    fieldLabel: this.app.i18n._('scroll_outgoing'),
                                    name: 'scroll_outgoing',
                                    id: 'scroll_outgoing',
                                    // @todo add that again
                                    //disabled: _writable.scroll_outgoing,                                    
                                    mode: 'local',
                                    anchor: '95%',
                                    triggerAction: 'all',
                                    editable: false,
                                    forceSelection: true,
                                    value: null,
                                    store: [
                                        [ null,  this.app.i18n._('- factory default -')],
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
                                    fieldLabel: this.app.i18n._('show_local_line'),
                                    name: 'show_local_line',
                                    id: 'show_local_line',
                                    // @todo add that again
                                    //disabled: _writable.show_local_line,                                    
                                    mode: 'local',
                                    anchor: '95%',
                                    triggerAction: 'all',
                                    editable: false,
                                    forceSelection: true,
                                    value: null,
                                    store: [
                                        [ null,  this.app.i18n._('- factory default -')],
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
                                    fieldLabel: this.app.i18n._('show_call_status'),
                                    name: 'show_call_status',
                                    id: 'show_call_status',
                                    // @todo add that again
                                    //disabled: _writable.show_call_status,                                    
                                    mode: 'local',
                                    anchor: '100%',
                                    triggerAction: 'all',
                                    editable: false,
                                    forceSelection: true,
                                    value: null,
                                    store: [
                                        [ null,  this.app.i18n._('- factory default -')],
                                        ['1', this.app.i18n._('on')],
                                        ['0', this.app.i18n._('off')]
                                    ]
                                }]
                            }]
                        }]
                    }]   // form 
                }]   // center       
            }
            // @todo add that again
            /*, 
                this.editPhoneLinesDialog(),
                this.editPhoneOwnerSelection()
            */
            ]
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
        height: 470,
        name: Tine.Voipmanager.SnomPhoneEditDialog.prototype.windowNamePrefix + id,
        contentPanelConstructor: 'Tine.Voipmanager.SnomPhoneEditDialog',
        contentPanelConstructorConfig: config
    });
    return window;
};