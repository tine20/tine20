/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

Ext.namespace('Tine.Voipmanager.Snom.Phones');

Tine.Voipmanager.Snom.Phones.Main = {
       
    actions: {
        addPhone: null,
        editPhone: null,
        deletePhone: null
    },
    
    handlers: {
        /**
         * onclick handler for addPhone
         */
        addPhone: function(_button, _event) 
        {
            Tine.Tinebase.Common.openWindow('phonesWindow', 'index.php?method=Voipmanager.editSnomPhone&phoneId=', 700, 450);
        },

        /**
         * onclick handler for editPhone
         */
        editPhone: function(_button, _event) 
        {
            var selectedRows = Ext.getCmp('Voipmanager_Phones_Grid').getSelectionModel().getSelections();
            var phoneId = selectedRows[0].id;
            
            Tine.Tinebase.Common.openWindow('phonesWindow', 'index.php?method=Voipmanager.editSnomPhone&phoneId=' + phoneId, 700, 450);
        },
        
        /**
         * onclick handler for deletePhone
         */
        deletePhone: function(_button, _event) {
            Ext.MessageBox.confirm('Confirm', 'Do you really want to delete the selected phones?', function(_button){
                if (_button == 'yes') {
                
                    var phoneIds = [];
                    
                    var selectedRows = Ext.getCmp('Voipmanager_Phones_Grid').getSelectionModel().getSelections();
                    for (var i = 0; i < selectedRows.length; ++i) {
                        phoneIds.push(selectedRows[i].id);
                    }
                    
                    phoneIds = Ext.util.JSON.encode(phoneIds);
                    
                    Ext.Ajax.request({
                        url: 'index.php',
                        params: {
                            method: 'Voipmanager.deleteSnomPhones',
                            _phoneIds: phoneIds
                        },
                        text: 'Deleting phone(s)...',
                        success: function(_result, _request){
                            Ext.getCmp('Voipmanager_Phones_Grid').getStore().reload();
                        },
                        failure: function(result, request){
                            Ext.MessageBox.alert('Failed', 'Some error occured while trying to delete the phone.');
                        }
                    });
                }
            });
        },
        
        /**
         * onclick handler for resetHttpClientInfo
         */
        resetHttpClientInfo: function(_button, _event) {
            Ext.MessageBox.confirm('Confirm', 'Do you really want to send HTTP Client Info again?', function(_button){
                if (_button == 'yes') {
                
                    var phoneIds = [];
                    
                    var selectedRows = Ext.getCmp('Voipmanager_Phones_Grid').getSelectionModel().getSelections();
                    for (var i = 0; i < selectedRows.length; ++i) {
                        phoneIds.push(selectedRows[i].id);
                    }
                    
                    phoneIds = Ext.util.JSON.encode(phoneIds);
                    
                    Ext.Ajax.request({
                        url: 'index.php',
                        params: {
                            method: 'Voipmanager.resetHttpClientInfo',
                            _phoneIds: phoneIds
                        },
                        text: 'sending HTTP Client Info to phone(s)...',
                        success: function(_result, _request){
                        	// not really needed to reload store
                            //Ext.getCmp('Voipmanager_Phones_Grid').getStore().reload();
                        },
                        failure: function(result, request){
                            Ext.MessageBox.alert('Failed', 'Some error occured while trying to send HTTP Client Info to the phone(s).');
                        }
                    });
                }
            });
        }            
    },
    
    renderer: {
        contactTid: function(_data, _cell, _record, _rowIndex, _columnIndex, _store) {
            //switch(_data) {
            //    default:
                    return "<img src='images/oxygen/16x16/actions/user.png' width='12' height='12' alt='contact'/>";
            //}
        }       
    },

    initComponent: function()
    {
        this.translation = new Locale.Gettext();
        this.translation.textdomain('Voipmanager');
    
        this.actions.addPhone = new Ext.Action({
            text: this.translation._('add phone'),
            handler: this.handlers.addPhone,
            iconCls: 'action_add',
            scope: this
        });
        
        this.actions.editPhone = new Ext.Action({
            text: this.translation._('edit phone'),
            disabled: true,
            handler: this.handlers.editPhone,
            iconCls: 'action_edit',
            scope: this
        });
        
        this.actions.deletePhone = new Ext.Action({
            text: this.translation._('delete phone'),
            disabled: true,
            handler: this.handlers.deletePhone,
            iconCls: 'action_delete',
            scope: this
        });
        
        this.actions.resetHttpClientInfo = new Ext.Action({
           text: this.translation._('send HTTP Client Info'), 
           handler: this.handlers.resetHttpClientInfo,
           iconCls: 'action_resetHttpClientInfo',
           scope: this
        });
    },

    updateMainToolbar : function() 
    {
        var menu = Ext.menu.MenuMgr.get('Tinebase_System_AdminMenu');
        menu.removeAll();
        /*menu.add(
            {text: 'product', handler: Tine.Crm.Main.handlers.editProductSource}
        );*/

        var adminButton = Ext.getCmp('tineMenu').items.get('Tinebase_System_AdminButton');
        adminButton.setIconClass('AddressbookTreePanel');
        //if(Tine.Voipmanager.rights.indexOf('admin') > -1) {
        //    adminButton.setDisabled(false);
        //} else {
            adminButton.setDisabled(true);
        //}

        var preferencesButton = Ext.getCmp('tineMenu').items.get('Tinebase_System_PreferencesButton');
        preferencesButton.setIconClass('VoipmanagerTreePanel');
        preferencesButton.setDisabled(true);
    },
    
    displayPhonesToolbar: function()
    {
        var onFilterChange = function(_field, _newValue, _oldValue){
            // only refresh data on new query strings
            if (_newValue != _oldValue) {
                Ext.getCmp('Voipmanager_Phones_Grid').getStore().load({
                    params: {
                        start: 0,
                        limit: 50
                    }
                });
            }
        };
        
        var quickSearchField = new Ext.ux.SearchField({
            id: 'quickSearchField',
            width: 240
        }); 
        quickSearchField.on('change', onFilterChange, this);
     
        var phoneToolbar = new Ext.Toolbar({
            id: 'Voipmanager_Phones_Toolbar',
            split: false,
            height: 26,
            items: [
                this.actions.addPhone, 
                this.actions.editPhone,
                this.actions.deletePhone,
                '->',
                this.translation._('Search: '), quickSearchField
            ]
        });

        Tine.Tinebase.MainScreen.setActiveToolbar(phoneToolbar);
    },

    displayPhonesGrid: function() 
    {
        // the datastore
        var dataStore = new Ext.data.JsonStore({
            root: 'results',
            totalProperty: 'totalcount',
            id: 'id',
            fields: Tine.Voipmanager.Model.Snom.Phone,
            // turn on remote sorting
            remoteSort: true
        });
        
        dataStore.setDefaultSort('description', 'asc');

        dataStore.on('beforeload', function(_dataStore) {
            _dataStore.baseParams.query = Ext.getCmp('quickSearchField').getRawValue();
        }, this);   
        
        Ext.StoreMgr.add('PhonesStore', dataStore);
        
        // the paging toolbar
        var pagingToolbar = new Ext.PagingToolbar({
            pageSize: 50,
            store: dataStore,
            displayInfo: true,
            displayMsg: this.translation._('Displaying phones {0} - {1} of {2}'),
            emptyMsg: this.translation._("No phones to display")
        }); 
        
        // the columnmodel
        var columnModel = new Ext.grid.ColumnModel([
            { resizable: true, id: 'id', header: this.translation._('Id'), dataIndex: 'id', width: 30, hidden: true },
            { resizable: true, id: 'macaddress', header: this.translation._('MAC address'), dataIndex: 'macaddress',width: 50 },
            { resizable: true, id: 'description', header: this.translation._('description'), dataIndex: 'description' },
            {
                resizable: true,
                id: 'location_id',
                header: this.translation._('Location'),
                dataIndex: 'location_id',
                width: 70,
                renderer: function(_data,_obj, _rec) {
                    return _rec.data.location;
                }
            },
            {
                resizable: true,
                id: 'template_id',
                header: this.translation._('Template'),
                dataIndex: 'template_id',
                width: 70,
                renderer: function(_data,_obj, _rec) {
                    return _rec.data.template;
                }                                
            },            
            { resizable: true, id: 'ipaddress', header: this.translation._('IP Address'), dataIndex: 'ipaddress', width: 50 },            
            { resizable: true, id: 'current_software', header: this.translation._('Software'), dataIndex: 'current_software', width: 50 },
            { resizable: true, id: 'current_model', header: this.translation._('current model'), dataIndex: 'current_model', width: 70, hidden: true },
            { resizable: true, id: 'redirect_event', header: this.translation._('redirect event'), dataIndex: 'redirect_event', width: 70, hidden: true },
            { resizable: true, id: 'redirect_number', header: this.translation._('redirect number'), dataIndex: 'redirect_number', width: 100, hidden: true },
            { resizable: true, id: 'redirect_time', header: this.translation._('redirect time'), dataIndex: 'redirect_time', width: 25, hidden: true },
            { resizable: true, id: 'settings_loaded_at', header: this.translation._('settings loaded at'), dataIndex: 'settings_loaded_at', width: 100, hidden: true },            
            { resizable: true, id: 'last_modified_time', header: this.translation._('last modified'), dataIndex: 'last_modified_time', width: 100, hidden: true }            
        ]);
        
        columnModel.defaultSortable = true; // by default columns are sortable
        
        // the rowselection model
        var rowSelectionModel = new Ext.grid.RowSelectionModel({multiSelect:true});

        rowSelectionModel.on('selectionchange', function(_selectionModel) {
            var rowCount = _selectionModel.getCount();

            if(rowCount < 1) {
                // no row selected
                this.actions.deletePhone.setDisabled(true);
                this.actions.editPhone.setDisabled(true);
            } else if(rowCount > 1) {
                // more than one row selected
                this.actions.deletePhone.setDisabled(false);
                this.actions.editPhone.setDisabled(true);
            } else {
                // only one row selected
                this.actions.deletePhone.setDisabled(false);
                this.actions.editPhone.setDisabled(false);
            }
        }, this);
        
        // the gridpanel
        var gridPanel = new Ext.grid.GridPanel({
            id: 'Voipmanager_Phones_Grid',
            store: dataStore,
            cm: columnModel,
            tbar: pagingToolbar,     
            autoSizeColumns: false,
            selModel: rowSelectionModel,
            enableColLock:false,
            loadMask: true,
            autoExpandColumn: 'description',
            border: false,
            view: new Ext.grid.GridView({
                autoFill: true,
                forceFit:true,
                ignoreAdd: true,
                emptyText: 'No phones to display'
            })            
            
        });
        
        gridPanel.on('rowcontextmenu', function(_grid, _rowIndex, _eventObject) {
            _eventObject.stopEvent();
            if(!_grid.getSelectionModel().isSelected(_rowIndex)) {
                _grid.getSelectionModel().selectRow(_rowIndex);
            }
            var contextMenu = new Ext.menu.Menu({
                id:'ctxMenuPhones', 
                items: [
                    this.actions.editPhone,
                    this.actions.deletePhone,
                    '-',
                    this.actions.addPhone,
                    '-',
                    this.actions.resetHttpClientInfo 
                ]
            });
            contextMenu.showAt(_eventObject.getXY());
        }, this);
        
        gridPanel.on('rowdblclick', function(_gridPar, _rowIndexPar, ePar) {
            var record = _gridPar.getStore().getAt(_rowIndexPar);
            //console.log('id: ' + record.data.id);
            try {
                Tine.Tinebase.Common.openWindow('phonesWindow', 'index.php?method=Voipmanager.editSnomPhone&phoneId=' + record.data.id, 700, 450);
            } catch(e) {
                // alert(e);
            }
        }, this);

        gridPanel.on('keydown', function(e){
             if(e.getKey() == e.DELETE && Ext.getCmp('Voipmanager_Phones_Grid').getSelectionModel().getCount() > 0){
                 this.handlers.deletePhone();
             }
        }, this);

        // add the grid to the layout
        Tine.Tinebase.MainScreen.setActiveContentPanel(gridPanel);
    },
    
    /**
     * update datastore with node values and load datastore
     */
    loadData: function(_node)
    {
        var dataStore = Ext.getCmp('Voipmanager_Phones_Grid').getStore();

        dataStore.baseParams.method = 'Voipmanager.getSnomPhones';

        dataStore.load({
            params:{
                start:0, 
                limit:50 
            }
        });
    },

    show: function(_node) 
    {
        var currentToolbar = Tine.Tinebase.MainScreen.getActiveToolbar();

        if(currentToolbar === false || currentToolbar.id != 'Voipmanager_Phones_Toolbar') {
            this.initComponent();
            this.displayPhonesToolbar();
            this.displayPhonesGrid();
            this.updateMainToolbar();
        }
        this.loadData(_node);
    },
    
    reload: function() 
    {
        if(Ext.ComponentMgr.all.containsKey('Voipmanager_Phones_Grid')) {
            setTimeout ("Ext.getCmp('Voipmanager_Phones_Grid').getStore().reload()", 200);
        }
    }
};


