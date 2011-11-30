/**
 * Tine 2.0
 * 
 * @package Sipgate
 * @license http://www.gnu.org/licenses/agpl.html AGPL3
 * @author Alexander Stintzing <alex@stintzing.net>
 * @copyright Copyright (c) 2011 Metaways Infosystems GmbH
 *            (http://www.metaways.de)
 * @version $Id: Sipgate.js 24 2011-05-02 02:47:52Z alex $
 * 
 */

Ext.ns('Tine.Sipgate');

var CallUpdateWindowTask;
var CallingState = false;

Tine.Sipgate.getPanel = function() {

    var translation = new Locale.Gettext();
    translation.textdomain('Sipgate');

    new Tine.Sipgate.AddressbookGridPanelHook({
                app : {
                    i18n : translation
                }
            });
// coming soon
//    var editSipgateSettingsAction = new Ext.Action({
//                text : translation._('Edit phone settings'),
//                iconCls : 'SipgateIconCls',
//                handler : function() {
//                    // TODO: options by user
//                },
//                scope : this
//            });
//
//    var contextMenu = new Ext.menu.Menu({
//                items : [editSipgateSettingsAction]
//            });
    /** ********* tree panel **************** */

    var treePanel = new Ext.tree.TreePanel({
                title : translation.gettext('Sipgate'),
                id : 'sipgate-tree',
                iconCls : 'SipgateIconCls',
                rootVisible : true,
                border : false,
                collapsible : true
            });

    /** ********* root node **************** */

    var treeRoot = new Ext.tree.TreeNode({
                text : translation._('Devices'),
                cls : 'treemain',
                allowDrag : false,
                allowDrop : false,
                id : 'root',
                icon : false
            });
    treePanel.setRootNode(treeRoot);

    Tine.Sipgate.loadSipgateStore();

    /** ****** tree panel handlers ********** */

    treePanel.on('click', function(node, event) {
                node.select();
            }, this);
// coming soon
//    treePanel.on('contextmenu', function(node, event) {
//                this.ctxNode = node;
//                if (node.id != 'root') {
//                    contextMenu.showAt(event.getXY());
//                }
//            }, this);

    treePanel.on('beforeexpand', function(panel) {
                if (panel.getSelectionModel().getSelectedNode() === null) {
                    var node = panel.getRootNode();
                    node.select();
                    node.expand();
                } else {
                    panel.getSelectionModel().fireEvent('selectionchange',
                            panel.getSelectionModel());
                }
            }, this);

    treePanel.getSelectionModel().on('selectionchange',
            function(_selectionModel) {
                var node = _selectionModel.getSelectedNode();

                // update toolbar
                var settingsButton = Ext.getCmp('phone-settings-button');
                if (settingsButton) {
                    if (node && node.id != 'root') {
                        settingsButton.setDisabled(false);
                    } else {
                        settingsButton.setDisabled(true);
                    }
                }
                Tine.Sipgate.Main.show(node);
            }, this);

    return treePanel;
};

/** ************************** main *************************************** */
/**
 * sipgate main view
 * 
 * @todo show phone calls
 */
