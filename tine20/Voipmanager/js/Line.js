/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

    
Ext.namespace('Tine.Voipmanager.Lines');

Tine.Voipmanager.Lines.Main = {
       
    actions: {
        addLine: null,
        editLine: null,
        deleteLine: null
    },
    
    handlers: {
        /**
         * onclick handler for addLine
         */
        addLine: function(_button, _event) 
        {
            Tine.Tinebase.Common.openWindow('linesWindow', 'index.php?method=Voipmanager.editLine&lineId=', 750, 600);
        },

        /**
         * onclick handler for editLine
         */
        editLine: function(_button, _event) 
        {
            var selectedRows = Ext.getCmp('Voipmanager_Lines_Grid').getSelectionModel().getSelections();
            var lineId = selectedRows[0].id;
            
            Tine.Tinebase.Common.openWindow('linesWindow', 'index.php?method=Voipmanager.editLine&lineId=' + lineId, 750, 600);
        },
        
        /**
         * onclick handler for deleteLine
         */
        deleteLine: function(_button, _event) {
            Ext.MessageBox.confirm('Confirm', 'Do you really want to delete the selected lines?', function(_button){
                if (_button == 'yes') {
                
                    var lineIds = [];
                    var selectedRows = Ext.getCmp('Voipmanager_Lines_Grid').getSelectionModel().getSelections();
                    for (var i = 0; i < selectedRows.length; ++i) {
                        lineIds.push(selectedRows[i].id);
                    }
                    
                    lineIds = Ext.util.JSON.encode(lineIds);
                    
                    Ext.Ajax.request({
                        url: 'index.php',
                        params: {
                            method: 'Voipmanager.deleteLines',
                            _lineIds: lineIds
                        },
                        text: 'Deleting line(s)...',
                        success: function(_result, _request){
                            Ext.getCmp('Voipmanager_Lines_Grid').getStore().reload();
                        },
                        failure: function(result, request){
                            Ext.MessageBox.alert('Failed', 'Some error occured while trying to delete the line.');
                        }
                    });
                }
            });
        }    
    },
    

    initComponent: function()
    {
        this.translation = new Locale.Gettext();
        this.translation.textdomain('Voipmanager');
    
        this.actions.addLine = new Ext.Action({
            text: this.translation._('add line'),
            handler: this.handlers.addLine,
            iconCls: 'action_add',
            scope: this
        });
        
        this.actions.editLine = new Ext.Action({
            text: this.translation._('edit line'),
            disabled: true,
            handler: this.handlers.editLine,
            iconCls: 'action_edit',
            scope: this
        });
        
        this.actions.deleteLine = new Ext.Action({
            text: this.translation._('delete line'),
            disabled: true,
            handler: this.handlers.deleteLine,
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
    
    displayLinesToolbar: function()
    {
        var onFilterChange = function(_field, _newValue, _oldValue){
            // only refresh data on new query strings
            if (_newValue != _oldValue) {
                Ext.getCmp('Voipmanager_Lines_Grid').getStore().load({
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
     
        var lineToolbar = new Ext.Toolbar({
            id: 'Voipmanager_Lines_Toolbar',
            split: false,
            height: 26,
            items: [
                this.actions.addLine, 
                this.actions.editLine,
                this.actions.deleteLine,
                '->',
                this.translation._('Search: '), quickSearchField
            ]
        });

        Tine.Tinebase.MainScreen.setActiveToolbar(lineToolbar);
    },

    displayLinesGrid: function() 
    {
        // the datastore
        var dataStore = new Ext.data.JsonStore({
            root: 'results',
            totalProperty: 'totalcount',
            id: 'id',
            fields: Tine.Voipmanager.Model.Line,
            // turn on remote sorting
            remoteSort: true
        });
        
        dataStore.setDefaultSort('name', 'asc');

        dataStore.on('beforeload', function(_dataStore) {
            _dataStore.baseParams.query = Ext.getCmp('quickSearchField').getRawValue();
        }, this);   
        
        Ext.StoreMgr.add('LinesStore', dataStore);
        
        // the paging toolbar
        var pagingToolbar = new Ext.PagingToolbar({
            pageSize: 50,
            store: dataStore,
            displayInfo: true,
            displayMsg: this.translation._('Displaying lines {0} - {1} of {2}'),
            emptyMsg: this.translation._("No lines to display")
        }); 
        
        // the columnmodel
        var columnModel = new Ext.grid.ColumnModel([
            { resizable: true, id: 'id', header: this.translation._('Id'), dataIndex: 'id', width: 30, hidden: true },
            { resizable: true, id: 'name', header: this.translation._('name'), dataIndex: 'name', width: 60 },
            { resizable: true, id: 'accountcode', header: this.translation._('ASTERISK_LINES_account code'), dataIndex: 'accountcode', width: 30, hidden: true },
            { resizable: true, id: 'amaflags', header: this.translation._('ASTERISK_LINES_ama flags'), dataIndex: 'amaflags', width: 30, hidden: true },
            { resizable: true, id: 'callgroup', header: this.translation._('ASTERISK_LINES_call group'), dataIndex: 'callgroup', width: 30, hidden: true },
            { resizable: true, id: 'callerid', header: this.translation._('ASTERISK_LINES_caller id'), dataIndex: 'callerid', width: 80 },
            { resizable: true, id: 'canreinvite', header: this.translation._('ASTERISK_LINES_can reinvite'), dataIndex: 'canreinvite', width: 30, hidden: true },
            { resizable: true, id: 'context', header: this.translation._('ASTERISK_LINES_context'), dataIndex: 'context', width: 100 },
            { resizable: true, id: 'defaultip', header: this.translation._('ASTERISK_LINES_default ip'), dataIndex: 'defaultip', width: 30, hidden: true },
            { resizable: true, id: 'dtmfmode', header: this.translation._('ASTERISK_LINES_dtmf mode'), dataIndex: 'dtmfmode', width: 30, hidden: true },
            { resizable: true, id: 'fromuser', header: this.translation._('ASTERISK_LINES_from user'), dataIndex: 'fromuser', width: 30, hidden: true },
            { resizable: true, id: 'fromdomain', header: this.translation._('ASTERISK_LINES_from domain'), dataIndex: 'fromdomain  	', width: 30, hidden: true },
            { resizable: true, id: 'fullcontact', header: this.translation._('ASTERISK_LINES_full contact'), dataIndex: 'fullcontact', width: 200, hidden: true },
            { resizable: true, id: 'host', header: this.translation._('ASTERISK_LINES_host'), dataIndex: 'host', width: 30, hidden: true },
            { resizable: true, id: 'insecure', header: this.translation._('ASTERISK_LINES_insecure'), dataIndex: 'insecure', width: 30, hidden: true },
            { resizable: true, id: 'language', header: this.translation._('ASTERISK_LINES_language'), dataIndex: 'language', width: 30, hidden: true },
            { resizable: true, id: 'mailbox', header: this.translation._('ASTERISK_LINES_mailbox'), dataIndex: 'mailbox', width: 50},
            { resizable: true, id: 'md5secret', header: this.translation._('ASTERISK_LINES_md5 secret'), dataIndex: 'md5secret', width: 30, hidden: true },
            { resizable: true, id: 'nat', header: this.translation._('ASTERISK_LINES_nat'), dataIndex: 'nat', width: 30, hidden: true },
            { resizable: true, id: 'deny', header: this.translation._('ASTERISK_LINES_deny'), dataIndex: 'deny', width: 30, hidden: true },
            { resizable: true, id: 'permit', header: this.translation._('ASTERISK_LINES_permit'), dataIndex: 'permit', width: 30, hidden: true },
            { resizable: true, id: 'mask', header: this.translation._('ASTERISK_LINES_mask'), dataIndex: 'mask', width: 30, hidden: true },
            { resizable: true, id: 'pickupgroup', header: this.translation._('ASTERISK_LINES_pickup group'), dataIndex: 'pickupgroup', width: 30 },
            { resizable: true, id: 'port', header: this.translation._('ASTERISK_LINES_port'), dataIndex: 'port', width: 30, hidden: true },
            { resizable: true, id: 'qualify', header: this.translation._('ASTERISK_LINES_qualify'), dataIndex: 'qualify', width: 30, hidden: true },
            { resizable: true, id: 'restrictcid', header: this.translation._('ASTERISK_LINES_retrict cid'), dataIndex: 'restrictcid', width: 30, hidden: true },
            { resizable: true, id: 'rtptimeout', header: this.translation._('ASTERISK_LINES_rtp timeout'), dataIndex: 'rtptimeout', width: 30, hidden: true },
            { resizable: true, id: 'rtpholdtimeout', header: this.translation._('ASTERISK_LINES_rtp hold timeout'), dataIndex: 'rtpholdtimeout', width: 30, hidden: true },
            { resizable: true, id: 'secret', header: this.translation._('ASTERISK_LINES_secret'), dataIndex: 'secret', width: 30, hidden: true },
            { resizable: true, id: 'type', header: this.translation._('ASTERISK_LINES_type'), dataIndex: 'type', width: 30 },
            { resizable: true, id: 'username', header: this.translation._('ASTERISK_LINES_username'), dataIndex: 'username', width: 30, hidden: true },
            { resizable: true, id: 'disallow', header: this.translation._('ASTERISK_LINES_disallow'), dataIndex: 'disallow', width: 30, hidden: true },
            { resizable: true, id: 'allow', header: this.translation._('ASTERISK_LINES_allow'), dataIndex: 'allow', width: 30, hidden: true },
            { resizable: true, id: 'musiconhold', header: this.translation._('ASTERISK_LINES_music on hold'), dataIndex: 'musiconhold', width: 30, hidden: true },
            { resizable: true, id: 'regseconds', header: this.translation._('ASTERISK_LINES_reg seconds'), dataIndex: 'regseconds', width: 30 },
            { resizable: true, id: 'ipaddr', header: this.translation._('ASTERISK_LINES_ip address'), dataIndex: 'ipaddr', width: 30, hidden: true },
            { resizable: true, id: 'regexten', header: this.translation._('ASTERISK_LINES_reg exten'), dataIndex: 'regexten', width: 30, hidden: true },
            { resizable: true, id: 'cancallforward', header: this.translation._('ASTERISK_LINES_can call forward'), dataIndex: 'cancallforward', width: 30, hidden: true },
            { resizable: true, id: 'setvar', header: this.translation._('ASTERISK_LINES_set var'), dataIndex: 'setvar', width: 30, hidden: true },
            { resizable: true, id: 'notifyringing', header: this.translation._('ASTERISK_LINES_notify ringing'), dataIndex: 'notifyringing', width: 30, hidden: true },
            { resizable: true, id: 'useclientcode', header: this.translation._('ASTERISK_LINES_use client code'), dataIndex: 'useclientcode', width: 30, hidden: true },
            { resizable: true, id: 'authuser', header: this.translation._('ASTERISK_LINES_auth user'), dataIndex: 'authuser', width: 30, hidden: true },
            { resizable: true, id: 'call-limit', header: this.translation._('ASTERISK_LINES_call limit'), dataIndex: 'call-limit', width: 30, hidden: true },
            { resizable: true, id: 'busy-level', header: this.translation._('ASTERISK_LINES_busy level'), dataIndex: 'busy-level', width: 30, hidden: true }
        ]);
        
        columnModel.defaultSortable = true; // by default columns are sortable
        
        // the rowselection model
        var rowSelectionModel = new Ext.grid.RowSelectionModel({multiSelect:true});

        rowSelectionModel.on('selectionchange', function(_selectionModel) {
            var rowCount = _selectionModel.getCount();

            if(rowCount < 1) {
                // no row selected
                this.actions.deleteLine.setDisabled(true);
                this.actions.editLine.setDisabled(true);
            } else if(rowCount > 1) {
                // more than one row selected
                this.actions.deleteLine.setDisabled(false);
                this.actions.editLine.setDisabled(true);
            } else {
                // only one row selected
                this.actions.deleteLine.setDisabled(false);
                this.actions.editLine.setDisabled(false);
            }
        }, this);
        
        // the gridpanel
        var gridPanel = new Ext.grid.GridPanel({
            id: 'Voipmanager_Lines_Grid',
            store: dataStore,
            cm: columnModel,
            tbar: pagingToolbar,     
            autoSizeColumns: false,
            selModel: rowSelectionModel,
            enableColLock:false,
            loadMask: true,
            autoExpandColumn: 'callerid',
            border: false,
            view: new Ext.grid.GridView({
                autoFill: true,
                forceFit:true,
                ignoreAdd: true,
                emptyText: this.translation._('No lines to display')
            })            
            
        });
        
        gridPanel.on('rowcontextmenu', function(_grid, _rowIndex, _eventObject) {
            _eventObject.stopEvent();
            if(!_grid.getSelectionModel().isSelected(_rowIndex)) {
                _grid.getSelectionModel().selectRow(_rowIndex);
            }
            var contextMenu = new Ext.menu.Menu({
                id:'ctxMenuLines', 
                items: [
                    this.actions.editLine,
                    this.actions.deleteLine,
                    '-',
                    this.actions.addLine 
                ]
            });
            contextMenu.showAt(_eventObject.getXY());
        }, this);
        
        gridPanel.on('rowdblclick', function(_gridPar, _rowIndexPar, ePar) {
            var record = _gridPar.getStore().getAt(_rowIndexPar);
            //console.log('id: ' + record.data.id);
            try {
                Tine.Tinebase.Common.openWindow('linesWindow', 'index.php?method=Voipmanager.editLine&lineId=' + record.data.id, 750, 600);
            } catch(e) {
                // alert(e);
            }
        }, this);

        gridPanel.on('keydown', function(e){
             if(e.getKey() == e.DELETE && Ext.getCmp('Voipmanager_Lines_Grid').getSelectionModel().getCount() > 0){
                 this.handlers.deleteLine();
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
        var dataStore = Ext.getCmp('Voipmanager_Lines_Grid').getStore();
    
        dataStore.baseParams.method = 'Voipmanager.getLines';
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

        if(currentToolbar === false || currentToolbar.id != 'Voipmanager_Lines_Toolbar') {
            this.initComponent();
            this.displayLinesToolbar();
            this.displayLinesGrid();
            this.updateMainToolbar();
        }
        this.loadData(_node);
    },
    
    reload: function() 
    {
        if(Ext.ComponentMgr.all.containsKey('Voipmanager_Lines_Grid')) {
            setTimeout ("Ext.getCmp('Voipmanager_Lines_Grid').getStore().reload()", 200);
        }
    }
};

Tine.Voipmanager.Lines.EditDialog =  {

    lineRecord: null,
    
    updateLineRecord: function(_lineData)
    {   
        this.lineRecord = new Tine.Voipmanager.Model.Line(_lineData);
    },
    
    
    deleteLine: function(_button, _event)
    {
        var lineIds = Ext.util.JSON.encode([this.lineRecord.get('id')]);
            
        Ext.Ajax.request({
            url: 'index.php',
            params: {
                method: 'Voipmanager.deleteLines', 
                lineIds: lineIds
            },
            text: 'Deleting line...',
            success: function(_result, _request) {
                window.opener.Tine.Voipmanager.Lines.Main.reload();
                window.close();
            },
            failure: function ( result, request) { 
                Ext.MessageBox.alert('Failed', 'Some error occured while trying to delete the line.'); 
            } 
        });         
    },
    
    applyChanges: function(_button, _event, _closeWindow) 
    {
        var form = Ext.getCmp('voipmanager_editLineForm').getForm();

        if(form.isValid()) {
            form.updateRecord(this.lineRecord);
    
            Ext.Ajax.request({
                params: {
                    method: 'Voipmanager.saveLine', 
                    lineData: Ext.util.JSON.encode(this.lineRecord.data)
                },
                success: function(_result, _request) {
                    if(window.opener.Tine.Voipmanager.Lines) {
                        window.opener.Tine.Voipmanager.Lines.Main.reload();
                    }
                    if(_closeWindow === true) {
                        window.close();
                    } else {
                        this.updateLineRecord(Ext.util.JSON.decode(_result.responseText).updatedData);
                        this.updateToolbarButtons();
                        form.loadRecord(this.lineRecord);
                    }
                },
                failure: function ( result, request) { 
                    Ext.MessageBox.alert('Failed', 'Could not save line.'); 
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
        
    generalTab: function() {

        var translation = new Locale.Gettext();
        translation.textdomain('Voipmanager');

        var _dialog = {
            title: 'General',
            border: false,
            anchor: '100%',
            xtype: 'columnform',
            items: [[{
                xtype: 'textfield',
                fieldLabel: translation._('Name'),
                name: 'name',
                maxLength: 80,
                anchor: '98%',
                allowBlank: false
            }, {
                xtype: 'combo',
                fieldLabel: translation._('Context'),
                name: 'context',
                mode: 'local',
                displayField: 'name',
                valueField: 'id',
                anchor: '98%',
                triggerAction: 'all',
                editable: false,
                forceSelection: true,
                store: new Ext.data.JsonStore({
                    storeId: 'Voipmanger_EditLine_Context',
                    id: 'id',
                    fields: ['id', 'name']
                })
            }, {
                xtype: 'combo',
                fieldLabel: translation._('Type'),
                name: 'type',
                mode: 'local',
                displayField: 'value',
                valueField: 'key',
                anchor: '98%',
                triggerAction: 'all',
                editable: false,
                forceSelection: true,
                store: new Ext.data.SimpleStore({
                    autoLoad: true,
                    id: 'key',
                    fields: ['key', 'value'],
                    data: [['friend', 'friend'], ['user', 'user'], ['peer', 'peer']]
                })
            }], [{
                xtype: 'textfield',
                fieldLabel: translation._('Secret'),
                name: 'secret',
                maxLength: 80,
                anchor: '98%',
                allowBlank: false
            }, {
                xtype: 'textfield',
                fieldLabel: translation._('Callerid'),
                name: 'callerid',
                maxLength: 80,
                anchor: '100%',
                allowBlank: true
            }, {
                xtype: 'textfield',
                fieldLabel: translation._('Mailbox'),
                name: 'mailbox',
                maxLength: 50,
                anchor: '98%',
                allowBlank: true
            }], [{
                xtype: 'textfield',
                fieldLabel: translation._('Callgroup'),
                name: 'callgroup',
                maxLength: 10,
                anchor: '98%',
                allowBlank: true
            }, {
                xtype: 'textfield',
                fieldLabel: translation._('Pickup group'),
                name: 'pickupgroup',
                maxLength: 10,
                anchor: '98%',
                allowBlank: true
            }, {
                xtype: 'textfield',
                fieldLabel: translation._('Accountcode'),
                name: 'accountcode',
                maxLength: 20,
                anchor: '98%',
                allowBlank: true
            }], [{
                xtype: 'textfield',
                fieldLabel: translation._('Language'),
                name: 'language',
                maxLength: 2,
                anchor: '98%',
                allowBlank: true
            }, {
                xtype: 'combo',
                fieldLabel: translation._('NAT'),
                name: 'nat',
                mode: 'local',
                displayField: 'value',
                valueField: 'key',
                anchor: '98%',
                triggerAction: 'all',
                editable: false,
                forceSelection: true,
                store: new Ext.data.SimpleStore({
                    autoLoad: true,
                    id: 'key',
                    fields: ['key', 'value'],
                    data: [['yes', 'yes'], ['no', 'no']]
                })
            }, {
                xtype: 'combo',
                fieldLabel: translation._('Qualify'),
                name: 'qualify',
                mode: 'local',
                displayField: 'value',
                valueField: 'key',
                anchor: '98%',
                triggerAction: 'all',
                editable: false,
                forceSelection: true,
                store: new Ext.data.SimpleStore({
                    autoLoad: true,
                    id: 'key',
                    fields: ['key', 'value'],
                    data: [['yes', 'yes'], ['no', 'no']]
                })
            }]]
        }
    
        return _dialog;
    },     

    editLineDialog: function(){
    
        var translation = new Locale.Gettext();
        translation.textdomain('Voipmanager');
        
        var _dialog = {
            id: 'adbEditDialogTabPanel',
            xtype: 'tabpanel',
            defaults: {
                frame: true
            },
            height: 500,
            plain: true,
            activeTab: 0,
            border: false,
            items: [this.generalTab, {
                title: 'Advanced',
                layout: 'column',
                border: false,
                anchor: '100%',
                items: [{
                    columnWidth: .25,
                    layout: 'form',
                    border: false,
                    anchor: '100%',
                    items: [{
                        xtype: 'textfield',
                        fieldLabel: translation._('Name'),
                        name: 'name',
                        maxLength: 80,
                        anchor: '98%',
                        allowBlank: false
                    }, {
                        xtype: 'textfield',
                        fieldLabel: translation._('Accountcode'),
                        name: 'accountcode',
                        maxLength: 20,
                        anchor: '98%',
                        allowBlank: true
                    }, {
                        xtype: 'textfield',
                        fieldLabel: translation._('Amaflags'),
                        name: 'amaflags',
                        maxLength: 13,
                        anchor: '98%',
                        allowBlank: true
                    }, {
                        xtype: 'textfield',
                        fieldLabel: translation._('Callgroup'),
                        name: 'callgroup',
                        maxLength: 10,
                        anchor: '98%',
                        allowBlank: true
                    }, {
                        xtype: 'textfield',
                        fieldLabel: translation._('Callerid'),
                        name: 'callerid',
                        maxLength: 80,
                        anchor: '98%',
                        allowBlank: true
                    }, {
                        xtype: 'combo',
                        fieldLabel: translation._('Can reinvite'),
                        name: 'canreinvite',
                        mode: 'local',
                        displayField: 'value',
                        valueField: 'key',
                        anchor: '98%',
                        triggerAction: 'all',
                        editable: false,
                        forceSelection: true,
                        store: new Ext.data.SimpleStore({
                            autoLoad: true,
                            id: 'key',
                            fields: ['key', 'value'],
                            data: [['yes', 'yes'], ['no', 'no']]
                        })
                    }, {
                        xtype: 'textfield',
                        fieldLabel: translation._('Context'),
                        name: 'context',
                        maxLength: 80,
                        anchor: '98%',
                        allowBlank: true
                    }, {
                        xtype: 'textfield',
                        fieldLabel: translation._('Default IP'),
                        name: 'defaultip',
                        maxLength: 15,
                        anchor: '98%',
                        allowBlank: true
                    }, {
                        xtype: 'combo',
                        fieldLabel: translation._('DTMF mode'),
                        name: 'dtmfmode',
                        mode: 'local',
                        displayField: 'value',
                        valueField: 'key',
                        anchor: '98%',
                        triggerAction: 'all',
                        editable: false,
                        forceSelection: true,
                        store: new Ext.data.SimpleStore({
                            autoLoad: true,
                            id: 'key',
                            fields: ['key', 'value'],
                            data: [['inband', 'inband', ], ['info', 'info'], ['rfc2833', 'rfc2833']]
                        })
                    }, {
                        xtype: 'textfield',
                        fieldLabel: translation._('From User'),
                        name: 'fromuser',
                        maxLength: 80,
                        anchor: '98%',
                        allowBlank: true
                    }, {
                        xtype: 'textfield',
                        fieldLabel: translation._('From Domain'),
                        name: 'fromdomain',
                        maxLength: 80,
                        anchor: '98%',
                        allowBlank: true
                    }]
                }, {
                    columnWidth: .25,
                    layout: 'form',
                    border: false,
                    anchor: '98%',
                    autoHeight: true,
                    items: [{
                        xtype: 'textfield',
                        fieldLabel: translation._('Full Contact'),
                        name: 'fullcontact',
                        maxLength: 80,
                        anchor: '98%',
                        allowBlank: true
                    }, {
                        xtype: 'textfield',
                        fieldLabel: translation._('Host'),
                        name: 'host',
                        maxLength: 31,
                        anchor: '98%',
                        allowBlank: true
                    }, {
                        xtype: 'combo',
                        fieldLabel: translation._('Insecure'),
                        name: 'insecure',
                        mode: 'local',
                        displayField: 'value',
                        valueField: 'key',
                        anchor: '98%',
                        triggerAction: 'all',
                        editable: false,
                        forceSelection: true,
                        store: new Ext.data.SimpleStore({
                            autoLoad: true,
                            id: 'key',
                            fields: ['key', 'value'],
                            data: [['very', 'very'], ['yes', 'yes'], ['no', 'no'], ['invite', 'invite'], ['port', 'port']]
                        })
                    }, {
                        xtype: 'textfield',
                        fieldLabel: translation._('Language'),
                        name: 'language',
                        maxLength: 2,
                        anchor: '98%',
                        allowBlank: true
                    }, {
                        xtype: 'textfield',
                        fieldLabel: translation._('Mailbox'),
                        name: 'mailbox',
                        maxLength: 50,
                        anchor: '98%',
                        allowBlank: true
                    }, {
                        xtype: 'textfield',
                        fieldLabel: translation._('MD5 Secret'),
                        name: 'md5secret',
                        maxLength: 80,
                        anchor: '98%',
                        allowBlank: true
                    }, {
                        xtype: 'combo',
                        fieldLabel: translation._('NAT'),
                        name: 'nat',
                        mode: 'local',
                        displayField: 'value',
                        valueField: 'key',
                        anchor: '98%',
                        triggerAction: 'all',
                        editable: false,
                        forceSelection: true,
                        store: new Ext.data.SimpleStore({
                            autoLoad: true,
                            id: 'key',
                            fields: ['key', 'value'],
                            data: [['yes', 'yes'], ['no', 'no']]
                        })
                    }, {
                        xtype: 'textfield',
                        fieldLabel: translation._('Deny'),
                        name: 'deny',
                        maxLength: 95,
                        anchor: '98%',
                        allowBlank: true
                    }, {
                        xtype: 'textfield',
                        fieldLabel: translation._('Permit'),
                        name: 'permit',
                        maxLength: 95,
                        anchor: '98%',
                        allowBlank: true
                    }, {
                        xtype: 'textfield',
                        fieldLabel: translation._('Mask'),
                        name: 'mask',
                        maxLength: 95,
                        anchor: '98%',
                        allowBlank: true
                    }, {
                        xtype: 'textfield',
                        fieldLabel: translation._('Pickup group'),
                        name: 'pickupgroup',
                        maxLength: 10,
                        anchor: '98%',
                        allowBlank: true
                    }]
                }, {
                    columnWidth: .25,
                    layout: 'form',
                    border: false,
                    anchor: '98%',
                    autoHeight: true,
                    items: [{
                        xtype: 'textfield',
                        fieldLabel: translation._('Port'),
                        name: 'port',
                        maxLength: 5,
                        anchor: '98%',
                        allowBlank: true
                    }, {
                        xtype: 'combo',
                        fieldLabel: translation._('Qualify'),
                        name: 'qualify',
                        mode: 'local',
                        displayField: 'value',
                        valueField: 'key',
                        anchor: '98%',
                        triggerAction: 'all',
                        editable: false,
                        forceSelection: true,
                        store: new Ext.data.SimpleStore({
                            autoLoad: true,
                            id: 'key',
                            fields: ['key', 'value'],
                            data: [['yes', 'yes'], ['no', 'no']]
                        })
                    }, {
                        xtype: 'textfield',
                        fieldLabel: translation._('Restrict CID'),
                        name: 'restrictcid',
                        maxLength: 1,
                        anchor: '98%',
                        allowBlank: true
                    }, {
                        xtype: 'textfield',
                        fieldLabel: translation._('RTP Timeout'),
                        name: 'rtptimeout',
                        maxLength: 3,
                        anchor: '98%',
                        allowBlank: true
                    }, {
                        xtype: 'textfield',
                        fieldLabel: translation._('RTP Hold Timeout'),
                        name: 'rtpholdtimeout',
                        maxLength: 3,
                        anchor: '98%',
                        allowBlank: true
                    }, {
                        xtype: 'textfield',
                        fieldLabel: translation._('Secret'),
                        name: 'secret',
                        maxLength: 80,
                        anchor: '98%',
                        allowBlank: true
                    }, {
                        xtype: 'combo',
                        fieldLabel: translation._('Type'),
                        name: 'type',
                        mode: 'local',
                        displayField: 'value',
                        valueField: 'key',
                        anchor: '98%',
                        triggerAction: 'all',
                        editable: false,
                        forceSelection: true,
                        store: new Ext.data.SimpleStore({
                            autoLoad: true,
                            id: 'key',
                            fields: ['key', 'value'],
                            data: [['friend', 'friend'], ['user', 'user'], ['peer', 'peer']]
                        })
                    }, {
                        xtype: 'textfield',
                        fieldLabel: translation._('Username'),
                        name: 'username',
                        maxLength: 80,
                        anchor: '98%',
                        allowBlank: false
                    }, {
                        xtype: 'textfield',
                        fieldLabel: translation._('Disallow'),
                        name: 'disallow',
                        maxLength: 100,
                        anchor: '98%',
                        allowBlank: false
                    }, {
                        xtype: 'textfield',
                        fieldLabel: translation._('Allow'),
                        name: 'allow',
                        maxLength: 100,
                        anchor: '98%',
                        allowBlank: false
                    }, {
                        xtype: 'textfield',
                        fieldLabel: translation._('Music on hold'),
                        name: 'musiconhold',
                        maxLength: 100,
                        anchor: '98%',
                        allowBlank: false
                    }]
                }, {
                    columnWidth: .25,
                    layout: 'form',
                    border: false,
                    anchor: '98%',
                    autoHeight: true,
                    items: [{
                        xtype: 'textfield',
                        fieldLabel: translation._('Reg Seconds'),
                        name: 'regseconds',
                        maxLength: 11,
                        anchor: '98%',
                        allowBlank: false
                    }, {
                        xtype: 'textfield',
                        fieldLabel: translation._('IP Address'),
                        name: 'ipaddr',
                        maxLength: 15,
                        anchor: '98%',
                        allowBlank: false
                    }, {
                        xtype: 'textfield',
                        fieldLabel: translation._('Reg Exten'),
                        name: 'regexten',
                        maxLength: 80,
                        anchor: '98%',
                        allowBlank: false
                    }, {
                        xtype: 'combo',
                        fieldLabel: translation._('Can forward call'),
                        name: 'cancallforward',
                        mode: 'local',
                        displayField: 'value',
                        valueField: 'key',
                        anchor: '98%',
                        triggerAction: 'all',
                        editable: false,
                        forceSelection: true,
                        store: new Ext.data.SimpleStore({
                            autoLoad: true,
                            id: 'key',
                            fields: ['key', 'value'],
                            data: [['yes', 'yes'], ['no', 'no']]
                        })
                    }, {
                        xtype: 'textfield',
                        fieldLabel: translation._('Set Var'),
                        name: 'setvar',
                        maxLength: 100,
                        anchor: '98%',
                        allowBlank: false
                    }, {
                        xtype: 'combo',
                        fieldLabel: translation._('Notify Ringing'),
                        name: 'notifyringing',
                        mode: 'local',
                        displayField: 'value',
                        valueField: 'key',
                        anchor: '98%',
                        triggerAction: 'all',
                        editable: false,
                        forceSelection: true,
                        store: new Ext.data.SimpleStore({
                            autoLoad: true,
                            id: 'key',
                            fields: ['key', 'value'],
                            data: [['yes', 'yes'], ['no', 'no']]
                        })
                    }, {
                        xtype: 'combo',
                        fieldLabel: translation._('Use Client Code'),
                        name: 'useclientcode',
                        mode: 'local',
                        displayField: 'value',
                        valueField: 'key',
                        anchor: '98%',
                        triggerAction: 'all',
                        editable: false,
                        forceSelection: true,
                        store: new Ext.data.SimpleStore({
                            autoLoad: true,
                            id: 'key',
                            fields: ['key', 'value'],
                            data: [['yes', 'yes'], ['no', 'no']]
                        })
                    }, {
                        xtype: 'textfield',
                        fieldLabel: translation._('Auth User'),
                        name: 'authuser',
                        maxLength: 100,
                        anchor: '98%',
                        allowBlank: false
                    }, {
                        xtype: 'textfield',
                        fieldLabel: translation._('Call Limit'),
                        name: 'call-limit',
                        maxLength: 11,
                        anchor: '98%',
                        allowBlank: false
                    }, {
                        xtype: 'textfield',
                        fieldLabel: translation._('Busy Level'),
                        name: 'busy-level',
                        maxLength: 100,
                        anchor: '98%',
                        allowBlank: false
                    }]
                }]
            }]
        };
     
        return _dialog;    
    },
    
    updateToolbarButtons: function()
    {
        if(this.lineRecord.get('id') > 0) {
            Ext.getCmp('voipmanager_editLineForm').action_delete.enable();
        }
    },
    
    display: function(_lineData, _contexts) 
    {
        if (!arguments[0]) {
            var _lineData = {};
        }

        // Ext.FormPanel
        var dialog = new Tine.widgets.dialog.EditRecord({
            id : 'voipmanager_editLineForm',
            //title: 'the title',
            labelWidth: 120,
            labelAlign: 'top',
            handlerScope: this,
            handlerApplyChanges: this.applyChanges,
            handlerSaveAndClose: this.saveChanges,
            handlerDelete: this.deleteLine,
            items: [{
		        id: 'adbEditDialogTabPanel',
		        xtype:'tabpanel',
		        defaults: {
		            frame: true
		        },
		        height: 500,
		        plain:true,
		        activeTab: 0,
		        border: false,
		        items:[
		          this.generalTab()
                ]
            }]
        });

        Ext.StoreMgr.lookup('Voipmanger_EditLine_Context').loadData(_contexts);

        var viewport = new Ext.Viewport({
            layout: 'border',
            frame: true,
            //height: 300,
            items: dialog
        });
        
                
        this.updateLineRecord(_lineData);
        this.updateToolbarButtons();           
        dialog.getForm().loadRecord(this.lineRecord);
    } 
};
