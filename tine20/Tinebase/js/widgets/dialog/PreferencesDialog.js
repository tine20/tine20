/*
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  widgets
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 * @todo        add filter toolbar
 * @todo        use proxy store?
 */

Ext.ns('Tine.widgets', 'Tine.widgets.dialog');

/**
 * 'Edit Preferences' dialog
 *
 * @namespace   Tine.widgets.dialog
 * @class       Tine.widgets.dialog.Preferences
 * @extends     Ext.FormPanel
 * @constructor
 * @param       {Object} config The configuration options.
 */
Tine.widgets.dialog.Preferences = Ext.extend(Ext.FormPanel, {
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
    
    /**
     * @cfg String  initialCardName to select after render
     */
    initialCardName: null,
    
    // private
    layout: 'fit',
    cls: 'tw-editdialog',
    anchor:'100% 100%',
    buttonAlign: 'right',
    border: false,
    
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

        this.initActions();
        this.initButtons();
        this.items = this.getItems();
        this.currentAccountId = Tine.Tinebase.registry.get('currentAccount').accountId;
    
        Tine.widgets.dialog.Preferences.superclass.initComponent.call(this);
    },
    
    /**
     * init actions
     * 
     * @todo only allow admin mode if user has admin right
     */
    initActions: function() {
        this.action_saveAndClose = new Ext.Action({
            text: i18n._('Ok'),
            minWidth: 70,
            scope: this,
            handler: this.onSaveAndClose,
            iconCls: 'action_saveAndClose'
        });
    
        this.action_cancel = new Ext.Action({
            text: i18n._('Cancel'),
            minWidth: 70,
            scope: this,
            handler: this.onCancel,
            iconCls: 'action_cancel'
        });

        this.action_switchAdminMode = new Ext.Button(new Ext.Action({
            text: i18n._('Admin Mode'),
            minWidth: 100,
            scope: this,
            handler: () => {
                this.adminMode = !this.adminMode;
                this.showPrefsForApp();
            },
            iconCls: 'action_adminMode',
            enableToggle: true,
            hidden: !Tine.Tinebase.appMgr.isEnabled('Admin'),
        }));
    },
    
    /**
     * init buttons
     * use preference settings for order of save and close buttons
     */
    initButtons: function () {
        this.buttons = [];
        
        this.buttons.push(this.action_cancel, this.action_saveAndClose);
        
        this.selectedUserAcoountId = '0';
        
        const allUserContact = new Tine.Addressbook.Model.Contact({
            account_id: '0',
            n_fileas: '0 - ' + i18n._('All Users'),
            jpegphoto: 'images/icon-set/icon_group_full.svg',
        });
        this.allUserContact = Tine.Tinebase.data.Record.setFromJson(allUserContact.data, Tine.Addressbook.Model.Contact);
        
        this.userAccountPicker = Tine.widgets.form.RecordPickerManager.get('Addressbook', 'Contact', {
            userOnly: true,
            useAccountRecord: true,
            fieldLabel: i18n._('User'),
            hidden: !(this.adminMode && Tine.Tinebase.common.hasRight('manage_accounts', 'Admin')),
            name: 'user_id',
            allowBlank: false,
            resizable: true,
            value:  this.allUserContact.data,
            listeners: {
                scope: this,
                select: this.onSearchUserPreference,
            }
        });
        
        this.userAccountPicker.store.on('load', function(store) {
                store.insert(0, this.allUserContact);
        } , this);
        
        this.tbar = new Ext.Toolbar({
            items: [ 
                this.action_switchAdminMode,
                { xtype: 'tbspacer', width: 10 },
                this.userAccountPicker
            ]
        });
    },
    
    onSearchUserPreference: async function(combo, record) {
        this.selectedUserAcoountId = record.get?.('account_id');
        this.showPrefsForApp();
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
            title: i18n._('Applications'),
            region: 'west',
            width: 200,
            border: false,
            frame: false,
            initialNodeId: this.initialCardName ?? 'Tinebase',
        })
        return [{
            xtype: 'panel',
            autoScroll: true,
            border: false,
            frame: false,
            layout: 'border',
            items: [
                this.treePanel,
                this.prefsCardPanel
            ]
        }];
    },
    
    /**
     * @private
     */
    onRender: function (ct, position) {
        Tine.widgets.dialog.Preferences.superclass.onRender.call(this, ct, position);
        
        // recalculate height, as autoHeight fails for Ext.Window ;-(
        this.setHeight(Ext.fly(this.el.dom.parentNode).getHeight());
        
        this.window.setTitle(this.i18n._('Edit Preferences'));
        this.loadMask = new Ext.LoadMask(ct, {msg: i18n._('Loading ...')});
    },
    
    /**
     * @private
     */
    onCancel: function () {
        this.fireEvent('cancel');
        this.purgeListeners();
        this.window.close();
    },

    /**
     * @private
     * 
     * TODO check if this is working correctly
     */
    onDestroy: function(){
        // delete panels
        for (var panelName in this.adminPrefPanels) {
            if (this.adminPrefPanels.hasOwnProperty(panelName)) {
                if (this.adminPrefPanels[panelName] !== null) {
                    this.adminPrefPanels[panelName].destroy();
                    this.adminPrefPanels[panelName] = null;
                }
            }
        }
        for (panelName in this.prefPanels) {
            if (this.prefPanels.hasOwnProperty(panelName)) {
                if (this.prefPanels[panelName] !== null) {
                    this.prefPanels[panelName].destroy();
                    this.prefPanels[panelName] = null;
                }
            }
        }
        this.prefsCardPanel.destroy();
        this.prefsCardPanel = null;
        
        Tine.widgets.dialog.Preferences.superclass.onDestroy.apply(this, arguments);
    },
    
    /**
     * @private
     */
    onSaveAndClose: function(){
        this.onApplyChanges(true);
        this.fireEvent('saveAndClose');
    },
    
    /**
     * apply changes handler
     */
    onApplyChanges: async function (closeWindow) {
        if (!this.isValid()) {
            Ext.MessageBox.alert(i18n._('Errors'), i18n._('You need to correct the red marked fields before config could be saved'));
            return;
        }
    
        this.loadMask.show();
        
        // get values from card panels
        const [data, clientneedsreload] = this.getValuesFromPanels();
        try {
            const result = this.adminMode
                ? await Tine.Admin.savePreferences(data, this.selectedUserAcoountId)
                : await Tine.Tinebase.savePreferences(data, this.currentAccountId);
            
            this.loadMask.hide();
            // update registry
            if (this.selectedUserAcoountId === '0' || this.selectedUserAcoountId === this.currentAccountId) {
                this.updateRegistry(result.results);
            }
        
            if (closeWindow) {
                this.purgeListeners();
                this.window.close();
            }

            if (clientneedsreload) {
                await Ext.ux.PopupWindowMgr.getMainWindow().Tine.Tinebase.common.reload();
            }
        } catch (e) {
            Ext.MessageBox.alert(i18n._('Errors'), i18n._('Saving of preferences failed.'));
        }
        
    },
    
    /**
     * check all panels if they are valid
     * 
     * @return {Boolean}
     */
    isValid: function() {
        let panel = {};
        const panelsToSave = this.getPreferencePanel();

        for (let panelName in panelsToSave) {
            panel = panelsToSave[panelName];
            if (panel && typeof panel.isValid === 'function' && ! panel.isValid()) {
                return false;
            }
        }
        
        return true;
    },
    
    getPreferencePanel: function() {
        return this.adminMode && this.selectedUserAcoountId === '0' ? this.adminPrefPanels : this.prefPanels;
    },
    
    /**
     * get values from card panels
     * 
     * @return {Object} with form data
     */
    getValuesFromPanels: function() {
        let panel;
        let data = {};
        let clientneedsreload = false;
        const panelsToSave = this.getPreferencePanel();
        
        for (let panelName in panelsToSave) {
            if (panelsToSave.hasOwnProperty(panelName)) {
                panel = panelsToSave[panelName];
                if (panel !== null) {
                    data[panel.appName] = {};
                    for (let j=0; j < panel.items.length; j++) {
                        const item = panel.items.items[j];
                        if (item && item.name) {
                            if (this.adminMode && this.selectedUserAcoountId === '0' && panel.appName !== 'Tinebase.UserProfile') {
                                // filter personal_only (disabled) items
                                if (! item.disabled) {
                                    data[panel.appName][item.prefId] = {value: item.getValue(), name: item.name};
                                    if (Ext.getCmp(item.name + '_writable')) {
                                        data[panel.appName][item.prefId].type = (Ext.getCmp(item.name + '_writable').getValue() === '1') ? 'default' : 'forced';
                                        data[panel.appName][item.prefId].locked = Ext.getCmp(item.name + '_writable').getValue() === '0' ? 1 : 0;
                                    } else {
                                        console.error(item);
                                    }
                                }
                            } else {
                                data[panel.appName][item.name] = {value: item.getValue()};
                            }
    
                            if(!this.adminMode && !clientneedsreload && (item.startValue !== item.getValue()) && item.startValue && _.get(item, 'pref.data.uiconfig.clientneedsreload')) {
                                clientneedsreload = true;
                            }
                        }
                    }
                }
            }
        }
        
        return [data, clientneedsreload];
    },
    
    /**
     * update registry after saving of prefs
     * 
     * @param {Object} data
     */
    updateRegistry: function(data) {
        let appPrefs;
        for (let application in data) {
            if (data.hasOwnProperty(application)) {
                appPrefs = data[application];
                const registryValues = Tine[application].registry.get('preferences');
                let changed = false;
                for (var i = 0; i < appPrefs.length; i++) {
                    if (registryValues.get(appPrefs[i].name) !== appPrefs[i].value) {
                        registryValues.replace(appPrefs[i].name, appPrefs[i].value);
                        changed = true;
                    }
                }
            
                if (changed) {
                    Tine[application].registry.replace('preferences', registryValues);
                }
            }
        }
    },
    
    /**
     * onUpdateAdminMode
     * 
     * enable/disable apps according to admin right for applications
     * @private
     *
     */
    onUpdateAdminModeForApp: function(appName) {
        const hasAppAdminRight = appName === 'Tinebase.UserProfile' 
            ? Tine.Tinebase.common.hasRight('manage_accounts', 'Admin') 
            : Tine.Tinebase.common.hasRight('admin', appName);
        
        this.action_switchAdminMode.setDisabled(!hasAppAdminRight);
        
        if (!hasAppAdminRight) {
            this.adminMode = false;
            if (this.action_switchAdminMode.pressed) {
                this.action_switchAdminMode.toggle();
            }
        }

        this.userAccountPicker.setVisible(this.adminMode && hasAppAdminRight);
        
        if (this.adminMode && this.selectedUserAcoountId === '0') {
            this.prefsCardPanel.addClass('prefpanel_adminMode');
        } else {
            this.prefsCardPanel.removeClass('prefpanel_adminMode');
        }
        
        this.treePanel.checkGrants(this.adminMode, this.selectedUserAcoountId);
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
        const accountId = this.adminMode ? this.selectedUserAcoountId : this.currentAccountId;
        
        // set filter to get only default/forced values if in admin mode
        const filter = this.adminMode && accountId === '0' ? [{
            field: 'account',
            operator: 'equals',
            value: {accountId: 0, accountType: 'anyone'}
        }] : [{
            field: 'account',
            operator: 'equals',
            value: {accountId: accountId, accountType: 'user'}
        }];
        
        const store = new Ext.data.JsonStore({
            fields: Tine.Tinebase.Model.Preference,
            baseParams: {
                method: 'Tinebase.searchPreferencesForApplication',
                applicationName: appName,
                filter: filter
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
        
        store.load();
    },

    /**
     * called after a new set of preference Records has been loaded
     *
     * @param  {Ext.data.Store} this.store
     * @param store
     * @param records
     * @param options
     */
    onStoreLoad: function(store, records, options) {
        const appName = store.baseParams.applicationName;
        store.removeAll();
        store.add(records);
        const card = new Tine.widgets.dialog.PreferencesPanel({
            prefStore: store,
            appName: appName,
            adminMode: this.adminMode && this.selectedUserAcoountId === '0',
        });
    
        card.on('click', function(appName) {
            // mark card as changed in tree
            const node = this.treePanel.getNodeById(appName);
            node.setText(node.text + '*');
            this.showPrefsForApp(node.id);
        }, this);
        
        // add to panel registry
        if (this.adminMode && this.selectedUserAcoountId === '0') {
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
     */
    showPrefsForApp: function() {
        const selectedNode = this.treePanel.getSelectionModel().getSelectedNode();
        if (!selectedNode?.id) {
            return;
        }
        
        const node = this.treePanel.getNodeById(selectedNode.id);
        if (!node.isSelected()) {
            node.select();
        }

        const appName = selectedNode.id;
        const accountId = this.adminMode ? this.selectedUserAcoountId : this.currentAccountId;
        
        this.onUpdateAdminModeForApp(appName);
        // TODO: invent panel hooking approach here
        const isPanelExist = !!this.prefPanels[appName];
        
        if (appName === 'Tinebase.UserProfile') {
            if (this.adminMode && accountId === '0') {
                return;
            }
            if (!isPanelExist) {
                this.prefPanels[appName] = new Tine.Tinebase.UserProfilePanel({
                    appName: appName,
                    accountId: accountId,
                });
            } else {
                this.prefPanels[appName].updateUserProfile(accountId);
            }
        } else {
            // check stores/panels
            this.initPrefStore(appName);
        }
    
        const panel = this.getPreferencePanel();
        
        if (panel[appName]) {
            this.activateCard(panel[appName], isPanelExist);
        }
    }
});

/**
 * Timetracker Edit Popup
 */
Tine.widgets.dialog.Preferences.openWindow = function (config) {
    var window = Tine.WindowFactory.getWindow({
        width: 800,
        height: 470,
        name: 'Preferences',
        contentPanelConstructor: 'Tine.widgets.dialog.Preferences',
        contentPanelConstructorConfig: config
    });
    return window;
};