Tine.Voipmanager.Snom.Phones.EditDialog =  {

        phoneRecord: null,
        
        settingsRecord: null,
        
        _templateData: null,
        
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
        
        
        updatePhoneRecord: function(_phoneData)
        {                     
            if(_phoneData.last_modified_time && _phoneData.last_modified_time !== null) {
                _phoneData.last_modified_time = Date.parseDate(_phoneData.last_modified_time, 'c');
            }
            if(_phoneData.settings_loaded_at && _phoneData.settings_loaded_at !== null) {
                _phoneData.settings_loaded_at = Date.parseDate(_phoneData.settings_loaded_at, 'c');
            }
            if(_phoneData.firmware_checked_at && _phoneData.firmware_checked_at !== null) {
                _phoneData.firmware_checked_at = Date.parseDate(_phoneData.firmware_checked_at, 'c');
            }
            if(_phoneData.redirect_event != 'time') {
                Ext.getCmp('redirect_time').disable();
            }
            
            this.phoneRecord = new Tine.Voipmanager.Model.Snom.Phone(_phoneData);

        },
        
        
        deletePhone: function(_button, _event)
        {
            var phoneIds = Ext.util.JSON.encode([this.phoneRecord.get('id')]);
                
            Ext.Ajax.request({
                url: 'index.php',
                params: {
                    method: 'Voipmanager.deleteSnomPhones', 
                    phoneIds: phoneIds
                },
                text: 'Deleting phone...',
                success: function(_result, _request) {
                    window.opener.Tine.Voipmanager.Snom.Phones.Main.reload();
                    window.close();
                },
                failure: function ( result, request) { 
                    Ext.MessageBox.alert('Failed', 'Some error occured while trying to delete the phone.'); 
                } 
            });         
        },
        
        applyChanges: function(_button, _event, _closeWindow) 
        {
            var form = Ext.getCmp('voipmanager_editPhoneForm').getForm();

            var _settingId = Ext.getCmp('template_id').store.getById(Ext.getCmp('template_id').getValue());
            _settingId = _settingId.data.setting_id;

            if(form.isValid()) {
                form.updateRecord(this.phoneRecord);
                
                var linesStore = Ext.StoreMgr.lookup('Voipmanger_EditPhone_SnomLines');
                var lines = [];
                linesStore.each(function(record) {
                	if(record.data.asteriskline_id != '') {
                        lines.push(record.data);          
                	}
                });
                
                var rightsStore = Ext.getCmp('phoneUsersGrid').getStore();
                var rights = [];

                rightsStore.each(function(record) {
                    rights.push(record.data);   
                });
                
                
                Ext.Ajax.request({
                    params: {
                        method: 'Voipmanager.saveSnomPhone', 
                        phoneData: Ext.util.JSON.encode(this.phoneRecord.data),
                        lineData: Ext.util.JSON.encode(lines),
                        rightsData: Ext.util.JSON.encode(rights)
                    },
                    success: function(_result, _request) {
                        if(window.opener.Tine.Voipmanager.Snom.Phones) {
                            window.opener.Tine.Voipmanager.Snom.Phones.Main.reload();
                        }
                        if(_closeWindow === true) {
                            window.close();
                        } else {
                            this.updatePhoneRecord(Ext.util.JSON.decode(_result.responseText).updatedData);
                            this.updateToolbarButtons();
                            form.loadRecord(this.phoneRecord);
                        }
                    },
                    failure: function ( result, request) { 
                        Ext.MessageBox.alert('Failed', 'Could not save phone.'); 
                    },
                    scope: this 
                });
            } else {
                Ext.MessageBox.alert('Errors', 'Please fix the errors noted.');
            }
        },

        saveChanges: function(_button, _event) 
        {
            this.applyChanges(_button, _event, true);
        },
                
                
        editPhoneLinesDialog: function(_maxLines, _lines, _snomLines) {
            
            var translation = new Locale.Gettext();
            translation.textdomain('Voipmanager');
            
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
		       header: translation._('lineactive'),
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
					header: translation._('sipCombo'),
					dataIndex: 'asteriskline_id',
					width: 80,
					editor: combo,
				    renderer: Ext.ux.comboBoxRenderer(combo)
				},
	            {
					resizable: true,
					id: 'idletext',
					header: translation._('Idle Text'),
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
                items: [gridPanel]
            };
            
            return _phoneLinesDialog;
//			return gridPanel;
        },                
                 
                  
        editPhoneDialog: function(_phoneData, _maxLines){
        
            var translation = new Locale.Gettext();
            translation.textdomain('Voipmanager');
        
   
            var _dialog = {
                title: 'Phone',
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
                            items: [{
                                xtype: 'textfield',
                                fieldLabel: translation._('MAC Address'),
                                name: 'macaddress',
                                maxLength: 12,
                                anchor: '98%',
                                allowBlank: false
                            }, {
                                xtype: 'combo',
                                fieldLabel: translation._('Template'),
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
                                },
                                store: new Ext.data.JsonStore({
                                    storeId: 'Voipmanger_EditPhone_Templates',
                                    id: 'id',
                                    fields: ['id', 'name', 'setting_id']
                                })
                            }, {
                                xtype: 'combo',
                                fieldLabel: translation._('Location'),
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
                            }]
                        }, {
                            columnWidth: 0.5,
                            layout: 'form',
                            border: false,
                            anchor: '98%',
                            autoHeight: true,
                            items: [new Ext.form.ComboBox({
                                fieldLabel: translation._('Phone Model'),
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
                                },
                                store: Tine.Voipmanager.Data.loadPhoneModelData()
                            }), {
                                xtype: 'textarea',
                                name: 'description',
                                fieldLabel: translation._('Description'),
                                grow: false,
                                preventScrollbars: false,
                                anchor: '100%',
                                height: 70
                            }]
                        }]
                    }, {
                        layout: 'form',
                        border: false,
                        anchor: '100%',
                        items: [{
                            xtype: 'fieldset',
                            checkboxToggle: false,
                            id: 'infos',
                            title: translation._('infos'),
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
                                        fieldLabel: translation._('Current IP Address'),
                                        name: 'ipaddress',
                                        maxLength: 20,
                                        anchor: '98%',
                                        readOnly: true
                                    }, {
                                        xtype: 'textfield',
                                        fieldLabel: translation._('Current Software Version'),
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
                                        fieldLabel: translation._('Settings Loaded at'),
                                        name: 'settings_loaded_at',
                                        anchor: '100%',
                                        emptyText: 'never',
                                        hideTrigger: true,
                                        readOnly: true
                                    }, {
                                        xtype: 'datetimefield',
                                        fieldLabel: translation._('Firmware last checked at'),
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
                        title: translation._('redirection'),
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
                                    fieldLabel: translation._('redirect_event'),
                                    name: 'redirect_event',
                                    id: 'redirect_event',                                                                       
                                    mode: 'local',
                                    displayField: 'name',
                                    valueField: 'id',
                                    anchor: '95%',
                                    triggerAction: 'all',
                                    editable: false,
                                    forceSelection: true,
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
                                    store: new Ext.data.SimpleStore({
                                        id: 'id',
                                        fields: ['id', 'name'],
                                        data: [
                                            ['all', translation._('all')],
                                            ['busy', translation._('busy')],
                                            ['none', translation._('none')],
                                            ['time', translation._('time')]
                                        ]
                                    })
                                }]
                            }, {
                                columnWidth: 0.33,
                                layout: 'form',
                                border: false,
                                anchor: '100%',
                                items: [{
                                    xtype: 'textfield',
                                    fieldLabel: translation._('redirect_number'),
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
                                    fieldLabel: translation._('redirect_time'),
                                    name: 'redirect_time',
                                    id: 'redirect_time',
                                    anchor: '100%'                                                                     
                               }]
                            }]  
                        }]
                    }]
                }]
            };
            
            return _dialog;   
        },
        
    handlers: {
        removeAccount: function(_button, _event) 
        {         	
            var accountsGrid = Ext.getCmp('phoneUsersGrid');
            var selectedRows = accountsGrid.getSelectionModel().getSelections();
            
            var accountsStore = this.dataStore;
            for (var i = 0; i < selectedRows.length; ++i) {
                accountsStore.remove(selectedRows[i]);
            }             
        },
        
        addAccount: function(account)
        {        	
            var accountsGrid = Ext.getCmp('phoneUsersGrid');
            
            var dataStore = accountsGrid.getStore();
            var selectionModel = accountsGrid.getSelectionModel();
            
            // check if exists
            var record = dataStore.getById(account.data.id);
            
            if (!record) {
            	var record = new Ext.data.Record({
                    account_id: account.data.id,
                    account_type: account.data.type,
                    accountDisplayName: account.data.name
                }, account.data.id);
                dataStore.addSorted(record);
            }
            selectionModel.selectRow(dataStore.indexOfId(account.data.account_id));   
        },

        applyChanges: function(_button, _event, _closeWindow) 
        {
        	Ext.MessageBox.wait('Please wait', 'Updating Rights');
        	
        	var dlg = Ext.getCmp('adminApplicationEditPermissionsDialog');
            var accountsGrid = Ext.getCmp('phoneUsersGrid');            
            var dataStore = accountsGrid.getStore();
            
            var rights = [];
            dataStore.each(function(_record){
            	rights.push(_record.data);
            });
            
            Ext.Ajax.request({
                params: {
                    method: 'Admin.saveApplicationPermissions', 
                    applicationId: dlg.applicationId,
                    rights: Ext.util.JSON.encode(rights)
                },
                success: function(_result, _request) {
                    if(_closeWindow === true) {
                        window.close();
                    } else {
                        Ext.MessageBox.hide();
                    }
                },
                failure: function ( result, request) { 
                    Ext.MessageBox.alert('Failed', 'Could not save group.'); 
                },
                scope: this 
            });

        },

        saveAndClose: function(_button, _event) 
        {
            this.handlers.applyChanges(_button, _event, true);
        }
     },		
		
		
        editPhoneOwnerSelection: function(_groupMembers){
        
            var translation = new Locale.Gettext();
            translation.textdomain('Voipmanager');
		
 		    /******* actions ********/

	    	this.actions = {
	            addAccount: new Ext.Action({
	                text: translation._('add account'),
	                disabled: true,
	                scope: this,
	                handler: this.handlers.addAccount,
	                iconCls: 'action_addContact'
	            }),
	            removeAccount: new Ext.Action({
	                text: translation._('remove account'),
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
	        	resizable: true, id: 'accountDisplayName', header: translation._('Name'), dataIndex: 'accountDisplayName', width: 30 
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
	            title: translation._('Owner'),
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
	        

	        
	        var editGroupDialog = {
	            layout:'border',
                title: translation._('Users'),
	            border:false,
	            width: 600,
	            height: 500,
	            items:[
		            accountPicker, 
		            phoneUsersGridPanel
	            ]
	        };            
            
            return editGroupDialog;   
        },
 		
        
       editPhoneSettingsDialog: function(_writable){
        
            var translation = new Locale.Gettext();
            translation.textdomain('Voipmanager');
            
            Ext.QuickTips.init();                         
            
        
            var _dialog = {
                title: translation._('Settings'),
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
                                    fieldLabel: translation._('web_language'),
                                    name: 'web_language',
                                    id: 'web_language',
                                    disabled: _writable.web_language,
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
                                            [ null,  translation._('- factory default -')],                                        
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
                                    xtype: 'combo',
                                    fieldLabel: translation._('language'),
                                    name: 'language',
                                    id: 'language',
                                    disabled: _writable.language,                                    
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
                                            [ null,  translation._('- factory default -')],                                                                                               
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
                                    xtype: 'combo',
                                    fieldLabel: translation._('display_method'),
                                    name: 'display_method',
                                    id: 'display_method',
                                    disabled: _writable.display_method,                                    
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
                                            [ null,  translation._('- factory default -')],                                                                                
                                            ['full_contact', translation._('whole url')],
                                            ['display_name', translation._('name')],
                                            ['display_number', translation._('number')],
                                            ['display_name_number', translation._('name + number')],
                                            ['display_number_name', translation._('number + name')]
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
                                    xtype: 'combo',
                                    fieldLabel: translation._('call_waiting'),
                                    name: 'call_waiting',
                                    id: 'call_waiting',
                                    disabled: _writable.call_waiting,                                    
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
                                            [ null,  translation._('- factory default -')],                                        
                                            ['on', translation._('on')],
                                            ['visual', translation._('visual')],
                                            ['ringer', translation._('ringer')],
                                            ['off', translation._('off')]
                                        ]
                                    })
                                }]
                            }, {
                                columnWidth: 0.33,
                                layout: 'form',
                                border: false,
                                anchor: '100%',
                                items: [{
                                    xtype: 'combo',
                                    fieldLabel: translation._('mwi_notification'),
                                    name: 'mwi_notification',
                                    id: 'mwi_notification',
                                    disabled: _writable.mwi_notification,                                    
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
                                            [ null,  translation._('- factory default -')],                                        
                                            ['silent', translation._('silent')],
                                            ['beep', translation._('beep')],
                                            ['reminder', translation._('reminder')]
                                        ]
                                    })
                                }]
                            }, {
                                columnWidth: 0.33,
                                layout: 'form',
                                border: false,
                                anchor: '100%',
                                items: [{
                                    xtype: 'combo',
                                    fieldLabel: translation._('mwi_dialtone'),
                                    name: 'mwi_dialtone',
                                    id: 'mwi_dialtone',
                                    disabled: _writable.mwi_dialtone,                                    
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
                                            [ null,  translation._('- factory default -')],                                        
                                            ['normal', translation._('normal')],
                                            ['stutter', translation._('stutter')]
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
                                    xtype: 'combo',
                                    fieldLabel: translation._('headset_device'),
                                    name: 'headset_device',
                                    id: 'headset_device',
                                    disabled: _writable.headset_device,                                    
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
                                            [ null,  translation._('- factory default -')],                                        
                                            ['none', translation._('none')],
                                            ['headset_rj', translation._('headset_rj')]
                                        ]
                                    })
                                }]
                            }, {
                                columnWidth: 0.33,
                                layout: 'form',
                                border: false,
                                anchor: '100%',
                                items: [{
                                        xtype: 'combo',
                                        fieldLabel: translation._('message_led_other'),
                                        name: 'message_led_other',
                                        id: 'message_led_other',
                                        disabled: _writable.message_led_other,                                        
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
                                                [ null,  translation._('- factory default -')],
                                                ['1', translation._('on')],
                                                ['0', translation._('off')]
                                            ]
                                        })
                                }]
                            }, {
                                columnWidth: 0.33,
                                layout: 'form',
                                border: false,
                                anchor: '100%',
                                items: [{
                                    xtype: 'combo',
                                    fieldLabel: translation._('global_missed_counter'),
                                    name: 'global_missed_counter',
                                    id: 'global_missed_counter',
                                    disabled: _writable.global_missed_counter,                                    
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
                                            [ null,  translation._('- factory default -')],
                                            ['1', translation._('on')], 
                                            ['0', translation._('off')]
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
                                    xtype: 'combo',
                                    fieldLabel: translation._('scroll_outgoing'),
                                    name: 'scroll_outgoing',
                                    id: 'scroll_outgoing',                                  
                                    disabled: _writable.scroll_outgoing,                                    
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
                                            [ null,  translation._('- factory default -')],
                                            ['1', translation._('on')],
                                            ['0', translation._('off')]
                                        ]
                                    })
                                }]
                            }, {
                                columnWidth: 0.33,
                                layout: 'form',
                                border: false,
                                anchor: '100%',
                                items: [{
                                    xtype: 'combo',
                                    fieldLabel: translation._('show_local_line'),
                                    name: 'show_local_line',
                                    id: 'show_local_line',                                  
                                    disabled: _writable.show_local_line,                                    
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
                                            [ null,  translation._('- factory default -')],
                                            ['1', translation._('on')],
                                            ['0', translation._('off')]
                                        ]
                                    })
                                }]
                            }, {
                                columnWidth: 0.33,
                                layout: 'form',
                                border: false,
                                anchor: '100%',
                                items: [{
                                    xtype: 'combo',
                                    fieldLabel: translation._('show_call_status'),
                                    name: 'show_call_status',
                                    id: 'show_call_status',
                                    disabled: _writable.show_call_status,                                    
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
                                            [ null,  translation._('- factory default -')],
                                            ['1', translation._('on')],
                                            ['0', translation._('off')]
                                        ]
                                    })
                                }]
                            }]
                        }]
                    }]   // form 
                }]   // center
            };
            
            return _dialog;   
        },        
		              
        updateToolbarButtons: function()
        {
            if(this.phoneRecord.get('id') > 0) {
                Ext.getCmp('voipmanager_editPhoneForm').action_delete.enable();
            }
        },
        
        display: function(_phoneData, _snomLines, _lines, _templates, _locations, _writable) 
        {
            // Ext.FormPanel
            var dialog = new Tine.widgets.dialog.EditRecord({
                id : 'voipmanager_editPhoneForm',
                //title: 'the title',
                labelWidth: 120,
                labelAlign: 'top',
                handlerScope: this,
                handlerApplyChanges: this.applyChanges,
                handlerSaveAndClose: this.saveChanges,
                handlerDelete: this.deletePhone,
                items: [{
                    defaults: {
                        frame: true
                    },
                	xtype: 'tabpanel',
                    border: false,
                    height: 100,
                    anchor: '100% 100%',
                    plain: true,
                    activeTab: 0,
                    id: 'editPhoneTabPanel',
                    layoutOnTabChange: true, 
                    deferredRender: false,                                   
                    items:[
                        this.editPhoneDialog(_phoneData,this._maxLines()),
                        this.editPhoneSettingsDialog(_writable),   
                        this.editPhoneLinesDialog(this._maxLines(_phoneData.current_model), _lines, _snomLines),
                        this.editPhoneOwnerSelection((_phoneData.rights ? _phoneData.rights : {}))
                    ]
                }]
            });

            Ext.StoreMgr.lookup('Voipmanger_EditPhone_Templates').loadData(_templates);
            Ext.StoreMgr.lookup('Voipmanger_EditPhone_Locations').loadData(_locations);
            
            var viewport = new Ext.Viewport({
                layout: 'border',
                frame: true,
                //height: 300,
                items: dialog
            });
               
            this.updatePhoneRecord(_phoneData);
            this.updateToolbarButtons();           
            dialog.getForm().loadRecord(this.phoneRecord);
        } 
};


