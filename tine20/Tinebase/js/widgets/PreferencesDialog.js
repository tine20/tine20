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
 * @todo        load default/forced/anyone prefs into store
 * @todo        add save functionality
 * @todo        add app tree view
 * @todo        add lock to force prefs
 * @todo        show prefs for all apps
 * @todo        add user search/filter toolbar
 * 
 * @todo        finish implementation
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
        
    // private
    bodyStyle:'padding:5px',
    layout: 'fit',
    cls: 'tw-editdialog',
    anchor:'100% 100%',
    //deferredRender: false,
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
        console.log('actions');
        this.initActions();
        // init buttons and tbar
        console.log('buttons');
        this.initButtons();
        // init preferences
        console.log('prefs');
        this.initPreferences();
        // get items for this dialog
        console.log('form');
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
        var genericButtons = [
//            this.action_delete
        ];
        
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
        return [{
        	xtype: 'panel',
        	//title: this.i18n._('Preferences'),
            autoScroll: true,
            border: true,
            frame: true,
            layout: 'border',
            height: 424,
            items: [{
                region: 'west',
                xtype: 'panel',
                title: _('Applications'),
                html: 'tree panel',
                width: 200,
                frame: true
                /*
                labelAlign: 'top',
                formDefaults: {
                    xtype:'textfield',
                    anchor: '100%',
                    labelSeparator: '',
                    columnWidth: .333
                },
                items: []
                */ 
            }, new Tine.widgets.dialog.PreferencesPanel({
                region: 'center'
            })
            /*{
                region: 'center',
                xtype: 'panel',
                html: 'prefs',
                frame: true
            }*/]
            //region: 'center'
        }];
        
            //layout: 'border'
                
                /*
            xtype: 'tabpanel',
            border: false,
            plain:true,
            activeTab: 0,
            border: false,
            items: [{               
                title: this.i18n._('Preferences'),
                autoScroll: true,
                border: false,
                frame: true,
                layout: 'border',
                items: [{
                    region: 'center',
                    xtype: 'columnform',
                    labelAlign: 'top',
                    formDefaults: {
                        xtype:'textfield',
                        anchor: '100%',
                        labelSeparator: '',
                        columnWidth: .333
                    },
                    items: [] 
                }]
            } ,{
                title: this.app.i18n._('Access'),
                layout: 'fit',
                items: [this.getGrantsGrid()]
            }, new Tine.widgets.activities.ActivitiesTabPanel({
                app: this.appName,
                record_id: this.record.id,
                record_model: this.appName + '_Model_' + this.recordClass.getMeta('modelName')
            }) ]*/
    },
    
    /**
     * @private
     */
    onRender : function(ct, position){
        Tine.widgets.dialog.Preferences.superclass.onRender.call(this, ct, position);
        //this.loadMask = new Ext.LoadMask(ct, {msg: _('Loading ...')});
        //    this.loadMask.show();
    },
    
    /**
     * update (action updater) top and bottom toolbars
     */
    updateToolbars: function(record, containerField) {
    	/*
        if (! this.evalGrants) {
            return;
        }
        
        var actions = [
            this.action_saveAndClose,
            this.action_applyChanges,
            this.action_delete,
            this.action_cancel
        ];
        Tine.widgets.actionUpdater(record, actions, containerField);
        Tine.widgets.actionUpdater(record, this.tbarItems, containerField);
        */
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
    	/*
        this.onApplyChanges(button, event, true);
        this.fireEvent('saveAndClose');
        */
    },
    
    /**
     * generic apply changes handler
     */
    onApplyChanges: function(button, event, closeWindow) {
    	/*
        var form = this.getForm();
        if(form.isValid()) {
            this.loadMask.show();
            
            this.onRecordUpdate();
            
            if (this.mode !== 'local') {
                this.recordProxy.saveRecord(this.record, {
                    scope: this,
                    success: function(record) {
                        // override record with returned data
                        this.record = record;
                        
                        // update form with this new data
                        // NOTE: We update the form also when window should be closed,
                        //       cause sometimes security restrictions might prevent
                        //       closing of native windows
                        this.onRecordLoad();
                        this.fireEvent('update', Ext.util.JSON.encode(this.record.data));
                        
                        // free 0 namespace if record got created
                        this.window.rename(this.windowNamePrefix + this.record.id);
                        
                        if (closeWindow) {
                            this.purgeListeners();
                            this.window.close();
                        }
                    },
                    failure: function ( result, request) { 
                        Ext.MessageBox.alert(_('Failed'), String.format(_('Could not save {0}.'), this.i18nRecordName)); 
                    }
                });
            } else {
                this.onRecordLoad();
                this.fireEvent('update', Ext.util.JSON.encode(this.record.data));
                
                // free 0 namespace if record got created
                this.window.rename(this.windowNamePrefix + this.record.id);
                        
                if (closeWindow) {
                    this.purgeListeners();
                    this.window.close();
                }
            }
        } else {
            Ext.MessageBox.alert(_('Errors'), _('Please fix the errors noted.'));
        }
        */
    }    
});