Tine.Sipgate.Main = {
    /**
     * translations object
     */
    translation : null,

    /**
     * holds underlaying store
     */
    store : null,

    /**
     * @cfg {Object} paging defaults
     */
    paging : {
        start : 0,
        stop : 10,
        sort : 'Timestamp',
        dir : 'DESC'
    },

    /**
     * action buttons
     */
    actions : {
        dialNumber : null,
        // editSipgateSettings: null,
        addNumber : null
    },

    /**
     * init component function
     */
    initComponent : function() {
        
        this.translation = new Locale.Gettext();
        this.translation.textdomain('Sipgate');

        this.actions.dialNumber = new Ext.Action({
                    text : this.translation._('Dial number'),
                    tooltip : this.translation._('Initiates a new outgoing call'),
                    handler : this.handlers.dialNumber,
                    iconCls : 'action_DialNumber',
                    scope : this
                });

        this.actions.addNumber = new Ext.Action({
            text : this.translation._('Add Number to Addressbook'),
            tooltip : this.translation._('Adds this number to the Addressbook'),
            handler : this.handlers.addNumber,
            iconCls : 'action_AddNumber',
            scope : this
        });

        this.initStore();

    },

    handlers : {
        addNumber : function(_button, _event) {
            var number = null;
            var grid = Ext.getCmp('Sipgate_Callhistory_Grid');
            if (grid) {
                var record = grid.getSelectionModel().getSelected();
                if (record && record.id != 'root') {
                    number = record.data.RemoteNumber;
                }
            }
            Tine.Sipgate.addPhoneNumber(number);
        },
        dialNumber : function(_button, _event) {
            var number = null;
            var contact = null;
            var grid = Ext.getCmp('Sipgate_Callhistory_Grid');
            if (grid) {
                var record = grid.getSelectionModel().getSelected();
                if (record && record.id != 'root') {
                    number = record.data.RemoteNumber;
                    contact = { data: record.data.RemoteRecord };
                }
            }
            Tine.Sipgate.dialPhoneNumber(number,contact);
        }
    },

    displayToolbar : function() {

        var toolbar = new Ext.Toolbar({
                    id : 'Sipgate_Toolbar',
                    items : [{
                        xtype : 'buttongroup',
                        columns : 1,
                        items : [Ext.apply(
                                new Ext.Button(this.actions.dialNumber), {
                                    scale : 'medium',
                                    rowspan : 2,
                                    iconAlign : 'top'
                                })]
                    }, '->']
                });

        Tine.Tinebase.MainScreen.setActiveToolbar(toolbar);
    },

    /**
     * init the calls json grid store
     * 
     * @todo add more filters (phone, line, ...)
     * @todo use new filter toolbar later
     */
    initStore : function() {

        this.store = new Ext.data.JsonStore({
                    id : 'EntryID',
                    autoLoad : false,
                    
                    root: 'items',
                    totalProperty : 'totalcount',
                    fields : ['EntryID', 'Timestamp', 'RemoteUri', 'LocalUri',
                            'Status', 'RemoteParty', 'RemoteRecord',
                            'RemoteNumber'],
                    remoteSort : false,
                    baseParams : {
                        
                        method : 'Sipgate.getCallHistory'
                    },
                    sortInfo : {
                        field : this.paging.sort,
                        direction : this.paging.dir
                    }
                });

        // register store
        Ext.StoreMgr.add('SipgateCallHistoryStore', this.store);

        // prepare filter getCallHistory
        this.store.on('beforeload', function(store, options) {

                    if (!options.params) {
                        options.params = {};
                    }

                    var node = Ext.getCmp('sipgate-tree').getSelectionModel().getSelectedNode()    || null;

                    var _pstart = options.params.start ? options.params.start : this.paging.start;
                    var _plimit = options.params.limit ? options.params.limit : this.paging.limit;
                    
                    
                    this.store.setBaseParam('_pstart', _pstart);
                    this.store.setBaseParam('_plimit', _plimit);
                    this.store.setBaseParam('_sipUri', node.id);
                    this.store.setBaseParam('_start', new Date(Ext.getCmp('startdt').getValue()));
                    this.store.setBaseParam('_stop', new Date(Ext.getCmp('enddt').getValue()));

                }, this);
    },

    /**
     * display the callhistory grid
     * 
     */
    displayGrid : function() {

        var fromdate = new Ext.form.DateField({
                    format : 'D, d. M. Y',
                    value : new Date(new Date().getTime()
                            - (24 * 60 * 60 * 1000)),
                    fieldLabel : _('Calls from'),
                    id : 'startdt',
                    name : 'startdt',
                    width : 140,
                    allowBlank : false,
                    endDateField : 'enddt'
                });

        var todate = new Ext.form.DateField({

                    format : 'D, d. M. Y',
                    fieldLabel : _('Calls to'),

                    value : new Date(),
                    maxValue : new Date(),
                    id : 'enddt',
                    name : 'enddt',
                    width : 140,
                    allowBlank : false,
                    startDateField : 'startdt'
                });

        var pagingToolbar = new Ext.PagingToolbar({
            items: [ fromdate, todate ],
            prependButtons: true,

                    pageSize : 20,
                    store : this.store,
                    displayInfo : true,
                    displayMsg : this.translation
                            ._('Displaying calls {0} - {1} of {2}'),
                    emptyMsg : this.translation._("No calls to display")
                });


        // the columnmodel
        var columnModel = new Ext.grid.ColumnModel({
                    defaults : {
                        sortable : true,
                        resizable : true
                    },
                    columns : [{
                        id : 'Status',
                        header : this.translation._('Status'),
                        dataIndex : 'Status',
                        width : 20,
                        renderer: function(el){ 
                            return '<div class="SipgateCallStateList ' + el + '"></div>';
                        } 
                        
                            // renderer : this.renderer.direction
                        }, {
                        id : 'RemoteParty',
                        header : this.translation._('Remote Party'),
                        dataIndex : 'RemoteParty',
                        hidden : false
                    }, {
                        id : 'RemoteNumber',
                        header : this.translation._('Remote Number'),
                        dataIndex : 'RemoteNumber',
                        hidden : false
                    }, {
                        id : 'LocalUri',
                        header : this.translation._('Local Uri'),
                        dataIndex : 'LocalUri',
                        hidden : true
                    }, {
                        id : 'RemoteUri',
                        header : this.translation._('Remote Uri'),
                        dataIndex : 'RemoteUri',
                        hidden : true
                            // renderer :
                            // this.renderer.destination'RemoteParty','RemoteRecord','RemoteNumber'
                        }, {
                        id : 'Timestamp',
                        header : this.translation._('Call started'),
                        dataIndex : 'Timestamp',
                        renderer: function(tstamp) {
                            var d = new Date(tstamp*1000);                            
                            var n = d.format('D, d. M. Y H:m:s');
                            return n;
                        },
                        hidden:false
                        }, {
                        id : 'EntryID',
                        header : this.translation._('Call ID'),
                        dataIndex : 'EntryID',
                        hidden : true
                    }]
                });

        columnModel.defaultSortable = true; // by default columns are
        // sortable

        // the rowselection model
        var rowSelectionModel = new Ext.grid.RowSelectionModel({
                    multiSelect : false
                });

        // the gridpanel
        var gridPanel = new Ext.grid.GridPanel({
                    id : 'Sipgate_Callhistory_Grid',
                    store : this.store,
                    cm : columnModel,
                    tbar: pagingToolbar,
                    autoSizeColumns : true,
                    selModel : rowSelectionModel,
                    enableColLock : false,
                    loadMask : true,
                    autoExpandColumn : 'destination',
                    border : false,
                    view : new Ext.grid.GridView({
                                autoFill : true,
                                forceFit : true,
                                ignoreAdd : true,
                                emptyText : this.translation
                                        ._('No calls to display')
                            })

                });

        gridPanel.on('rowcontextmenu',
                function(_grid, _rowIndex, _eventObject) {
                    _eventObject.stopEvent();

                    if (!_grid.getSelectionModel().isSelected(_rowIndex)) {
                        _grid.getSelectionModel().selectRow(_rowIndex);
                    }

                    if(_grid.getSelectionModel().getSelected().data.RemoteRecord) {
                        var items = [ this.actions.dialNumber ];
                    } else {
                        var items = [ this.actions.dialNumber, this.actions.addNumber ];
                    }
                    
                    
                    var contextMenu = new Ext.menu.Menu({
                                items : items
                            });
                    contextMenu.showAt(_eventObject.getXY());
                }, this);

        gridPanel.on('rowdblclick', function(obj) {
            if(obj) {
                var record = obj.getSelectionModel().getSelected();
                if (record) {
                    //var contact = null;
                    Tine.log.debug(record);
                    var contact = { data: record.data.RemoteRecord }; 
                    var number = record.data.RemoteNumber;
                    if(number) {
                        Tine.Sipgate.dialPhoneNumber(number,contact);
                    }
                }
            }

        });
        
        Tine.Tinebase.MainScreen.setActiveContentPanel(gridPanel);
    },

    show : function(_node) {

        var currentToolbar = Tine.Tinebase.MainScreen.getActiveToolbar();

        this.initComponent();
        this.displayToolbar();
        this.displayGrid();
        if (_node.id != 'root') {
            this.store.load({});
        }
    }

};

