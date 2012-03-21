/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 * @todo        check this for deprecated code
 * @todo        use snom phone grid as initial grid
 */
 
Ext.namespace('Tine.Voipmanager');

Tine.Voipmanager.PhoneTreePanel = Ext.extend(Ext.tree.TreePanel,{
    rootVisible: false,
    border: false,
    
    initComponent: function() {
        this.root = {
            id: 'root',
            children: [{
                text: this.app.i18n._('Asterisk'),
                cls: 'treemain',
                allowDrag: false,
                allowDrop: true,
                id: 'Asterisk',
                icon: false,
                children: [{
                    text: this.app.i18n._('SipPeer'),
                    cls: "treemain",
                    allowDrag: false,
                    allowDrop: true,
                    id: "SipPeer",
                    icon: false,
                    children: [],
                    leaf: true,
                    expanded: true
                }, {
                    text: this.app.i18n._('Dialplan'),
                    cls: "treemain",
                    allowDrag: false,
                    allowDrop: true,
                    id: "Dialplan",
                    icon: false,
                    children: [],
                    leaf: true,
                    disabled: true,
                    expanded: true
                }, {
                    text: this.app.i18n._('Context'),
                    cls: "treemain",
                    allowDrag: false,
                    allowDrop: true,
                    id: "Context",
                    icon: false,
                    children: [],
                    leaf: true,
                    expanded: true
                }, {
                    text: this.app.i18n._('Voicemail'),
                    cls: "treemain",
                    allowDrag: false,
                    allowDrop: true,
                    id: "Voicemail",
                    icon: false,
                    children: [],
                    leaf: true,
                    expanded: true
                }, {
                    text: this.app.i18n._('Meetme'),
                    cls: "treemain",
                    allowDrag: false,
                    allowDrop: true,
                    id: "Meetme",
                    icon: false,
                    children: [],
                    leaf: true,
                    expanded: true
                }, {
                    text: this.app.i18n._('Queues'),
                    cls: "treemain",
                    allowDrag: false,
                    allowDrop: true,
                    id: "Queues",
                    icon: false,
                    disabled: true,
                    children: [],
                    leaf: true,
                    expanded: true
                }],
                leaf: null,
                expanded: true,
                dataPanelType: 'asterisk',
                viewRight: 'asterisk'
            }, {
                text: this.app.i18n._('Snom'),
                cls: 'treemain',
                allowDrag: false,
                allowDrop: true,
                id: 'Snom',
                icon: false,
                children: [{
                    text: this.app.i18n._('Phones'),
                    cls: 'treemain',
                    allowDrag: false,
                    allowDrop: true,
                    id: 'Phone',
                    icon: false,
                    children: [],
                    leaf: true,
                    expanded: true
                }, {
                    text: this.app.i18n._('Location'),
                    cls: "treemain",
                    allowDrag: false,
                    allowDrop: true,
                    id: "Location",
                    icon: false,
                    children: [],
                    leaf: true,
                    expanded: true
                }, {
                    text: this.app.i18n._('Templates'),
                    cls: "treemain",
                    allowDrag: false,
                    allowDrop: true,
                    id: "Template",
                    icon: false,
                    children: [],
                    leaf: null,
                    expanded: true
                }, {
                    text: this.app.i18n._('Keylayout'),
                    cls: "treemain",
                    allowDrag: false,
                    allowDrop: true,
                    id: "Keylayout",
                    icon: false,
                    children: [],
                    leaf: null,
                    expanded: true,
                    disabled: true
                }, {
                    text: this.app.i18n._('Setting'),
                    cls: "treemain",
                    allowDrag: false,
                    allowDrop: true,
                    id: "Setting",
                    icon: false,
                    children: [],
                    leaf: null,
                    expanded: true
                }, {
                    text: this.app.i18n._('Software'),
                    cls: "treemain",
                    allowDrag: false,
                    allowDrop: true,
                    id: "Software",
                    icon: false,
                    children: [],
                    leaf: null,
                    expanded: true
                }],
                leaf: null,
                expanded: true,
                dataPanelType: 'snom',
                viewRight: 'snom'
            }]
        };
        
        Tine.Voipmanager.PhoneTreePanel.superclass.initComponent.call(this);
        
        this.on('click', function(node) {
            if (node.disabled) {
                return false;
            }
            
            var contentType = node.getPath().split('/')[3];
            var contentGroup = node.getPath().split('/')[2];
                                
            this.app.getMainScreen().activeContentType = contentType;
            this.app.getMainScreen().activeContentGroup = contentGroup;
            this.app.getMainScreen().show();
            
            
            
        }, this);
    },
    
    /**
     * @private
     */
    afterRender: function() {
        Tine.Voipmanager.PhoneTreePanel.superclass.afterRender.call(this);
        var type = this.app.getMainScreen().activeContentType;

        this.expandPath('/root/snom');
        this.selectPath('/root/snom/phones');
    },
    
    /**
     * returns a filter plugin to be used in a grid
     * 
     * @deprecated
     * @todo remove that
     */
    getFilterPlugin: function() {
        if (!this.filterPlugin) {
            var scope = this;
            this.filterPlugin = new Tine.widgets.grid.FilterPlugin({
                getValue: function() {
                    var nodeAttributes = scope.getSelectionModel().getSelectedNode().attributes || {};
                    return [
                        {field: 'containerType', operator: 'equals', value: nodeAttributes.containerType ? nodeAttributes.containerType : 'all' },
                        {field: 'container',     operator: 'equals', value: nodeAttributes.container ? nodeAttributes.container.id : null       },
                        {field: 'owner',         operator: 'equals', value: nodeAttributes.owner ? nodeAttributes.owner.accountId : null        }
                    ];
                }
            });
        }
        
        return this.filterPlugin;
    }
});