/**
 * preferences panel
 */
Tine.widgets.dialog.PreferencesPanel = Ext.extend(Ext.Panel, {
    
    //private
    layout: 'form',
    border: true,
    frame: true,
    labelAlign: 'top',
    autoScroll: true,
    defaults: {
        anchor: '100%',
        labelSeparator: ''
    },
    
    initComponent: function() {
        this.title = _('Preferences');
        
        var prefStore = this.getPrefStore();
        if (prefStore) {
            this.items = [];
            /*
            prefStore.each(function(def) {
                var fieldDef = {
                    fieldLabel: def.get('label'),
                    name: 'customfield_' + def.get('name'),
                    xtype: def.get('type')
                };
                
                try {
                    var fieldObj = Ext.ComponentMgr.create(fieldDef);
                    this.items.push(fieldObj);
                    
                    // ugh a bit ugly
                    def.fieldObj = fieldObj;
                } catch (e) {
                    console.error('unable to create custom field "' + def.get('name') + '". Check definition!');
                    prefStore.remove(def);
                }
                
            }, this);
            
            this.formField = new Tine.widgets.customfields.CustomfieldsPanelFormField({
                prefStore: prefStore
            });
            
            this.items.push(this.formField);
            */
            
        } else {
            this.html = '<div class="x-grid-empty">' + _('There are no preferences yet') + "</div>";
        }
        
        Tine.widgets.dialog.PreferencesPanel.superclass.initComponent.call(this);
        
        // added support for defered rendering as a quick hack: it would be better to 
        // let cfpanel be a plugin of editDialog
        /*
        this.on('render', function() {
            // fill data from record into form wich is not done due to defered rendering
            this.setAllCfValues(this.quickHack.record.get('customfields'));
        }, this);
        */
    },
    
    getPrefStore: function() {
    	return null;
    	/*
        var appName = this.recordClass.getMeta('appName');
        var modelName = this.recordClass.getMeta('modelName');
        if (Tine[appName].registry.containsKey('customfields')) {
            var allCfs = Tine[appName].registry.get('customfields');
            var prefStore = new Ext.data.JsonStore({
                fields: Tine.Tinebase.Model.Customfield,
                data: allCfs
            });
            
            prefStore.filter('model', appName + '_Model_' + modelName);
            
            if (prefStore.getCount() > 0) {
                return prefStore;
            }
        }
        */
    }
    
    /*
    setAllCfValues: function(customfields) {
        // check if all cfs are already rendered
        var allRendered = false;
        this.items.each(function(item) {
            allRendered |= item.rendered;
        }, this);
        
        if (! allRendered) {
            this.setAllCfValues.defer(100, this, [customfields]);
        } else {
            this.formField.setValue(customfields);
        }
    }
    */
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