/** ************************** store *************************************** */

/**
 * get user phones store
 * 
 * @return Ext.data.JsonStore with phones
 */
Tine.Sipgate.loadSipgateStore = function(reload) {
    
    var store = Ext.StoreMgr.get('SipgateStore');
//Tine.log.info('Loading Sipgate Store...');
    if (!store) {
        // create store (get from initial data)
        store = new Ext.data.JsonStore({
                    fields : ['SipUri', 'UriAlias', 'type', 'E164Out',
                            'DefaultUri'],
                    data : Tine.Sipgate.registry.get('Devices'),
                    autoLoad : true,
                    id : 'SipUri'
                });
        

        if(store.getTotalCount() > 0) {    
            Ext.StoreMgr.add('SipgateStore', store);
            Tine.Sipgate.updateSipgateTree(store);
        }
        
    }
    
    return store;
};

/**
 * load phones
 */
Tine.Sipgate.updateSipgateTree = function(store) {

    var translation = new Locale.Gettext();
    translation.textdomain('Phone');

    // get tree root
    var treeRoot = Ext.getCmp('sipgate-tree').getRootNode();

    // remove all children first
    treeRoot.eachChild(function(child) {
                treeRoot.removeChild(child);
            });

    // add phones to tree menu
    store.each(function(record) {
        var node = new Ext.tree.TreeNode({
                            id : record.id,
                            record : record,
                            text : record.data.SipUri,
                            qtip : record.data.UriAlias,
                            iconCls : 'SipgateTreeNode_' + record.data.type,
                            leaf : true
                        });
                treeRoot.appendChild(node);
            });
};

