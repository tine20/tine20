/*
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  widgets
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 * @todo        add admin mode
 * @todo        add lock to force prefs
 * @todo        add filter toolbar
 * @todo        add preference label translations
 * @todo        use proxy store?
 * @todo        update js registry?
 */

Ext.namespace('Tine.widgets');

Ext.namespace('Tine.widgets.dialog');

/**
 * 'Edit Preferences' dialog
 */
/**
 * @class Tine.widgets.dialog.Preferences
 * @extends Ext.FormPanel
 * @constructor
 * @param {Object} config The configuration options.
 */
Tine.widgets.dialog.Preferences = Ext.extend(Ext.FormPanel, {
    /**
     * @cfg {Array} tbarItems
     * additional toolbar items (defaults to false)
     */
    tbarItems: false,
    
    /**
     * @property window {Ext.Window|Ext.ux.PopupWindow|Ext.Air.Window}
     */
    /**
     * @property {Number} loadRequest 
     * transaction id of loadData request
     */
    /**
     * @property loadMask {Ext.LoadMask}
     */
    
    /**
     * @property {Locale.gettext} i18n
     */
    i18n: null,

    /**
     * @property {Tine.widgets.dialog.PreferencesCardPanel} prefsCardPanel
     */
    prefsCardPanel: null,
    
    /**
     * @property {Tine.widgets.dialog.PreferencesApplicationsPanel} treePanel
     */
    treePanel: null,
    
    /**
     * @property {Object} prefPanels
     * here we store the pref panels for all apps
     */    
    prefPanels: {},
    
    // private
    bodyStyle:'padding:5px',
    layout: 'fit',
    cls: 'tw-editdialog',
    anchor:'100% 100%',
    buttonAlign: 'right',
    
    //private
    initComponent: function(){
        this.addEvents(
            /**
             * @event cancel
             * Fired when user pressed cancel button
             */
            'cancel',
            /**
             * @event saveAndClose
             * Fired when user pressed OK button
             */
            'saveAndClose',
            /**
             * @event update
             * @desc  Fired when the record got updated
             * @param {Json String} data data of the entry
             */
            'update'
        );
        
        this.i18n = new Locale.Gettext();
        this.i18n.textdomain('Tinebase');
        
        // init actions
        this.initActions();
        // init buttons and tbar
        this.initButtons();
        // init preferences
        this.initPreferences();
        // get items for this dialog
        this.items = this.getItems();
        
        Tine.widgets.dialog.Preferences.superclass.initComponent.call(this);
    },
    
    /**
     * init actions
     */
    initActions: function() {
        this.action_saveAndClose = new Ext.Action({
            //requiredGrant: 'editGrant',
            text: _('Ok'),
            minWidth: 70,
            scope: this,
            handler: this.onSaveAndClose,
            iconCls: 'action_saveAndClose'
        });
    
        this.action_cancel = new Ext.Action({
            text: _('Cancel'),
            minWidth: 70,
            scope: this,
            handler: this.onCancel,
            iconCls: 'action_cancel'
        });
    },
    
    /**
     * init buttons
     */
    initButtons: function() {
        this.buttons = [
            this.action_cancel,
            this.action_saveAndClose
        ];
       
        if (this.tbarItems) {
            this.tbar = new Ext.Toolbar({
                items: this.tbarItems
            });
        }
    },
    
    /**
     * init preferences to edit (does nothing at the moment)
     */
    initPreferences: function() {
    },
    
    /**
     * returns dialog
     * 
     * NOTE: when this method gets called, all initalisation is done.
     */
    getItems: function() {
    	this.prefsCardPanel = new Tine.widgets.dialog.PreferencesCardPanel({
            region: 'center'
        });
        this.treePanel = new Tine.widgets.dialog.PreferencesApplicationsPanel({
            title: _('Applications'),
            region: 'west',
            width: 200,
            frame: true
        })
        return [{
        	xtype: 'panel',
            autoScroll: true,
            border: true,
            frame: true,
            layout: 'border',
            height: 424,
            items: [
                this.treePanel,
                this.prefsCardPanel
            ]
        }];
    },
    
    /**
     * @private
     */
    onRender : function(ct, position){
        Tine.widgets.dialog.Preferences.superclass.onRender.call(this, ct, position);
        this.loadMask = new Ext.LoadMask(ct, {msg: _('Loading ...')});
        //this.loadMask.show();
    },
    
    /**
     * @private
     */
    onCancel: function(){
        this.fireEvent('cancel');
        this.purgeListeners();
        this.window.close();
    },
    
    /**
     * @private
     */
    onSaveAndClose: function(button, event){
        this.onApplyChanges(button, event, true);
        this.fireEvent('saveAndClose');
    },
    
    /**
     * generic apply changes handler
     * 
     */
    onApplyChanges: function(button, event, closeWindow) {
    	
    	this.loadMask.show();
    	
    	// get values from card panels
    	var data = {};
    	for each (panel in this.prefPanels) {
    		//console.log(panel);
    		data[panel.appName] = {};
            for (var j=0; j < panel.items.length; j++) {
            	if (panel.items.items[j] && panel.items.items[j].name) {
                    data[panel.appName][panel.items.items[j].name] = panel.items.items[j].getValue();
            	}
            }
    	}
    	/*
    	this.prefPanels.each(function(panel) {
            for (var j=0; j < panel.items.length; j++) {
                data[panel.items.items[j].name] = panel.items.items[j].getValue();
            }    		
        }, this);
        */
    	
    	// save preference data
    	//console.log(data);
    	Ext.Ajax.request({
            scope: this,
            params: {
                method: 'Tinebase.savePreferences',
                data: Ext.util.JSON.encode(data)
            },
            success: function(response) {
                this.loadMask.hide();
                
                // reload mainscreen (only if timezone or locale have changed
                if (data.Tinebase && 
                        (data.Tinebase.locale   != Tine.Tinebase.registry.get('locale').locale ||
                         data.Tinebase.timezone != Tine.Tinebase.registry.get('timeZone'))
                ) {
                	/*
                	console.log('reload');
                	console.log(data.Tinebase);
                	console.log(Tine.Tinebase.registry.get('locale').locale);
                	console.log(Tine.Tinebase.registry.get('timeZone'));
                	*/
                    var mainWindow = Ext.ux.PopupWindowGroup.getMainWindow(); 
                    mainWindow.location = window.location.href.replace(/#+.*/, '');
                }
                
                if (closeWindow) {
                    this.purgeListeners();
                    this.window.close();
                }
            },
            failure: function (response) {
                Ext.MessageBox.alert(_('Errors'), _('Saving of preferences failed.'));    
            }
        });
    },
    
    /**
     * init app preferences store
     * 
     * @param {String} appName
     * 
     * @todo add filter
     * @todo use generic json backend here?
     */
    initPrefStore: function(appName) {
    	this.loadMask.show();
        var store = new Ext.data.JsonStore({
            fields: Tine.Tinebase.Model.Preference,
            baseParams: {
                method: 'Tinebase.searchPreferencesForApplication',
                applicationName: appName,
                filter: ''
            },
            listeners: {
                load: this.onStoreLoad,
                scope: this
            },
            root: 'results',
            totalProperty: 'totalcount',
            id: 'id',
            remoteSort: false
        });
        
        //console.log('created store for ' + appName);
        store.load();
    },

    /**
     * called after a new set of preference Records has been loaded
     * 
     * @param  {Ext.data.Store} this.store
     * @param  {Array}          loaded records
     * @param  {Array}          load options
     */
    onStoreLoad: function(store, records, options) {
        //console.log('loaded');
        //console.log(store);
        var appName = store.baseParams.applicationName;
        
        var card = new Tine.widgets.dialog.PreferencesPanel({
            prefStore: store,
            appName: appName
        });
        
        // add to panel registry
        this.prefPanels[appName] = card;
        
        this.activateCard(card, false);
        this.loadMask.hide();
    },
    
    /**
     * activateCard in preferences panel
     * 
     * @param {Tine.widgets.dialog.PreferencesPanel} panel
     * @param {boolean} exists
     */
    activateCard: function(panel, exists) {
    	if (!exists) {
            this.prefsCardPanel.add(panel);
            this.prefsCardPanel.layout.container.add(panel);
    	}
        this.prefsCardPanel.layout.setActiveItem(panel.id);
        panel.doLayout();    	
    },
    
    /**
     * showPrefsForApp 
     * - check stores (create new store if not exists)
     * - activate pref panel for app
     * 
     * @param {String} appName
     */
    showPrefsForApp: function(appName) {
        // check stores/panels
        if (!this.prefPanels[appName]) {
            // add new card + store
            this.initPrefStore(appName);
        } else {
        	this.activateCard(this.prefPanels[appName], true);
        }
    }
});

/**
 * preferences application tree panel
 * -> this panel is filled with the preferences subpanels containing the pref stores for the apps
 * 
 * @todo use fire event in parent panel?
 */
Tine.widgets.dialog.PreferencesApplicationsPanel = Ext.extend(Ext.tree.TreePanel, {

	// presets
    iconCls: 'x-new-application',
    rootVisible: false,
    border: false,
    autoScroll: true,
    
    /**
     * initComponent
     * 
     */
    initComponent: function(){
        
        Tine.widgets.dialog.PreferencesApplicationsPanel.superclass.initComponent.call(this);
        
        this.initTreeNodes();
        this.initHandlers();
    },

    /**
     * afterRender -> selects Tinebase prefs panel
     * 
     * @private
     * 
     * @todo activate default app/prefs after render
     */
    afterRender: function() {
        Tine.widgets.dialog.PreferencesApplicationsPanel.superclass.afterRender.call(this);

        /*
        this.expandPath('/root/Tinebase');
        this.selectPath('/root/Tinebase');
        */
    },
    
    /**
     * initTreeNodes with Tinebase and apps prefs
     * 
     * @private
     */
    initTreeNodes: function() {
        var treeRoot = new Ext.tree.TreeNode({
            text: 'root',
            draggable:false,
            allowDrop:false,
            id:'root'
        });
        this.setRootNode(treeRoot);
        
        // add tinebase/general prefs node
        var generalNode = new Ext.tree.TreeNode({
            text: _('General Preferences'),
            cls: 'file',
            id: 'Tinebase',
            leaf: null,
            expanded: true
        });
        treeRoot.appendChild(generalNode);

        // add all apps
        var allApps = Tine.Tinebase.appMgr.getAll();

        // console.log(allApps);
        allApps.each(function(app) {
            var node = new Ext.tree.TreeNode({
                text: app.getTitle(),
                cls: 'file',
                id: app.appName,
                leaf: null
            });
    
            treeRoot.appendChild(node);
        }, this);
    },
    
    /**
     * initTreeNodes with Tinebase and apps prefs
     * 
     * @private
     */
    initHandlers: function() {
        this.on('click', function(node){
            // note: if node is clicked, it is not selected!
            node.getOwnerTree().selectPath(node.getPath());
            node.expand();
            
            //console.log(node);
            
            // get parent pref panel
            var parentPanel = this.findParentByType(Tine.widgets.dialog.Preferences);
            //console.log(parentPanel);

            // add panel to card panel to show prefs for chosen app
            parentPanel.showPrefsForApp(node.id);
            
        }, this);
        
        this.on('beforeexpand', function(_panel) {
            if(_panel.getSelectionModel().getSelectedNode() === null) {
                _panel.expandPath('/root');
                _panel.selectPath('/root/Tinebase');
            }
            _panel.fireEvent('click', _panel.getSelectionModel().getSelectedNode());
        }, this);
    }
});

/**
 * preferences card panel
 * -> this panel is filled with the preferences subpanels containing the pref stores for the apps
 * 
 */
Tine.widgets.dialog.PreferencesCardPanel = Ext.extend(Ext.Panel, {
    
    //private
    layout: 'card',
    border: true,
    //frame: true,
    labelAlign: 'top',
    autoScroll: true,
    defaults: {
        anchor: '100%'
    },
    
    initComponent: function() {
        this.title = _('Preferences');
        //this.html = _('Select Application or General Preferences');
        Tine.widgets.dialog.PreferencesCardPanel.superclass.initComponent.call(this);
    }
});

/**
 * preferences panel with the preference input fields for an application
 * 
 */
Tine.widgets.dialog.PreferencesPanel = Ext.extend(Ext.Panel, {
    
	/**
	 * the prefs store
	 * @cfg {Ext.data.Store}
	 */
	prefStore: null,
	
    /**
     * @cfg {string} app name
     */
    appName: 'Tinebase',
	
    //private
    layout: 'form',
    border: true,
    labelAlign: 'top',
    autoScroll: true,
    defaults: {
        anchor: '95%',
        labelSeparator: ''
    },
    bodyStyle: 'padding:5px',
    
    initComponent: function() {
        if (this.prefStore) {
            
            this.items = [];
            this.prefStore.each(function(pref) {
        	    // check if options available -> use combobox
                var fieldDef = {
                    fieldLabel: _(pref.get('name')),
                    name: pref.get('name'),
                    value: pref.get('value'),
                    xtype: (pref.get('options') && pref.get('options').length > 0) ? 'combo' : 'textfield'
                };
                
                if (pref.get('options') && pref.get('options').length > 0) {
                	// add additional combobox config
                	fieldDef.store = pref.get('options');
                	fieldDef.mode = 'local';
                    fieldDef.forceSelection = true;
                    fieldDef.triggerAction = 'all';
                }
                
                //console.log(fieldDef);
                try {
                    var fieldObj = Ext.ComponentMgr.create(fieldDef);
                    this.items.push(fieldObj);
                    
                    // ugh a bit ugly
                    pref.fieldObj = fieldObj;
                } catch (e) {
                	//console.log(e);
                    console.error('Unable to create preference field "' + pref.get('name') + '". Check definition!');
                    this.prefStore.remove(pref);
                }
            }, this);

        } else {
            this.html = '<div class="x-grid-empty">' + _('There are no preferences yet') + "</div>";
        }
        
        Tine.widgets.dialog.PreferencesPanel.superclass.initComponent.call(this);
    }
});

/**
 * Timetracker Edit Popup
 */
Tine.widgets.dialog.Preferences.openWindow = function (config) {
    //var id = (config.record && config.record.id) ? config.record.id : 0;
    var window = Tine.WindowFactory.getWindow({
        width: 800,
        height: 470,
        name: 'Preferences',
        contentPanelConstructor: 'Tine.widgets.dialog.Preferences',
        contentPanelConstructorConfig: config
    });
    return window;
};
