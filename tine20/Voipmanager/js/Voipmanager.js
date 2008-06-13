/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
 
Ext.namespace('Tine.Voipmanager');

Tine.Voipmanager = function() {
    
    /**
     * builds the voipmanager applications tree
     */
    var _initialTree = [{
        text: 'Asterisk',
        cls: 'treemain',
        allowDrag: false,
        allowDrop: true,
        id: 'asterisk',
        icon: false,
        children: [{
            text :"Lines",
            cls :"treemain",
            allowDrag :false,
            allowDrop :true,
            id :"lines",
            icon :false,
            children :[],
            leaf :true,
            expanded :true,
            dataPanelType :"lines",
            viewRight: 'lines'
        },{
            text :"Dialplan",
            cls :"treemain",
            allowDrag :false,
            allowDrop :true,
            id :"dialplan",
            icon :false,
            children :[],
            leaf :true,
            expanded :true,
            dataPanelType :"dialplan",
            viewRight:'dialplan'
        },{
            text :"Context",
            cls :"treemain",
            allowDrag :false,
            allowDrop :true,
            id :"context",
            icon :false,
            children :[],
            leaf :true,
            expanded :true,
            dataPanelType :"context",
            viewRight:'context'
        },{
            text :"Voicemail",
            cls :"treemain",
            allowDrag :false,
            allowDrop :true,
            id :"voicemail",
            icon :false,
            children :[],
            leaf :true,
            expanded :true,
            dataPanelType :"voicemail",
            viewRight:'voicemail'
        },{
            text :"Meetme",
            cls :"treemain",
            allowDrag :false,
            allowDrop :true,
            id :"meetme",
            icon :false,
            children :[],
            leaf :true,
            expanded :true,
            dataPanelType :"meetme",
            viewRight:'meetme'
        },{
            text :"Queues",
            cls :"treemain",
            allowDrag :false,
            allowDrop :true,
            id :"queues",
            icon :false,
            children :[],
            leaf :true,
            expanded :true,
            dataPanelType :"queues",
            viewRight:'queues'
        }],
        leaf: null,
        expanded: true,
        dataPanelType: 'asterisk',
        viewRight: 'asterisk'
    },{
        text: 'Snom',
        cls: 'treemain',
        allowDrag: false,
        allowDrop: true,
        id: 'snom',
        icon: false,
        children: [{
            text: 'Phones',
            cls: 'treemain',
            allowDrag: false,
            allowDrop: true,
            id: 'phones',
            icon: false,
            children: [],
            leaf: true,
            expanded: true,
            dataPanelType: 'phones',
            viewRight: 'phones'
        },{
            text: "Location",
            cls: "treemain",
            allowDrag: false,
            allowDrop: true,
            id: "location",
            icon: false,
            children: [],
            leaf: true,
            expanded: true,
            dataPanelType: "location",
            viewRight: 'location'
        },{
            text: "Templates",
            cls: "treemain",
            allowDrag: false,
            allowDrop: true,
            id: "templates",
            icon: false,
            children: [],
            leaf: null,
            expanded: true,
            dataPanelType: "templates",
            viewRight: 'templates'
        },{
            text :"Keylayout",
            cls :"treemain",
            allowDrag :false,
            allowDrop :true,
            id :"keylayout",
            icon :false,
            children :[],
            leaf :null,
            expanded :true,
            dataPanelType :"keylayout",
            viewRight: 'keylayout'
        },{
            text :"Settings",
            cls :"treemain",
            allowDrag :false,
            allowDrop :true,
            id :"settings",
            icon :false,
            children :[],
            leaf :null,
            expanded :true,
            dataPanelType :"settings",
            viewRight: 'settings'
        },{
            text :"Software",
            cls :"treemain",
            allowDrag :false,
            allowDrop :true,
            id :"software",
            icon :false,
            children :[],
            leaf :null,
            expanded :true,
            dataPanelType :"software",
            viewRight: 'software'
        }],
        leaf: null,
        expanded: true,
        dataPanelType: 'snom',
        viewRight: 'snom'
    }];

    /**
     * creates the voipmanager menu tree
     *
     */
    var _getVoipmanagerTree = function() 
    {
        var translation = new Locale.Gettext();
        translation.textdomain('Voipmanager');        
        
        var treeLoader = new Ext.tree.TreeLoader({
            dataUrl:'index.php',
            baseParams: {
                jsonKey: Tine.Tinebase.Registry.get('jsonKey'),
                method: 'Voipmanager.getSubTree',
                location: 'mainTree'
            }
        });
        treeLoader.on("beforeload", function(_loader, _node) {
            _loader.baseParams.node     = _node.id;
        }, this);
    
        var treePanel = new Ext.tree.TreePanel({
            title: 'Voipmanager',
            id: 'voipmanager-tree',
            iconCls: 'VoipmanagerIconCls',
            loader: treeLoader,
            rootVisible: false,
            border: false
        });
        
        // set the root node
        var treeRoot = new Ext.tree.TreeNode({
            text: 'root',
            draggable:false,
            allowDrop:false,
            id:'root'
        });
        treePanel.setRootNode(treeRoot);

        for(var i=0; i<_initialTree.length; i++) {
            
            var node = new Ext.tree.AsyncTreeNode(_initialTree[i]);
        
            // check view right
            if ( _initialTree[i].viewRight && !Tine.Tinebase.hasRight('view', _initialTree[i].viewRight) ) {
                node.disabled = true;
            }
            
            treeRoot.appendChild(node);
        }

        
        treePanel.on('click', function(_node, _event) {
            if ( _node.disabled ) {
                return false;
            }
            
            var currentToolbar = Tine.Tinebase.MainScreen.getActiveToolbar();

            switch(_node.attributes.dataPanelType) {
                case 'phones':
                    if(currentToolbar !== false && currentToolbar.id == 'toolbarVoipmanagerPhones') {
                        Ext.getCmp('gridVoipmanagerPhones').getStore().load({params:{start:0, limit:50}});
                    } else {
                        Tine.Voipmanager.Phones.Main.show(_node);
                    }
                    break;                    
                    
                case 'location':
                    if(currentToolbar !== false && currentToolbar.id == 'toolbarVoipmanagerLocation') {
                        Ext.getCmp('gridVoipmanagerLocation').getStore().load({params:{start:0, limit:50}});
                    } else {
                        Tine.Voipmanager.Location.Main.show(_node);
                    }
                    break;                                        
                    
                case 'templates':
                    if(currentToolbar !== false && currentToolbar.id == 'toolbarVoipmanagerTemplates') {
                        Ext.getCmp('gridVoipmanagerTemplates').getStore().load({params:{start:0, limit:50}});
                    } else {
                        Tine.Voipmanager.Templates.Main.show(_node);
                    }
                    break;                     
                    
                case 'software':
                    if(currentToolbar !== false && currentToolbar.id == 'toolbarVoipmanagerSoftware') {
                        Ext.getCmp('gridVoipmanagerSoftware').getStore().load({params:{start:0, limit:50}});
                    } else {
                        Tine.Voipmanager.Software.Main.show(_node);
                    }
                    break;                      
                    
                case 'lines':
                    if(currentToolbar !== false && currentToolbar.id == 'toolbarVoipmanagerLines') {
                        Ext.getCmp('gridVoipmanagerLines').getStore().load({params:{start:0, limit:50}});
                    } else {
                        Tine.Voipmanager.Lines.Main.show(_node);
                    }
                    break;                                          
            }
        }, this);

        treePanel.on('beforeexpand', function(_panel) {
            if(_panel.getSelectionModel().getSelectedNode() === null) {
                _panel.expandPath('/root');
                _panel.selectPath('/root/snom/phones');
            }
            _panel.fireEvent('click', _panel.getSelectionModel().getSelectedNode());
        }, this);

        treePanel.on('contextmenu', function(_node, _event) {
            _event.stopEvent();
            //_node.select();
            //_node.getOwnerTree().fireEvent('click', _node);
            //console.log(_node.attributes.contextMenuClass);
            /* switch(_node.attributes.contextMenuClass) {
                case 'ctxMenuContactsTree':
                    ctxMenuContactsTree.showAt(_event.getXY());
                    break;
            } */
        });

        return treePanel;
    };
    
    // public functions and variables
    return {
        getPanel: _getVoipmanagerTree
    };
    
}();