/**
 * @param string
 *            number
 */
Tine.Sipgate.dialPhoneNumber = function(number, contact) {

    var translation = new Locale.Gettext();
    translation.textdomain('Sipgate');

    if (!number) {
        var popUpWindow = Tine.Sipgate.DialNumberDialog.openWindow();
        return true;
    }
    
    if (!contact) {
        contact = { data : { n_fn : translation._('unknown Person')    }};
    }

    var info = { name : contact.data.n_fn, number : number };

    var win = Tine.Sipgate.CallStateWindow.openWindow({  info : info });

    var initRequest = Ext.Ajax.request({
        url : 'index.php',
        params : { method : 'Sipgate.dialNumber', _number : number },
        success : function(_result, _request) {
            Tine.log.debug('RES: ',_result);
            var sessionId = Ext.decode(_result.responseText).state.SessionID;
            var win2 = Ext.getCmp('callstate-window');
            if(win2) {
                win2.sessionId = sessionId;
                Tine.Sipgate.CallStateWindow.startTask(sessionId, contact);
                Ext.getCmp('call-cancel-button').enable();
            }
        },
        failure : function(_result, _request) {
          Ext.Msg.alert(_('Configuration not set'), _('Please configure the Sipgate application'));
          win.close();
          
          return false;
          }
    });
    return true;
};

Tine.Sipgate.addPhoneNumber = function(number) {

    // check if addressbook app is available
    if (!Tine.Addressbook || !Tine.Tinebase.common.hasRight('run', 'Addressbook')) {
        return;
    }
    var popupWindow = Tine.Sipgate.SearchAddressDialog.openWindow({
        number : number
    });
};

