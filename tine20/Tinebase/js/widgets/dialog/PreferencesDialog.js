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
 * @todo        add filter toolbar
 * @todo        add preference label translations
 * @todo        move that to dialog subdir?
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
     * @property {Tine.widgets.dialog.PreferencesTreePanel} treePanel
     */
    treePanel: null,
    
    /**
     * @property {Object} prefPanels
     * here we store the pref panels for all apps
     */    
    prefPanels: {},

    /**
     * @property {boolean} adminMode
     * when adminMode is activated -> show defaults/forced values
     */    
    adminMode: false,

    /**
     * @property {Object} prefPanels
     * here we store the pref panels for all apps [admin mode]
     */    
    adminPrefPanels: {},
    
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
        // get items for this dialog
        this.items = this.getItems();
        
        Tine.widgets.dialog.Preferences.superclass.initComponent.call(this);
    },
    
    /**
     * init actions
     * 
     * @todo only allow admin mode if user has admin right
     */
    initActions: function() {
        this.action_saveAndClose = new Ext.Action({
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

        this.action_switchAdminMode = new Ext.Action({
            text: _('Admin Mode'),
            minWidth: 70,
            scope: this,
            handler: this.onSwitchAdminMode,
            iconCls: 'action_adminMode',
            enableToggle: true
            //disabled: true
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
       
        this.tbar = new Ext.Toolbar({
            items: [
                this.action_switchAdminMode
            ]
        });
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
        this.treePanel = new Tine.widgets.dialog.PreferencesTreePanel({
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
     * @todo display alert message if there are changed panels with data from the other mode
     * @todo submit 'lock' info as well in admin mode
     */
    onApplyChanges: function(button, event, closeWindow) {
    	
    	this.loadMask.show();
    	
    	// get values from card panels
    	var data = {};
    	var panelsToSave = (this.adminMode) ? this.adminPrefPanels : this.prefPanels;
    	for each (panel in panelsToSave) {
    		console.log(panel);
    		data[panel.appName] = {};
            for (var j=0; j < panel.items.length; j++) {
            	var item = panel.items.items[j];
            	if (item && item.name) {
                    if (this.adminMode) {
                    	data[panel.appName][item.prefId] = {value: item.getValue()};
                    	data[panel.appName][item.prefId].type = (Ext.getCmp(item.name + '_writable').getValue() == 1) ? 'default' : 'forced';
                    } else {
                        data[panel.appName][item.name] = {value: item.getValue()};
                    }
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
    	console.log(data);
    	Ext.Ajax.request({
            scope: this,
            params: {
                method: 'Tinebase.savePreferences',
                data: Ext.util.JSON.encode(data),
                adminMode: this.adminMode
            },
            success: function(response) {
                this.loadMask.hide();
                
                // reload mainscreen (only if timezone or locale have changed
                if (!this.adminMode && data.Tinebase && 
                        (data.Tinebase.locale   != Tine.Tinebase.registry.get('locale').locale ||
                         data.Tinebase.timezone != Tine.Tinebase.registry.get('timeZone'))
                ) {
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
     * onSwitchAdminMode
     * 
     * @private
     * 
     * @todo enable/disable apps according to admin right for applications
     */
    onSwitchAdminMode: function(button, event) {
    	this.adminMode = (!this.adminMode);
    	
        if (this.adminMode) {
        	this.prefsCardPanel.addClass('prefpanel_adminMode');
        } else {
        	this.prefsCardPanel.removeClass('prefpanel_adminMode');
        }
        
        // activate panel in card panel
        var selectedNode = this.treePanel.getSelectionModel().getSelectedNode();
        if (selectedNode) {
            this.showPrefsForApp(this.treePanel.getSelectionModel().getSelectedNode().id);
        }
        
        this.treePanel.checkGrants(this.adminMode);
    },

	/**
     * init app preferences store
     * 
     * @param {String} appName
     * 
     * @todo use generic json backend here?
     */
    initPrefStore: function(appName) {
    	this.loadMask.show();
    	
    	// set filter to get only default/forced values if in admin mode
    	var filter = (this.adminMode) ? [{field: 'account', operator: 'equals', value: {accountId: 0, accountType: 'anyone'}}] : '';
    	
        var store = new Ext.data.JsonStore({
            fields: Tine.Tinebase.Model.Preference,
            baseParams: {
                method: 'Tinebase.searchPreferencesForApplication',
                applicationName: appName,
                filter: Ext.util.JSON.encode(filter)
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
            appName: appName,
            adminMode: this.adminMode
        });
        
        card.on('change', function(appName) {
            // mark card as changed in tree
        	var node = this.treePanel.getNodeById(appName);
        	node.setText(node.text + '*');
        }, this);
        
        // add to panel registry
        if (this.adminMode) {
            this.adminPrefPanels[appName] = card;
        } else {
        	this.prefPanels[appName] = card;
        }
        
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
    	var panel = (this.adminMode) ? this.adminPrefPanels[appName] : this.prefPanels[appName];
    	
    	if (!this.adminMode) {
    		// check grant for pref and enable/disable button
			this.action_switchAdminMode.setDisabled(!Tine.Tinebase.common.hasRight('admin', appName));
    	}
    	
        // check stores/panels
        if (!panel) {
            // add new card + store
            this.initPrefStore(appName);
        } else {
        	this.activateCard(panel, true);
        }
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
    border: false,
    frame: true,
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
 * @todo make admin mode work for textfields
 * @todo add checkbox type
 */
Tine.widgets.dialog.PreferencesPanel = Ext.extend(Ext.Panel, {
    
	/**
	 * the prefs store
	 * @cfg {Ext.data.Store}
	 */
	prefStore: null,
	
    /**
     * @cfg {String} appName
     */
    appName: 'Tinebase',

    /**
     * @cfg {Boolean} adminMode activated?
     */
    adminMode: false,
    
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
    	
        this.addEvents(
            /**
             * @event change
             * @param appName
             * Fired when a value is changed
             */
            'change'
        );    	
    	
        if (this.prefStore && this.prefStore.getCount() > 0) {
            
            this.items = [];
            this.prefStore.each(function(pref) {
            	            	
        	    // check if options available -> use combobox or textfield
                var fieldDef = {
                    fieldLabel: _(pref.get('name')),
                    name: pref.get('name'),
                    value: pref.get('value'),
                    listeners: {
                    	scope: this,
                    	change: function(field, newValue, oldValue) {
                    		// fire change event
                    		this.fireEvent('change', this.appName);
                    	}
                    },
                    prefId: pref.id
                };
                
                // evaluate xtype
                var xtype = (pref.get('options') && pref.get('options').length > 0) ? 'combo' : 'textfield';
                if (xtype == 'combo' && this.adminMode) {
                    xtype = 'lockCombo';
                } else if (xtype == 'textfield' && this.adminMode) {
                    xtype = 'lockTextfield';
                    /*
                    xtype = 'lockCombo';
                    //fieldDef.hideTrigger = true;
                    fieldDef.store = [pref.get('value')];
                    */
                }
                fieldDef.xtype = xtype;
                
                if (pref.get('options') && pref.get('options').length > 0) {
                	// add additional combobox config
                	fieldDef.store = pref.get('options');
                	fieldDef.mode = 'local';
                    fieldDef.forceSelection = true;
                    fieldDef.triggerAction = 'all';
                }
                
                if (this.adminMode) {
                	// set lock (value forced => hiddenFieldData = '0')
                	fieldDef.hiddenFieldData = (pref.get('type') == 'default') ? '1' : '0';
                	fieldDef.hiddenFieldId = pref.get('name') + '_writable';
                	console.log(pref);
                } else {
                	fieldDef.disabled = (pref.get('type') == 'forced');
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
            this.html = '<div class="x-grid-empty">' + _('There are no preferences for this application.') + "</div>";
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
