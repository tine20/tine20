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
     * @cfg {String} modelName
     * name of the model/record  (required)
     */
    modelName: null,
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
     * @cfg {String} idProperty
     * property of the id of the record
     */
    idProperty: 'id',
    /**
     * @cfg {String} titleProperty
     * property of the title attibute, used in generic getTitle function  (required)
     */
    titleProperty: null,
    /**
     * @cfg {String} containerItemName
     * untranslated container item name
     */
    containerItemName: 'record',
    /**
     * @cfg {String} containerItemsName
     * untranslated container items (plural) name
     */
    containerItemsName: 'records',
    /**
     * @cfg {String} containerName
     * untranslated container name
     */
    containerName: 'container',
    /**
     * @cfg {string} containerName
     * untranslated name of container (plural)
     */
    containersName: 'containers',
    /**
     * @cfg {String} containerProperty
     * name of the container property
     */
    containerProperty: 'container_id',
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
        
        // init translations
        this.translation = new Locale.Gettext();
        this.translation.textdomain(this.appName);
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
            iconCls: 'action_saveAndClose',
        });
    
        this.action_applyChanges =new Ext.Action({
            requiredGrant: 'editGrant',
            text: _('Apply'),
            minWidth: 70,
            scope: this,
            handler: this.onApplyChanges,
            iconCls: 'action_applyChanges',
        });
        
        this.action_cancel = new Ext.Action({
            text: _('Cancel'),
            minWidth: 70,
            scope: this,
            handler: this.onCancel,
            iconCls: 'action_cancel',
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
            this.window.setTitle(String.format(this.translation.gettext('Add New {0}'), this.containerItemName));
        } else {
            this.window.setTitle(String.format(this.translation._('Edit {0} "{1}"'), this.containerItemName, this.getTitle(this.record)));
        }
        
        this.getForm().loadRecord(this.record);
        this.updateToolbars(this.record);
        
        this.loadMask.hide();
    },
    
    /**
     * get title of record
     * 
     * NOTE: has noting to do with the title of a window/panel ;-)
     * @todo move to base class of app/record handler
     * @param  {Ext.data.Record} record
     * @return {String} title
     */
    getTitle: function(record) {
        if (this.titleProperty) {
            return record.get(this.titleProperty);
        }
    },
    
    /**
     * @private
     */
    onRender : function(ct, position){
        Tine.widgets.dialog.EditDialog.superclass.onRender.call(this, ct, position);
        
        if (this.showContainerSelector) {
            this.recordContainerEl = this.footer.first().first().insertFirst({tag: 'div', style: {'position': 'relative', 'top': '4px', 'float': 'left'}});
            var ContainerForm = new Tine.widgets.container.selectionComboBox({
                id: this.appName + 'EditDialogContainerSelector',
                fieldLabel: _('Saved in'),
                width: 300,
                name: this.containerProperty,
                //itemName: this.containerItemName,
                containerName: this.containerName,
                containersName: this.containersName,
                appName: this.appName
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
        
        this.loadMask = new Ext.LoadMask(ct, {msg: String.format(this.translation._('Loading {0}...'), this.containerItemName)});
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
            var saveMask = new Ext.LoadMask(this.getEl(), {msg: String.format(this.translation._('Saving {0}'), this.containerItemName)});
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
                    Ext.MessageBox.alert(this.translation._('Failed'), String.format(this.translation._('Could not save {0}.'), this.containerItemName)); 
                }
            });
        } else {
            Ext.MessageBox.alert(this.translation._('Errors'), this.translation._('Please fix the errors noted.'));
        }
    },
    
    /**
     * generic delete handler
     */
    onDelete: function(btn, e) {
        Ext.MessageBox.confirm(this.translation._('Confirm'), String.format(this.translation._('Do you really want to delete this {0}?'), this.containerItemName), function(_button) {
            if(btn == 'yes') {
                var deleteMask = new Ext.LoadMask(this.getEl(), {msg: String.format(this.translation._('Deleting {0}'), this.containerItemName)});
                deleteMask.show();
                
                this.recordProxy.deleteRecords(this.record, {
                    scope: this,
                    success: function() {
                        this.fireEvent('update', this.record);
                        this.purgeListeners();
                        this.window.close();
                    },
                    failure: function () { 
                        Ext.MessageBox.alert(this.translation._('Failed'), String.format(this.translation.ngettext('Could not delete {0}.', 'Could not delete {0}', 1), this.containerItemName));
                        Ext.MessageBox.hide();
                    }
                });
            }
        });
    },

});