Tine.Sipgate.closeSession = function(sessionId) {
    Ext.Ajax.request({
        url : 'index.php',

        params : {
            method : 'Sipgate.closeSession',
            sessionId : sessionId
            },

        success : function(_result, _request) {    },
        failure : function(_result, _request) {    }
            });
};

/**
 * @param string sessionId
 * @param {Object} contact
 */

Tine.Sipgate.updateCallStateWindow = function(sessionId, contact) {

    Ext.Ajax.request({
        url : 'index.php',

        params : {
            method : 'Sipgate.getSessionStatus',
            sessionId : sessionId
        },
        success : function(_result, _request) {
            var result = Ext.decode(_result.responseText);

            try {
                var uC = Ext.getCmp('csw-update-container');
                if (uC) throw uC;
                else throw false;
            } catch (e) {
                if (e != false) {
                    try {
                        if (result.StatusCode == 200) throw result;
                        else throw false;
                    } catch (e) {
                        if (e != false) {
                            // Statusmeldungen auswerten
                            switch (result.SessionStatus) {
                                case 'first dial' :
                                    Ext.getCmp('csw-call-info').update(Tine.Tinebase.appMgr.get('Sipgate').i18n._('first dial'));
                                    Ext.get('csw-my-phone').frame("ff0000", 1);
                                    CallingState = true;
                                    break;
                                case 'second dial' :
                                    Ext.getCmp('csw-call-info').update(Tine.Tinebase.appMgr.get('Sipgate').i18n._('second dial') + ' ' + contact.data.n_fn);
                                    Ext.get('csw-my-phone').addClass('established');
                                    Ext.get('csw-other-phone').frame("ff0000",1);
                                    CallingState = '1';
                                    break;
                                case 'established' :
                                    Ext.get('csw-other-phone').addClass('established');
                                    Ext.getCmp('csw-call-info').update(Tine.Tinebase.appMgr.get('Sipgate').i18n._('established') + ' ' + contact.data.n_fn);
                                    CallingState = '2';
                                    break;
                                case ('call 1 busy' || 'call 1 failed') :
                                    Ext.get('csw-my-phone').addClass('error');
                                    Ext.getCmp('csw-call-info').update(Tine.Tinebase.appMgr.get('Sipgate').i18n._('call 1 busy'));
                                    CallingState = false;
                                    Tine.Sipgate.CallStateWindow.stopTask();
                                    break;
                                case ('call 2 busy' || 'call 1 failed') :
                                    Ext.get('csw-other-phone').addClass('error');
                                    Ext.get('csw-my-phone').removeClass('established');
                                    Ext.getCmp('csw-call-info').update(contact.data.n_fn + ' ' + Tine.Tinebase.appMgr.get('Sipgate').i18n._('call 2 busy'));
                                    CallingState = false;
                                    Tine.Sipgate.CallStateWindow.stopTask();
                                    break;
                                case 'hungup' :
                                    switch (CallingState) {
                                        case '1' :
                                            Ext.getCmp('csw-call-info').update(Tine.Tinebase.appMgr.get('Sipgate').i18n._('hungup before other called'));
                                            break;
                                        default :
                                            Ext.getCmp('csw-call-info').update(Tine.Tinebase.appMgr.get('Sipgate').i18n._('hungup'));
                                    }
                                    Ext.get('csw-my-phone').removeClass('established');
                                    Ext.get('csw-other-phone').removeClass('established');
                                    Tine.Sipgate.CallStateWindow.stopTask();
                                    CallingState = false;
                                    break;
                                default :
                                    Ext.getCmp('csw-call-info').update(result.SessionStatus);
                                    Tine.Sipgate.CallStateWindow.stopTask();
                                    CallingState = false;
                            }
                        }
                    }
                }
            }
        },
        failure : function(result, request) {

        }
    });

};
