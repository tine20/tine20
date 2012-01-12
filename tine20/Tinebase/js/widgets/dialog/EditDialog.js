/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.widgets.dialog');

/**
 * Generic 'Edit Record' dialog
 * Base class for all 'Edit Record' dialogs
 * 
 * @namespace   Tine.widgets.dialog
 * @class       Tine.widgets.dialog.EditDialog
 * @extends     Ext.FormPanel
 * @author      Cornelius Weiss <c.weiss@metaways.de>
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
     * @cfg {String} saveAndCloseButtonText
     * text of save and close button
     */
    saveAndCloseButtonText: '',
    /**
     * @cfg {String} cancelButtonText
     * text of cancel button
     */
    cancelButtonText: '',
    
    /**
     * @cfg {Boolean} copyRecord
     * copy record
     */
    copyRecord: false,
    
    /**
     * @cfg {Boolean} doDuplicateCheck
     * do duplicate check when saveing record (mode remote only)
     */
    doDuplicateCheck: true,
    
    /**
     * required grant for apply/save
     * @type String
     */
    editGrant: 'editGrant',

    /**
     * Shall the MultipleEditDialogPlugin be aplied?
     * @type Boolean
     */
    useMultiple: false,
    
    /**
     * holds items to disable on multiple edit
     * @type Array
     */
    disableOnEditMultiple: null,
    
    selectedRecords: null,
    selectionFilter: null,
        
    
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
    buttonAlign: null,
    bufferResize: 500,
    
    //private
    initComponent: function() {
        try {
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
                 * @pram  {String} this.mode
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
                'load',
                /**
                 * @event save
                 * Fired when remote record is saving
                 */
                'save'
            );
            
            if (this.recordClass) {
                this.appName    = this.appName    ? this.appName    : this.recordClass.getMeta('appName');
                this.modelName  = this.modelName  ? this.modelName  : this.recordClass.getMeta('modelName');
            }
            
            if (! this.app) {
                this.app = Tine.Tinebase.appMgr.get(this.appName);
            }
            
            Tine.log.debug('initComponent: appName: ', this.appName);
            Tine.log.debug('initComponent: modelName: ', this.modelName);
            Tine.log.debug('initComponent: app: ', this.app);
            
            this.selectedRecords = Ext.decode(this.selectedRecords);
            this.selectionFilter = Ext.decode(this.selectionFilter);
            
            // init some translations
            if (this.app.i18n && this.recordClass !== null) {
                this.i18nRecordName = this.app.i18n.n_hidden(this.recordClass.getMeta('recordName'), this.recordClass.getMeta('recordsName'), 1);
                this.i18nRecordsName = this.app.i18n._hidden(this.recordClass.getMeta('recordsName'));
            }
        
            if (! this.recordProxy && this.recordClass) {
                Tine.log.debug('no record proxy given, creating a new one...');
                this.recordProxy = new Tine.Tinebase.data.RecordProxy({
                    recordClass: this.recordClass
                });
            }
            
            // init plugins
            this.plugins = this.plugins ? this.plugins : [];
            this.plugins.push(new Tine.widgets.customfields.EditDialogPlugin({}));
            this.plugins.push(this.tokenModePlugin = new Tine.widgets.dialog.TokenModeEditDialogPlugin({}));
            
            if(this.useMultiple) this.plugins.push(new Tine.widgets.dialog.MultipleEditDialogPlugin({}));
            
            // init actions
            this.initActions();
            // init buttons and tbar
            this.initButtons();
            // init container selector
            this.initContainerSelector();
            // init record 
            this.initRecord();
            // get items for this dialog
            this.items = this.getFormItems();
            
            Tine.widgets.dialog.EditDialog.superclass.initComponent.call(this);
        } catch (e) {
            Tine.log.error('Tine.widgets.dialog.EditDialog::initComponent');
            Tine.log.error(e.stack ? e.stack : e);
        }
    },
    
    /**
     * init actions
     */
    initActions: function() {
        this.action_saveAndClose = new Ext.Action({
            requiredGrant: this.editGrant,
            text: (this.saveAndCloseButtonText != '') ? this.app.i18n._(this.saveAndCloseButtonText) : _('Ok'),
            minWidth: 70,
            ref: '../btnSaveAndClose',
            scope: this,
            handler: this.onSaveAndClose,
            iconCls: 'action_saveAndClose'
        });
    
        this.action_applyChanges = new Ext.Action({
            requiredGrant: this.editGrant,
            text: _('Apply'),
            minWidth: 70,
            scope: this,
            handler: this.onApplyChanges,
            iconCls: 'action_applyChanges'
        });
        
        this.action_cancel = new Ext.Action({
            text: (this.cancelButtonText != '') ? this.app.i18n._(this.cancelButtonText) : _('Cancel'),
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
        
        this.fbar = [
            '->',
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
     * init container selector
     */
    initContainerSelector: function() {
        if (this.showContainerSelector) {
            var ContainerForm = new Tine.widgets.container.selectionComboBox({
                id: this.app.appName + 'EditDialogContainerSelector',
                fieldLabel: _('Saved in'),
                width: 300,
                listWidth: 300,
                name: this.recordClass.getMeta('containerProperty'),
                recordClass: this.recordClass,
                containerName: this.app.i18n.n_hidden(this.recordClass.getMeta('containerName'), this.recordClass.getMeta('containersName'), 1),
                containersName: this.app.i18n._hidden(this.recordClass.getMeta('containersName')),
                appName: this.app.appName,
                requiredGrant: this.evalGrants ? 'addGrant' : false
            });
            this.on('render', function() {this.getForm().add(ContainerForm);}, this);
            
            this.fbar = [
                _('Saved in'),
                ContainerForm
            ].concat(this.fbar);
        }
        
    },
    
    /**
     * init record to edit
     */
    initRecord: function() {
        
        Tine.log.debug('init record with mode: ' + this.mode);
        if (! this.record) {
            Tine.log.debug('creating new default data record');
            this.record = new this.recordClass(this.recordClass.getDefaultData(), 0);
        }
        
        if (this.mode !== 'local') {
            if (this.record && this.record.id) {
                this.loadRemoteRecord();
            } else {
                this.onRecordLoad();
            }
        } else {
            // note: in local mode we expect a valid record
            if (typeof this.record.beginEdit != 'function') {
                this.record = this.recordProxy.recordReader({responseText: this.record});
            }
            this.onRecordLoad();
        }
    },
    
    /**
     * load record via record proxy
     */
    loadRemoteRecord: function() {
        Tine.log.info('initiating record load via proxy');
        this.loadRequest = this.recordProxy.loadRecord(this.record, {
            scope: this,
            success: function(record) {
                this.record = record;
                this.onRecordLoad();
            }
        });
    },

    /**
     * copy record
     */
    doCopyRecord: function() {
        var omitFields = this.recordClass.getMeta('copyOmitFields') || [];
        // always omit id + notes
        omitFields = omitFields.concat(['id', 'notes']);
        
        var fieldsToCopy = this.recordClass.getFieldNames().diff(omitFields),
            recordData = Ext.copyTo({}, this.record.data, fieldsToCopy);

        this.record = new this.recordClass(recordData, 0);
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
        
        try {
            Tine.log.debug('loading of the following record completed:');
            Tine.log.debug(this.record);
            
            if (this.copyRecord) {
                this.doCopyRecord();
                this.window.setTitle(String.format(_('Copy {0}'), this.i18nRecordName));
            } else {
                if (! this.record.id) {
                    this.window.setTitle(String.format(_('Add New {0}'), this.i18nRecordName));
                } else {
                    this.window.setTitle(String.format(_('Edit {0} "{1}"'), this.i18nRecordName, this.record.getTitle()));
                }
            }
            
            if (this.fireEvent('load', this) !== false) {
                this.getForm().loadRecord(this.record);
                this.getForm().clearInvalid();
                this.updateToolbars(this.record, this.recordClass.getMeta('containerProperty'));
                
                this.loadMask.hide();
            }
        } catch (e) {
            Tine.log.error('Tine.widgets.dialog.EditDialog::onRecordLoad');
            Tine.log.error(e.stack ? e.stack : e);
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
        try {
            Tine.widgets.dialog.EditDialog.superclass.onRender.call(this, ct, position);
            
            // generalized keybord map for edit dlgs
            var map = new Ext.KeyMap(this.el, [
                {
                    key: [10,13], // ctrl + return
                    ctrl: true,
                    scope: this,
                    fn: function() {
                        // focus ok btn
                        if (this.action_saveAndClose.items) {
                            this.action_saveAndClose.items[0].focus();
                        }
                        this.onSaveAndClose.defer(10, this);
                    }
                }
            ]);
    
            // should be fixed in WindowFactory
            //this.setHeight(Ext.fly(this.el.dom.parentNode).getHeight());
                
            this.loadMask = new Ext.LoadMask(ct, {msg: String.format(_('Transferring {0}...'), this.i18nRecordName)});
            if (this.mode !== 'local' && this.recordProxy !== null && this.recordProxy.isLoading(this.loadRequest)) {
                this.loadMask.show();
            }
        } catch (e) {
            Tine.log.error('Tine.widgets.dialog.EditDialog::onRender');
            Tine.log.error(e.stack ? e.stack : e);
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
    
    /**
     * is form valid?
     * 
     * @return {Boolean}
     */
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
        // we need to sync record before validating to let (sub) panels have 
        // current data of other panels
        this.onRecordUpdate();
        
        if(this.isValid()) {
            this.loadMask.show();
            
            if (this.mode !== 'local') {
                this.fireEvent('save');
                this.recordProxy.saveRecord(this.record, {
                    scope: this,
                    success: function(record) {
                        // override record with returned data
                        this.record = record;
                        
                        if (! (closeWindow && typeof this.window.cascade == 'function')) {
                            // update form with this new data
                            // NOTE: We update the form also when window should be closed,
                            //       cause sometimes security restrictions might prevent
                            //       closing of native windows
                            this.onRecordLoad();
                        }
                        this.fireEvent('update', Ext.util.JSON.encode(this.record.data), this.mode);
                        
                        // free 0 namespace if record got created
                        this.window.rename(this.windowNamePrefix + this.record.id);
                        
                        if (closeWindow) {
                            this.purgeListeners();
                            this.window.close();
                        }
                    },
                    failure: this.onRequestFailed,
                    timeout: 300000 // 5 minutes
                }, {
                    duplicateCheck: this.doDuplicateCheck
                });
            } else {
                this.onRecordLoad();
                this.fireEvent('update', Ext.util.JSON.encode(this.record.data), this.mode);
                
                // free 0 namespace if record got created
                this.window.rename(this.windowNamePrefix + this.record.id);
                        
                if (closeWindow) {
                    this.purgeListeners();
                    this.window.close();
                }
            }
        } else {
            Ext.MessageBox.alert(_('Errors'), this.getValidationErrorMessage());
        }
    },
    
    /**
     * get validation error message
     * 
     * @return {String}
     */
    getValidationErrorMessage: function() {
        return _('Please fix the errors noted.');
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
     * doublicate(s) found exception handler
     * 
     * @param {Object} exception
     */
    onDuplicateException: function(exception) {
        var resolveGridPanel = new Tine.widgets.dialog.DuplicateResolveGridPanel({
            app: this.app,
            store: new Tine.widgets.dialog.DuplicateResolveStore({
                app: this.app,
                recordClass: this.recordClass,
                recordProxy: this.recordProxy,
                data: {
                    clientRecord: this.record, //exception.clientRecord,
                    duplicates: exception.duplicates
                }
            }),
            fbar: [
                '->',
                this.action_cancel,
                this.action_saveAndClose
           ]
        });
        
        // intercept save handler
        resolveGridPanel.btnSaveAndClose.setHandler(function(btn, e) {
            var resolveStrategy = resolveGridPanel.store.resolveStrategy;
            
            // action discard -> close window
            if (resolveStrategy == 'discard') {
                return this.onCancel();
            }
            
            this.record = resolveGridPanel.store.getResolvedRecord();
            this.onRecordLoad();
            
            mainCardPanel.layout.setActiveItem(this.id);
            resolveGridPanel.doLayout();
            
            this.doDuplicateCheck = false;
            this.onSaveAndClose(btn, e);
        }, this);
        
        // place in viewport
        this.window.setTitle(String.format(_('Resolve Duplicate {0} Suspicion'), this.i18nRecordName));
        var mainCardPanel = Tine.Tinebase.viewport.tineViewportMaincardpanel;
        mainCardPanel.add(resolveGridPanel);
        mainCardPanel.layout.setActiveItem(resolveGridPanel.id);
        resolveGridPanel.doLayout();
        
        
        this.loadMask.hide();

        return;
    },
    
    /**
     * generic request exception handler
     * 
     * @param {Object} exception
     */
    onRequestFailed: function(exception) {
        // Duplicate record(s) found
        if (exception.code == 629) {
            return this.onDuplicateException.apply(this, arguments);
        }
        
        Tine.Tinebase.ExceptionHandler.handleRequestException(exception);
    },
    
    /**
     * add given item disable registry for multiple edit
     * 
     * NOTE: this function can be called from any child's scope also
     * 
     * @param {Ext.Component} item
     */
    addToDisableOnEditMultiple: function(item) {
        
        var mgrCmpTest = function(p) {return Ext.isFunction(p.addToDisableOnEditMultiple);},
            me = mgrCmpTest(this) ? this : this.findParentBy(mgrCmpTest);
            
        if (me) {
            me.disableOnEditMultiple = me.disableOnEditMultiple || [];
            if (me.disableOnEditMultiple.indexOf(item) < 0) {
                Tine.log.debug('Tine.widgets.dialog.EditDialog::addToDisableOnEditMultiple ' + item.id);
                me.disableOnEditMultiple.push(item);
            }
        }
    },
    
    getDisableOnEditMultiple: function() {
        if(!this.disableOnEditMultiple) this.disableOnEditMultiple = new Array();
        return this.disableOnEditMultiple;
       
    }
});