/**
 * default Asterisk.Context backend
 */
Tine.Voipmanager.AsteriskContextBackend = new Tine.Tinebase.data.RecordProxy({
    appName: 'Voipmanager',
    modelName: 'AsteriskContext',
    recordClass: Tine.Voipmanager.Model.AsteriskContext
});


/**
 * default Asterisk.SipPeer backend
 */
Tine.Voipmanager.AsteriskSipPeerBackend = new Tine.Tinebase.data.RecordProxy({
    appName: 'Voipmanager',
    modelName: 'AsteriskSipPeer',
    recordClass: Tine.Voipmanager.Model.AsteriskSipPeer
});

/**
 * default Asterisk.Voicemail backend
 */
Tine.Voipmanager.AsteriskVoicemailBackend = new Tine.Tinebase.data.RecordProxy({
    appName: 'Voipmanager',
    modelName: 'AsteriskVoicemail',
    recordClass: Tine.Voipmanager.Model.AsteriskVoicemail
});

/**
 * default Asterisk.Meetme backend
 */
Tine.Voipmanager.AsteriskMeetmeBackend = new Tine.Tinebase.data.RecordProxy({
    appName: 'Voipmanager',
    modelName: 'AsteriskMeetme',
    recordClass: Tine.Voipmanager.Model.AsteriskMeetme
});



/**
 * default Snom.Phone backend
 */
Tine.Voipmanager.SnomPhoneBackend = new Tine.Tinebase.data.RecordProxy({
    appName: 'Voipmanager',
    modelName: 'SnomPhone',
    recordClass: Tine.Voipmanager.Model.SnomPhone
});

/**
 * default Snom.Location backend
 */
Tine.Voipmanager.SnomLocationBackend = new Tine.Tinebase.data.RecordProxy({
    appName: 'Voipmanager',
    modelName: 'SnomLocation',
    recordClass: Tine.Voipmanager.Model.SnomLocation
});

/**
 * default Snom.Template backend
 */
Tine.Voipmanager.SnomTemplateBackend = new Tine.Tinebase.data.RecordProxy({
    appName: 'Voipmanager',
    modelName: 'SnomTemplate',
    recordClass: Tine.Voipmanager.Model.SnomTemplate
});

/**
 * default Snom.Software backend
 */
Tine.Voipmanager.SnomSoftwareBackend = new Tine.Tinebase.data.RecordProxy({
    appName: 'Voipmanager',
    modelName: 'SnomSoftware',
    recordClass: Tine.Voipmanager.Model.SnomSoftware
});

/**
 * default Snom.SoftwareImage backend
 */
Tine.Voipmanager.SnomSoftwareImageBackend = new Tine.Tinebase.data.RecordProxy({
    appName: 'Voipmanager',
    modelName: 'SnomSoftwareImage',
    recordClass: Tine.Voipmanager.Model.SnomSoftwareImage
});

/**
 * default Snom.Line backend
 */
Tine.Voipmanager.SnomLineBackend = new Tine.Tinebase.data.RecordProxy({
    appName: 'Voipmanager',
    modelName: 'SnomLine',
    recordClass: Tine.Voipmanager.Model.SnomLine
});

/**
 * default Snom.Setting backend
 */
Tine.Voipmanager.SnomSettingBackend = new Tine.Tinebase.data.RecordProxy({
    appName: 'Voipmanager',
    modelName: 'SnomSetting',
    recordClass: Tine.Voipmanager.Model.SnomSetting
});

/*
 * default Snom.Owner backend
 * @depricated
Tine.Voipmanager.SnomOwnerBackend = new Tine.Tinebase.data.RecordProxy({
    appName: 'Voipmanager',
    modelName: 'SnomOwner',
    recordClass: Tine.Voipmanager.Model.SnomOwner
});
*/




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
                method: 'Voipmanager.getSnomTemplates',
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
                method: 'Voipmanager.getSnomLocations',
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
                method: 'Voipmanager.getSnomKeylayouts',
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
                method: 'Voipmanager.getSnomSettings',
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
