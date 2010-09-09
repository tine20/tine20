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
 * @deprecated  use EditDialog instead. This class will be removed when all
 *              dialogs are moved to the EditDialog widget
 */

/*global Ext, Tine*/

Ext.ns('Tine.widgets', 'Tine.widgets.dialog');

/**
 * Generic 'Edit Record' dialog
 *
 * @namespace   Tine.widgets.dialog
 * @class       Tine.widgets.dialog.EditRecord
 * @extends     Ext.FormPanel
 */
Tine.widgets.dialog.EditRecord = Ext.extend(Ext.FormPanel, {
	/**
	 * @cfg {Array} additional toolbar items
	 */
	tbarItems: false,
    /**
     * @cfg {String} internal app name
     */
    appName: null,
    /**
     * @cfg {String} translated container item name
     */
    //containerItemName: 'record',
    /**
     * @cfg {String} translated container name
     */
    containerName: 'container',
    /**
     * @cfg {string} containerName
     * name of container (plural)
     */
    containersName: 'containers',
    /**
     * @cfg {String} name of the container property
     */
    containerProperty: 'container_id',
    /**
     * @cfg {Bool} show container selector in bottom area
     */
    showContainerSelector: false,
    /**
     * @cfg {Object} handlerScope scope, the defined handlers will be executed in 
     */
    handlerScope: null,
    /**
     * @cfg {function} handler for generic save and close action
     */
    handlerSaveAndClose: null,
    /**
     * @cfg {function} handler for generic save and close action
     */
    handlerApplyChanges: null,
    /**
     * @cfg {function} handler for generic save and close action
     */
    handlerCancel: null,
    /**
     * @cfg {String} layout of the containing window
     */
    windowLayout: 'border',
    /**
     * @property {Ext.Window|Ext.ux.PopupWindow|Ext.Air.Window}
     */
    window: null,
    
    // private
    bodyStyle: 'padding:5px',
    //layout: 'fit',
    anchor: '100% 100%',
    region: 'center',
    deferredRender: false,
    buttonAlign: 'right',
    cls: 'tw-editdialog',
	
	//private
    initComponent: function () {
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
            'apply');
        
        this.initHandlers();
        this.initActions();
        this.initButtons();
        
		Tine.widgets.dialog.EditRecord.superclass.initComponent.call(this);
	},
    
    /**
     * @private
     */
    onRender : function (ct, position) {
        Tine.widgets.dialog.EditRecord.superclass.onRender.call(this, ct, position);
        
        if (this.showContainerSelector) {
            this.recordContainerEl = this.footer.first().insertFirst({tag: 'div', style: {'position': 'relative', 'top': '4px', 'float': 'left'}});
            var ContainerForm = new Tine.widgets.container.selectionComboBox({
                id: this.appName + 'EditRecordContainerSelector',
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
    },
    
    /**
     * Init actions
     */
    initActions: function () {
    	this.action_saveAndClose = new Ext.Action({
            requiredGrant: 'editGrant',
            text: _('Ok'),
            //tooltip: 'Save changes and close this window',
            minWidth: 70,
            //handler: this.onSaveAndClose,
            handler: this.handlerSaveAndClose,
            iconCls: 'action_saveAndClose',
            scope: this.handlerScope
        });
    
        this.action_applyChanges = new Ext.Action({
            requiredGrant: 'editGrant',
            text: _('Apply'),
            //tooltip: 'Save changes',
            minWidth: 70,
            handler: this.handlerApplyChanges,
            iconCls: 'action_applyChanges',
            scope: this.handlerScope
            //disabled: true
        });
        
        this.action_cancel = new Ext.Action({
            text: _('Cancel'),
            //tooltip: 'Reject changes and close this window',
            minWidth: 70,
            handler: this.handlerCancel,
            iconCls: 'action_cancel',
            scope: this.handlerScope
        });
        
        this.action_delete = new Ext.Action({
            requiredGrant: 'deleteGrant',
            text: _('delete'),
            minWidth: 70,
            handler: this.handlerDelete,
            iconCls: 'action_delete',
            scope: this.handlerScope,
            disabled: true
        });
    },
    
    /**
     * @private
     */
    initButtons: function () {
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
     * @private
     */
    initHandlers: function () {
        this.handlerScope = this.handlerScope ? this.handlerScope : this;
        
        this.handlerSaveAndClose = this.handlerSaveAndClose ? this.handlerSaveAndClose : function (e, button) {
            this.handlerApplyChanges(e, button, true);
        };
        
        this.handlerCancel = this.handlerCancel ? this.handlerCancel : this.closeWindow;
    },
    
    /**
     * update (action updateer) top and bottom toolbars
     */
    updateToolbars: function (record, containerField) {
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
	getToolbar: function () {
		return this.getTopToolbar();
	},
    
    /**
     * @private
     */
    onCancel: function () {
        this.fireEvent('cancel');
        this.purgeListeners();
    },
    
    /**
     * @private
     */
    onSaveAndClose: function () {
        this.fireEvent('saveAndClose');
    },
    
    /**
     * @private
     */
    onApply: function () {
        this.fireEvent('apply');
    },
    
    /**
     * helper function to close window
     */
    closeWindow: function () {
        this.window.close();
    }
});

Ext.reg('tineeditrecord', Tine.widgets.dialog.EditRecord);

