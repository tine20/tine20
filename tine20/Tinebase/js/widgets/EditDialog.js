/**
 * Tine 2.0
 * 
 * @package     Tine
 * @subpackage  Widgets
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
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
     * @property {Ext.data.Record} record
     * record in edit process
     */
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
    cls: 'tw-editdialog',
    anchor:'100% 100%',
    deferredRender: false,
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
             * @param {Ext.data.record} data data of the entry
             */
            'update',
            /**
             * @event apply
             * Fired when user pressed apply button
             */
            'apply'
        );
        
        if (! this.app) {
            this.app = Tine.Tinebase.appMgr.get(this.appName);
        }
        
        // init some translations
        this.i18nRecordName = this.app.i18n.n_hidden(this.recordClass.getMeta('recordName'), this.recordClass.getMeta('recordsName'), 1);
        this.i18nRecordsName = this.app.i18n.n_hidden(this.recordClass.getMeta('recordName'), this.recordClass.getMeta('recordsName'), 50);
        
        
        // init actions
        this.initActions();
        // init buttons and tbar
        this.initButtons();
        // init record and request data
        this.record = this.record ? this.record : new this.recordClass({}, 0);
        this.requestData();
        // get itmes for this dialog
        this.items = this.getFormItems();
        
        Tine.widgets.dialog.EditDialog.superclass.initComponent.call(this);
    },
    
    /**
     * init actions
     */
    initActions: function() {
        this.action_saveAndClose = new Ext.Action({
            requiredGrant: 'editGrant',
            text: _('Ok'),
            minWidth: 70,
            scope: this,
            handler: this.onSaveAndClose,
            iconCls: 'action_saveAndClose'
        });
    
        this.action_applyChanges =new Ext.Action({
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
            this.action_applyChanges,
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
     * execuded after record got updated
     */
    onRecordLoad: function() {
        if (! this.record.id) {
            this.window.setTitle(String.format(_('Add New {0}'), this.i18nRecordName));
        } else {
            this.window.setTitle(String.format(_('Edit {0} "{1}"'), this.i18nRecordName, this.record.getTitle()));
        }
        
        this.getForm().loadRecord(this.record);
        this.updateToolbars(this.record);
        
        this.loadMask.hide();
    },
    
    /**
     * @private
     */
    onRender : function(ct, position){
        Tine.widgets.dialog.EditDialog.superclass.onRender.call(this, ct, position);
        
        if (this.showContainerSelector) {
            this.recordContainerEl = this.footer.first().first().insertFirst({tag: 'div', style: {'position': 'relative', 'top': '4px', 'float': 'left'}});
            var ContainerForm = new Tine.widgets.container.selectionComboBox({
                id: this.app.appName + 'EditDialogContainerSelector',
                fieldLabel: _('Saved in'),
                width: 300,
                name: this.recordClass.getMeta('containerProperty'),
                //itemName: this.recordClass.recordName,
                containerName: this.app.i18n._hidden(this.recordClass.getMeta('containerName')),
                containersName: this.app.i18n._hidden(this.recordClass.getMeta('containersName')),
                appName: this.app.appName
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
        
        this.loadMask = new Ext.LoadMask(ct, {msg: String.format(_('Loading {0}...'), this.i18nRecordName)});
        if (this.recordProxy.isLoading(this.loadRequest)) {
            this.loadMask.show();
        }
    },
    
    /**
     * update (action updateer) top and bottom toolbars
     */
    updateToolbars: function(record, containerField) {
        var actions = [
            this.action_saveAndClose,
            this.action_applyChanges,
            this.action_delete,
            this.action_cancel
        ];
        Tine.widgets.ActionUpdater(record, actions, containerField);
        Tine.widgets.ActionUpdater(record, this.tbarItems, containerField);
    },
    
    /**
     * get top toolbar
     */
    getToolbar: function() {
        return this.getTopToolbar();
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
        var form = this.getForm();
        if(form.isValid()) {
            var saveMask = new Ext.LoadMask(this.getEl(), {msg: String.format(_('Saving {0}'), this.i18nRecordName)});
            saveMask.show();
            
            // merge changes from form into task record
            form.updateRecord(this.record);
            
            this.recordProxy.saveRecord(this.record, {
                scope: this,
                success: function(record) {
                    // override record with returned data
                    this.record = record;
                    this.fireEvent('update', this.record);
                    
                    // free 0 namespace if record got created
                    this.window.rename(this.windowNamePrefix + this.record.id);

                    if (closeWindow) {
                        this.purgeListeners();
                        this.window.close();
                    } else {
                        // update form with this new data
                        form.loadRecord(this.record);
                        this.action_delete.enable();
                        saveMask.hide();
                    }
                },
                failure: function ( result, request) { 
                    Ext.MessageBox.alert(_('Failed'), String.format(_('Could not save {0}.'), this.i18nRecordName)); 
                }
            });
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
                        this.fireEvent('update', this.record);
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
    }

});
