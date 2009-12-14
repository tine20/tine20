/**
 * Tine 2.0
 * 
 * @package     Tine
 * @subpackage  Widgets
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 */
Ext.namespace('Tine.widgets');

Ext.namespace('Tine.widgets.dialog');

/**
 * Generic 'Edit Record' dialog
 */
/**
 * @class Tine.widgets.dialog.EditDialog
 * @extends Ext.FormPanel
 * Base class for all 'Edit Record' dialogs
 * @constructor
 * @param {Object} config The configuration options.
 */
Tine.widgets.dialog.EditDialog = Ext.extend(Ext.FormPanel, {
    /**
     * @cfg {Tine.Tinebase.Application} app
     * instance of the app object (required)
     */
    app: null,
    /**
     * @cfg {String} mode
     * Set to 'local' if the EditDialog only operates on this.record (defaults to 'remote' which loads and saves using the recordProxy)
     */
    mode : 'remote',
    /**
     * @cfg {Array} tbarItems
     * additional toolbar items (defaults to false)
     */
    tbarItems: false,
    /**
     * @cfg {String} appName
     * internal/untranslated app name (required)
     */
    appName: null,
    /**
     * @cfg {Ext.data.Record} recordClass
     * record definition class  (required)
     */
    recordClass: null,
    /**
     * @cfg {Ext.data.DataProxy} recordProxy
     */
    recordProxy: null,
    /**
     * @cfg {Bool} showContainerSelector
     * show container selector in bottom area
     */
    showContainerSelector: false,
    /**
     * @cfg {Bool} evalGrants
     * should grants of a grant-aware records be evaluated (defaults to true)
     */
    evalGrants: true,
    /**
     * @cfg {Ext.data.Record} record
     * record in edit process.
     */
    record: null,
    /**
     * @cfg {Bool} getDefaultsAgain
     * load record defaults if new record is created and mode != local
     * 
     * TODO is it required to load the defaults again here? should load defaults only if no record is supplied when opening dialog window
     */
    getDefaultsAgain: true,
    
    /**
     * @cfg {String} saveAndCloseButtonText
     * text of save and close button
     */
    saveAndCloseButtonText: '',

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
    
    // private
    bodyStyle:'padding:5px',
    layout: 'fit',
    border: false,
    cls: 'tw-editdialog',
    anchor:'100% 100%',
    deferredRender: false,
    buttonAlign: 'right',
    bufferResize: 500,
    
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
            'update',
            /**
             * @event apply
             * Fired when user pressed apply button
             */
            'apply',
            /**
             * @event load
             * Fired when record is loaded
             */
            'load'
        );
        
        if (this.recordClass) {
            this.appName    = this.appName    ? this.appName    : this.recordClass.getMeta('appName');
            this.modelName  = this.modelName  ? this.modelName  : this.recordClass.getMeta('modelName');
        }
        
        if (! this.app) {
            this.app = Tine.Tinebase.appMgr.get(this.appName);
        }
        
        // init some translations
        if (this.app.i18n && this.recordClass !== null) {
            this.i18nRecordName = this.app.i18n.n_hidden(this.recordClass.getMeta('recordName'), this.recordClass.getMeta('recordsName'), 1);
            this.i18nRecordsName = this.app.i18n._hidden(this.recordClass.getMeta('recordsName'));
        }
    
        if (! this.recordProxy && this.recordClass) {
            this.recordProxy = new Tine.Tinebase.data.RecordProxy({
                recordClass: this.recordClass
            });
        }
        
        // init actions
        this.initActions();
        // init buttons and tbar
        this.initButtons();
        // init record 
        this.initRecord();
        // get items for this dialog
        this.items = this.getFormItems();
        
        Tine.widgets.dialog.EditDialog.superclass.initComponent.call(this);
    },
    
    /**
     * init actions
     */
    initActions: function() {
        this.action_saveAndClose = new Ext.Action({
            requiredGrant: 'editGrant',
            text: (this.saveAndCloseButtonText != '') ? this.app.i18n._(this.saveAndCloseButtonText) : _('Ok'),
            minWidth: 70,
            scope: this,
            handler: this.onSaveAndClose,
            iconCls: 'action_saveAndClose'
        });
    
        this.action_applyChanges = new Ext.Action({
            requiredGrant: 'editGrant',
            text: _('Apply'),
            minWidth: 70,
            scope: this,
            handler: this.onApplyChanges,
            iconCls: 'action_applyChanges'
        });
        
        this.action_cancel = new Ext.Action({
            text: _('Cancel'),
            minWidth: 70,
            scope: this,
            handler: this.onCancel,
            iconCls: 'action_cancel'
        });
        
        this.action_delete = new Ext.Action({
            requiredGrant: 'deleteGrant',
            text: _('delete'),
            minWidth: 70,
            scope: this,
            handler: this.onDelete,
            iconCls: 'action_delete',
            disabled: true
        });
    },
    
    /**
     * init buttons
     */
    initButtons: function() {
        var genericButtons = [
            this.action_delete
        ];
        
        //this.tbarItems = genericButtons.concat(this.tbarItems);
        
        this.buttons = [
            //this.action_applyChanges,
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
     * init record to edit
     */
    initRecord: function() {
        // note: in local mode we expect a valid record
        if (this.mode !== 'local') {
            if (this.record && this.record.id) {
                this.loadRequest = this.recordProxy.loadRecord(this.record, {
                    scope: this,
                    success: function(record) {
                        this.record = record;
                        this.onRecordLoad();
                    }
                });
            } else {
                if (! this.record || this.getDefaultsAgain) {
                    this.record = new this.recordClass(this.recordClass.getDefaultData(), 0);
                }
                this.onRecordLoad();
            }
        } else {
            if (! typeof this.record.beginEdit != 'function') {
                this.record = this.recordProxy.recordReader({responseText: this.record});
            }
            this.onRecordLoad();
        }
    },
    
    /**
     * executed after record got updated from proxy
     */
    onRecordLoad: function() {
        // interrupt process flow until dialog is rendered
        if (! this.rendered) {
            this.onRecordLoad.defer(250, this);
            return;
        }
        
        if (! this.record.id) {
            this.window.setTitle(String.format(_('Add New {0}'), this.i18nRecordName));
        } else {
            this.window.setTitle(String.format(_('Edit {0} "{1}"'), this.i18nRecordName, this.record.getTitle()));
        }
        
        if (this.fireEvent('load', this) !== false) {
            this.getForm().loadRecord(this.record);
            this.updateToolbars(this.record, this.recordClass.getMeta('containerProperty'));
            
            this.loadMask.hide();
        }
    },
    
    /**
     * executed when record gets updated from form
     */
    onRecordUpdate: function() {
        var form = this.getForm();
        
        // merge changes from form into record
        form.updateRecord(this.record);
    },
    
    /**
     * @private
     */
    onRender : function(ct, position){
        Tine.widgets.dialog.EditDialog.superclass.onRender.call(this, ct, position);
        
        // generalized keybord map for edit dlgs
        var map = new Ext.KeyMap(this.el, [
            {
                key: [10,13], // enter + return
                ctrl: true,
                fn: this.onSaveAndClose,
                scope: this
            }
        ]);

        // should be fixed in WindowFactory
        //this.setHeight(Ext.fly(this.el.dom.parentNode).getHeight());
        
        if (this.showContainerSelector) {
            this.recordContainerEl = this.footer.first().insertFirst({tag: 'div', style: {'position': 'relative', 'top': '4px', 'float': 'left'}});
            var ContainerForm = new Tine.widgets.container.selectionComboBox({
                id: this.app.appName + 'EditDialogContainerSelector',
                fieldLabel: _('Saved in'),
                width: 300,
                name: this.recordClass.getMeta('containerProperty'),
                //itemName: this.recordClass.recordName,
                containerName: this.app.i18n.n_hidden(this.recordClass.getMeta('containerName'), this.recordClass.getMeta('containersName'), 1),
                containersName: this.app.i18n._hidden(this.recordClass.getMeta('containersName')),
                appName: this.app.appName,
                requiredGrant: this.evalGrants ? 'addGrant' : false
            });
            this.getForm().add(ContainerForm);
            
            var containerSelect = new Ext.Panel({
                layout: 'form',
                border: false,
                renderTo: this.recordContainerEl,
                bodyStyle: {'background-color': '#F0F0F0'},
                items: ContainerForm
            });
        }
        
        this.loadMask = new Ext.LoadMask(ct, {msg: String.format(_('Transfering {0}...'), this.i18nRecordName)});
        if (this.recordProxy !== null && this.recordProxy.isLoading(this.loadRequest)) {
            this.loadMask.show();
        }
    },
    
    /**
     * update (action updateer) top and bottom toolbars
     */
    updateToolbars: function(record, containerField) {
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
    },
    
    /**
     * get top toolbar
     */
    getToolbar: function() {
        return this.getTopToolbar();
    },
    
    isValid: function() {
        return this.getForm().isValid();
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
     */
    onApplyChanges: function(button, event, closeWindow) {
        if(this.isValid()) {
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
                    failure: this.onRequestFailed,
                    timeout: 150000 // 3 minutes
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
    },
    
    /**
     * generic delete handler
     */
    onDelete: function(btn, e) {
        Ext.MessageBox.confirm(_('Confirm'), String.format(_('Do you really want to delete this {0}?'), this.i18nRecordName), function(_button) {
            if(btn == 'yes') {
                var deleteMask = new Ext.LoadMask(this.getEl(), {msg: String.format(_('Deleting {0}'), this.i18nRecordName)});
                deleteMask.show();
                
                this.recordProxy.deleteRecords(this.record, {
                    scope: this,
                    success: function() {
                        this.purgeListeners();
                        this.window.close();
                    },
                    failure: function () { 
                        Ext.MessageBox.alert(_('Failed'), String.format(_('Could not delete {0}.'), this.i18nRecordName));
                        Ext.MessageBox.hide();
                    }
                });
            }
        });
    },
    
    /**
     * generic request exception handler
     * 
     * @param {Object} exception
     */
    onRequestFailed: function(exception) {
        Tine.Tinebase.ExceptionHandler.handleRequestException(exception);
    }
});
