/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

Ext.namespace('Tine.Voipmanager.Phones');

Tine.Voipmanager.Phones.Main = {
       
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
            Tine.Tinebase.Common.openWindow('phonesWindow', 'index.php?method=Voipmanager.editPhone&phoneId=', 600, 450);
        },

        /**
         * onclick handler for editPhone
         */
        editPhone: function(_button, _event) 
        {
            var selectedRows = Ext.getCmp('Voipmanager_Phones_Grid').getSelectionModel().getSelections();
            var phoneId = selectedRows[0].id;
            
            Tine.Tinebase.Common.openWindow('phonesWindow', 'index.php?method=Voipmanager.editPhone&phoneId=' + phoneId, 600, 450);
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
            fields: Tine.Voipmanager.Model.Phone,
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
            { resizable: true, id: 'current_software', header: this.translation._('Software'), dataIndex: 'current_software', width: 50 },
            { resizable: true, id: 'ipaddress', header: this.translation._('IP Address'), dataIndex: 'ipaddress', width: 50 },
            { resizable: true, id: 'last_modified_time', header: this.translation._('last modified'), dataIndex: 'last_modified_time', width: 100, hidden: true },
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
                    this.actions.addPhone 
                ]
            });
            contextMenu.showAt(_eventObject.getXY());
        }, this);
        
        gridPanel.on('rowdblclick', function(_gridPar, _rowIndexPar, ePar) {
            var record = _gridPar.getStore().getAt(_rowIndexPar);
            //console.log('id: ' + record.data.id);
            try {
                Tine.Tinebase.Common.openWindow('phonesWindow', 'index.php?method=Voipmanager.editPhone&phoneId=' + record.data.id, 600, 450);
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


Tine.Voipmanager.Phones.EditDialog =  {

        phoneRecord: null,
        
        _templateData: null,
        
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
            this.phoneRecord = new Tine.Voipmanager.Model.Phone(_phoneData);
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
                    window.opener.Tine.Voipmanager.Phones.Main.reload();
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

            if(form.isValid()) {
                form.updateRecord(this.phoneRecord);
                
                var linesStore = Ext.StoreMgr.lookup('Voipmanger_EditPhone_SnomLines');
                var lines = [];
                linesStore.each(function(record) {
                	if(record.data.asteriskline_id != '') {
                        lines.push(record.data);          
                	}
                });
                
                Ext.Ajax.request({
                    params: {
                        method: 'Voipmanager.saveSnomPhone', 
                        phoneData: Ext.util.JSON.encode(this.phoneRecord.data),
                        lineData: Ext.util.JSON.encode(lines)
                    },
                    success: function(_result, _request) {
                        if(window.opener.Tine.Voipmanager.Phones) {
                            window.opener.Tine.Voipmanager.Phones.Main.reload();
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
				_snomRecord = new Tine.Voipmanager.Model.SnomLine({
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
			}			
			
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
				id: 'Voipmanager_Software_Grid',
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
                    frame: true
                },
                items: [gridPanel]
            };
            
            return _phoneLinesDialog;
//			return gridPanel;
        },                
                 
                  
        editPhoneDialog: function(){
        
            var translation = new Locale.Gettext();
            translation.textdomain('Voipmanager');
        
            var _dialog = {
                title: 'Phone',
                layout: 'border',
                anchor: '100% 100%',
                layoutOnTabChange: true,
                defaults: {
                    border: true,
                    frame: true
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
                            columnWidth: .5,
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
                                store: new Ext.data.JsonStore({
                                    storeId: 'Voipmanger_EditPhone_Templates',
                                    id: 'id',
                                    fields: ['id', 'name']
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
                            columnWidth: .5,
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
                                store: Tine.Voipmanager.Data.loadPhoneModelData()
                            }), {
                                xtype: 'textarea',
                                name: 'description',
                                fieldLabel: translation._('Description'),
                                grow: false,
                                preventScrollbars: false,
                                anchor: '100%',
                                height: 85
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
                            title: 'Infos',
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
                                    columnWidth: .5,
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
                                    columnWidth: .5,
                                    layout: 'form',
                                    border: false,
                                    anchor: '100%',
                                    items: [{
                                        xtype: 'datefield',
                                        fieldLabel: translation._('Settings Loaded at'),
                                        name: 'settings_loaded_at',
                                        anchor: '100%',
                                        emptyText: 'never',
                                        format: "d.m.Y H:i:s",
                                        hideTrigger: true,
                                        readOnly: true
                                    }, {
                                        xtype: 'datefield',
                                        fieldLabel: translation._('Firmware last checked at'),
                                        name: 'firmware_checked_at',
                                        anchor: '100%',
                                        emptyText: 'never',
                                        format: "d.m.Y H:i:s",
                                        hideTrigger: true,
                                        readOnly: true
                                    }]
                                }]
                            }]
                        }]
                    }]
                
                }]
            };
            
            return _dialog;   
        },
        
        updateToolbarButtons: function()
        {
            if(this.phoneRecord.get('id') > 0) {
                Ext.getCmp('voipmanager_editPhoneForm').action_delete.enable();
            }
        },
        
        display: function(_phoneData, _snomLines, _lines, _templates, _locations) 
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
                    layout:'fit',
                    border: false,
                    autoHeight: true,
                    anchor: '100% 100%',
                    items: new Ext.TabPanel({
                        plain:true,
                        activeTab: 0,
                        id: 'editPhoneTabPanel',
                        layoutOnTabChange:true,  
                        items:[
                            this.editPhoneDialog(),
                            this.editPhoneLinesDialog(2, _lines, _snomLines)                  
                        ]
                    })
                   
                                        
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