Tine.Voipmanager.Data = {
  
    loadTimezoneData: function() {


        var timezoneDataStore = new Ext.data.SimpleStore({
            autoLoad: true,
            fields: ['key','timezone'],
            data: [
                ['USA-10','-10 Vereinigte Staaten - Hawaii-Aleutian'],
                ['USA-9','-9 Vereinigte Staaten - Alaska Time'],
                ['CAN-8','-8 Kanada (Vancouver, Whitehorse)'],
                ['MEX-8','-8 Mexiko (Tijuana, Mexicali)'],
                ['USA-8','-8 Vereinigte Staaten - Pacific Time'],
                ['CAN-7','-7 Kanada (Edmonton, Calgary)'],
                ['MEX-7','-7 Mexiko (Mazatlan, Chihuahua)'],
                ['USA-7','-7 Vereinigte Staaten - Mountain Time'],
                ['USA2-7','-7 Vereinigte Staaten - Mountain Time'],
                ['CAN-6','-6 Kanada - Manitoba (Winnipeg)'],
                ['CHL-6','-6 Chile (Easter Islands)'],
                ['MEX-6','-6 Mexiko (Mexiko City, Acapulco)'],
                ['USA-6','-6 Vereinigte Staaten - Central Time'],
                ['BHS-5','-5 Bahamas (Nassau)'],
                ['CAN-5','-5 Kanada (Montreal, Ottawa, Quebec)'],
                ['CUB-5','-5 Kuba (Havana)'],
                ['USA-5','-5 Vereinigte Staaten - Eastern Time'],
                ['CAN-4','-4 Kanada (Halifax, Saint John)'],
                ['CHL-4','-4 Chile (Santiago)'],
                ['PRY-4','-4 Paraguay (Asunçion)'],
                ['BMU-4','-4 Großbritannien (Bermuda)'],
                ['FLK-4','-4 Großbritannien (Falkland Inseln)'],
                ['TTB-4','-4 Trinidad&amp;Tobago'],
                ['CAN-3.5','-3.5 Kanada - Neufundland (St. Johns)'],
                ['GRL-3','-3 Dänemark - Grönland (Nuuk)'],
                ['ARG-3','-3 Argentinien (Buenos Aires)'],
                ['BRA2-3','-3 Brasilien (keine SZ)'],
                ['BRA1-3','-3 Brasilien (SZ)'],
                ['BRA-2','-2 Brasilien (keine SZ)'],
                ['PRT-1','-1 Portugal (Azoren)'],
                ['FRO-0','0 Dänemark - Faroer Inseln (Torshaven)'],
                ['IRL-0','0 Irland (Dublin)'],
                ['PRT-0','0 Portugal (Lissabon, Porto, Funchal)'],
                ['ESP-0','0 Spanien - Canary Islands (Las Palmas)'],
                ['GBR-0','0 Großbritannien (London)'],
                ['ALB+1','+1 Albanien (Tirana)'],
                ['AUT+1','+1 Österreich (Wien)'],
                ['BEL+1','+1 Belgien (Brüssel)'],
                ['CAI+1','+1 Caicos'],
                ['CHA+1','+1 Chatam'],
                ['HRV+1','+1 Hrvska (Zagreb)'],
                ['CZE+1','+1 Czech Republic (Prague)'],
                ['DNK+1','+1 Dänemark (Kopenhagen)'],
                ['FRA+1','+1 Frankreich (Nizza)'],
                ['GER+1','+1 Deutschland (Berlin)'],
                ['HUN+1','+1 Ungarn (Budapest)'],
                ['ITA+1','+1 Italien (Rom)'],
                ['LUX+1','+1 Luxemburg (Luxenburg)'],
                ['MAK+1','+1 Mazedonien (Skopje)'],
                ['NLD+1','+1 Niederlande (Amsterdam)'],
                ['NAM+1','+1 Namibia (Windhoek)'],
                ['NOR+1','+1 Norwegen (Oslo)'],
                ['POL+1','+1 Polen (Warszawa)'],
                ['SVK+1','+1 Slovakei (Breslau)'],
                ['ESP+1','+1 Spanien (Madrid)'],
                ['SWE+1','+1 Schweden (Stockholm)'],
                ['CHE+1','+1 Schweiz (Bern)'],
                ['GIB+1','+1 Großbritannien (Gibraltar)'],
                ['YUG+1','+1 Serbien Montenegro (Belgrad)'],
                ['WAT+1','+1 West Afrika'],
                ['BLR+2','+2 Weissrussland (Minsk)'],
                ['BGR+2','+2 Bulgarien (Sofia)'],
                ['CYP+2','+2 Zypern (Nicosia)'],
                ['CAT+2','+2 Zentral Afrika'],
                ['EGY+2','+2 Ägypten (Kairo)'],
                ['EST+2','+2 Estland (Tallin)'],
                ['FIN+2','+2 Finnland (Helsinki)'],
                ['GAZ+2','+2 Gazastreifen (Gaza)'],
                ['GRC+2','+2 Griechenland (Athen)'],
                ['ISR+2','+2 Israel (Tel Aviv)'],
                ['JOR+2','+2 Jordanien (Amman)'],
                ['LVA+2','+2 Litauen (Riga)'],
                ['LBN+2','+2 Libanon (Beirut)'],
                ['MDA+2','+2 Moldavien (Kishinev)'],
                ['RUS+2','+2 Russland (Kaliningrad)'],
                ['ROU+2','+2 Rumänien (Bucharest)'],
                ['SYR+2','+2 Syrien (Damascus)'],
                ['TUR+2','+2 Tärkei (Ankara)'],
                ['UKR+2','+2 Ukraine (Kiev, Odessa)'],
                ['EAT+3','+3 Ost Afrika'],
                ['IRQ+3','+3 Irak (Baghdad)'],
                ['RUS+3','+3 Russland (Moscow)'],
                ['IRN+3.5','+3.5 Iran (Teheran)'],
                ['ARM+4','+4 Armenien (Yerevan)'],
                ['AZE+4','+4 Azerbaijan (Baku)'],
                ['GEO+4','+4 Georgien (Tbilisi)'],
                ['KAZ+4','+4 Kazastan (Aqtau)'],
                ['RUS+4','+4 Russland (Samara)'],
                ['KAZ+5','+5 Kazastan (Aqtobe)'],
                ['KGZ+5','+5 Kyrgyzien (Bishkek)'],
                ['PAK+5','+5 Pakistan (Islamabad)'],
                ['RUS+5','+5 Russland (Chelyabinsk)'],
                ['IND+5.5','+5.5 Indien (Kalkutta)'],
                ['KAZ+6','+6 Kazastan (Astana, Almaty)'],
                ['RUS+6','+6 Russland (Novosibirsk, Omsk)'],
                ['RUS+7','+7 Russland (Krasnoyarsk)'],
                ['THA+7','+7 Thailand (Bangkok)'],
                ['CHN+7','+8 China (Peking)'],
                ['SGP+8','+8 Singapur (Singapur)'],
                ['KOR+8','+8 Korea (Seoul)'],
                ['AUS+8','+8 Australien (Perth)'],
                ['JPN+9','+9 Japan (Tokyo)'],
                ['AUS+9.5','+9.5 Australien (Adelaide)'],
                ['AUS2+9.5','+9.5 Australien (Darwin)'],
                ['AUS+10','+10 Australien (Sydney, Melbourne, Canberra)'],
                ['AUS2+10','+10 Australien (Brisbane)'],
                ['AUS3+10','+10 Australien (Hobart)'],
                ['RUS+10','+10 Russland (Vladivostok)'],
                ['AUS+10.5','+10.5 Australien (Lord Howe Islands)'],
                ['NZL+12','+12 Neuseeland (Wellington, Auckland)'],
                ['RUS+12','+12 Russland (Anadyr, Kamchatka)'],
                ['NZL+12.75','+12.75 Neuseeland (Chatham Islands)'],
                ['TON+13','+13 Tonga (Nukualofa)']
            ]   
        });

        return timezoneDataStore;
    },  
  
    
    loadTemplateData: function() {


        var templateDataStore = new Ext.data.JsonStore({
          baseParams: {
                method: 'Voipmanager.getTemplates',
                sort: 'name',
                dir: 'ASC',
                query: ''
            },
            root: 'results',
            totalProperty: 'totalcount',
            id: 'id',
            fields: [
                {name: 'id'},
                {name: 'name'}
            ],
            
            // turn on remote sorting
            remoteSort: true
        });

        templateDataStore.setDefaultSort('name', 'asc');

//        Ext.StoreMgr.add('templateStore', templateDataStore);               
        return templateDataStore;
    },    
    
    
    loadLocationData: function() {

        var locationDataStore = new Ext.data.JsonStore({
            baseParams: {
                method: 'Voipmanager.getLocation',
                sort: 'name',
                dir: 'ASC',
                query: ''
            },
            root: 'results',
            totalProperty: 'totalcount',
            id: 'id',
            fields: [
                {name: 'id'},
                {name: 'name'}
            ],
            
            // turn on remote sorting
            remoteSort: true
        });

        locationDataStore.setDefaultSort('name', 'asc');
               
        return locationDataStore;
    },
    
    
    loadSoftwareData: function() {

        var softwareDataStore = new Ext.data.JsonStore({
            baseParams: {
                method: 'Voipmanager.getSoftware',
                sort: 'description',
                dir: 'ASC'
            },
            root: 'results',
            totalProperty: 'totalcount',
            id: 'id',
            fields: [
                {name: 'id'},
                {name: 'model'},
                {name: 'description'}
            ],
            
            // turn on remote sorting
            remoteSort: true
        });

        softwareDataStore.setDefaultSort('description', 'asc');            
         
        return softwareDataStore;
    },
    
    loadKeylayoutData: function() {

        var keylayoutDataStore = new Ext.data.JsonStore({
            baseParams: {
                method: 'Voipmanager.getKeylayout',
                sort: 'description',
                dir: 'ASC'
            },
            root: 'results',
            totalProperty: 'totalcount',
            id: 'id',
            fields: [
                {name: 'id'},
                {name: 'model'},
                {name: 'description'}
            ],
            
            // turn on remote sorting
            remoteSort: true
        });

        keylayoutDataStore.setDefaultSort('description', 'asc');

//        Ext.StoreMgr.add('swData', keylayoutDataStore);               
         
        return keylayoutDataStore;
    },
    
    loadSettingsData: function(_query) {

        var settingsDataStore = new Ext.data.JsonStore({
            baseParams: {
                method: 'Voipmanager.getSettings',
                sort: 'description',
                dir: 'ASC'
            },
            root: 'results',
            totalProperty: 'totalcount',
            id: 'id',
            fields: [
                {name: 'id'},
                {name: 'model'},
                {name: 'description'}
            ],
            
            // turn on remote sorting
            remoteSort: true
        });

        settingsDataStore.setDefaultSort('description', 'asc');

//        Ext.StoreMgr.add('swData', settingsDataStore);               
         
        return settingsDataStore;
    }            
};
    
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
            Tine.Tinebase.Common.openWindow('linesWindow', 'index.php?method=Voipmanager.editLine&lineId=', 500, 350);
        },

        /**
         * onclick handler for editLine
         */
        editLine: function(_button, _event) 
        {
            var selectedRows = Ext.getCmp('Voipmanager_Lines_Grid').getSelectionModel().getSelections();
            var lineId = selectedRows[0].id;
            
            Tine.Tinebase.Common.openWindow('linesWindow', 'index.php?method=Voipmanager.editLine&lineId=' + lineId, 500, 350);
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
            { resizable: true, id: 'name', header: this.translation._('name'), dataIndex: 'name', width: 80 },
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
            { resizable: true, id: 'fullcontact', header: this.translation._('ASTERISK_LINES_full contact'), dataIndex: 'fullcontact', width: 230 },
            { resizable: true, id: 'host', header: this.translation._('ASTERISK_LINES_host'), dataIndex: 'host', width: 30, hidden: true },
            { resizable: true, id: 'insecure', header: this.translation._('ASTERISK_LINES_insecure'), dataIndex: 'insecure', width: 30, hidden: true },
            { resizable: true, id: 'language', header: this.translation._('ASTERISK_LINES_language'), dataIndex: 'language', width: 30, hidden: true },
            { resizable: true, id: 'mailbox', header: this.translation._('ASTERISK_LINES_mailbox'), dataIndex: 'mailbox', width: 30, hidden: true },
            { resizable: true, id: 'md5secret', header: this.translation._('ASTERISK_LINES_md5 secret'), dataIndex: 'md5secret', width: 30, hidden: true },
            { resizable: true, id: 'nat', header: this.translation._('ASTERISK_LINES_nat'), dataIndex: 'nat', width: 30, hidden: true },
            { resizable: true, id: 'deny', header: this.translation._('ASTERISK_LINES_deny'), dataIndex: 'deny', width: 30, hidden: true },
            { resizable: true, id: 'permit', header: this.translation._('ASTERISK_LINES_permit'), dataIndex: 'permit', width: 30, hidden: true },
            { resizable: true, id: 'mask', header: this.translation._('ASTERISK_LINES_mask'), dataIndex: 'mask', width: 30, hidden: true },
            { resizable: true, id: 'pickupgroup', header: this.translation._('ASTERISK_LINES_pickup group'), dataIndex: 'pickupgroup', width: 30, hidden: true },
            { resizable: true, id: 'port', header: this.translation._('ASTERISK_LINES_port'), dataIndex: 'port', width: 30, hidden: true },
            { resizable: true, id: 'qualify', header: this.translation._('ASTERISK_LINES_qualify'), dataIndex: 'qualify', width: 30, hidden: true },
            { resizable: true, id: 'restrictcid', header: this.translation._('ASTERISK_LINES_retrict cid'), dataIndex: 'restrictcid', width: 30, hidden: true },
            { resizable: true, id: 'rtptimeout', header: this.translation._('ASTERISK_LINES_rtp timeout'), dataIndex: 'rtptimeout', width: 30, hidden: true },
            { resizable: true, id: 'rtpholdtimeout', header: this.translation._('ASTERISK_LINES_rtp hold timeout'), dataIndex: 'rtpholdtimeout', width: 30, hidden: true },
            { resizable: true, id: 'secret', header: this.translation._('ASTERISK_LINES_secret'), dataIndex: 'secret', width: 30, hidden: true },
            { resizable: true, id: 'type', header: this.translation._('ASTERISK_LINES_type'), dataIndex: 'type', width: 30, hidden: true },
            { resizable: true, id: 'username', header: this.translation._('ASTERISK_LINES_username'), dataIndex: 'username', width: 30, hidden: true },
            { resizable: true, id: 'disallow', header: this.translation._('ASTERISK_LINES_disallow'), dataIndex: 'disallow', width: 30, hidden: true },
            { resizable: true, id: 'allow', header: this.translation._('ASTERISK_LINES_allow'), dataIndex: 'allow', width: 30, hidden: true },
            { resizable: true, id: 'musiconhold', header: this.translation._('ASTERISK_LINES_music on hold'), dataIndex: 'musiconhold', width: 30, hidden: true },
            { resizable: true, id: 'regseconds', header: this.translation._('ASTERISK_LINES_reg seconds'), dataIndex: 'regseconds  	', width: 30, hidden: true },
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
            autoExpandColumn: 'fullcontact',
            border: false,
            view: new Ext.grid.GridView({
                autoFill: true,
                forceFit:true,
                ignoreAdd: true,
                emptyText: 'No lines to display'
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
                Tine.Tinebase.Common.openWindow('linesWindow', 'index.php?method=Voipmanager.editLine&lineId=' + record.data.id, 500, 350);
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
        
        // we set them directly, because this properties also need to be set when paging
        switch(_node.attributes.dataPanelType) {
            case 'phones':
                dataStore.baseParams.method = 'Voipmanager.getPhones';
                break;
                
            case 'location':
                dataStore.baseParams.method = 'Voipmanager.getLocation';
                break;                
                
            case 'templates':
                dataStore.baseParams.method = 'Voipmanager.getTemplates';
                break;                 
                
            case 'lines':
                dataStore.baseParams.method = 'Voipmanager.getLines';
                break;                
                
            case 'settings':
                dataStore.baseParams.method = 'Voipmanager.getSettings';
                break;                
                
            case 'software':
                dataStore.baseParams.method = 'Voipmanager.getSoftware';
                break;                                                                
        }
        
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


