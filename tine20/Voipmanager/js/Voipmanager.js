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
  
  
   loadPhoneModelData: function(_model) {

        var phoneModelDataStore = new Ext.data.SimpleStore({
            autoLoad: true,
            id: 'model',
            fields: ['id', 'model','lines'],
            data: [
                ['snom300','Snom 300','4'],
                ['snom320','Snom 320','12'],
                ['snom360','Snom 360','12'],
                ['snom370','Snom 370','12']
            ]   
        });

        if (_model) {
            var _idx = phoneModelDataStore.find('model', _model);
            return phoneModelDataStore.getAt(_idx);
        }
        
        return phoneModelDataStore;
    },    
  
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
                method: 'Voipmanager.searchSnomSoftware',
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
