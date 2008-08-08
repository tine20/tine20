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
Tine.widgets.dialog.EditRecord = Ext.extend(Ext.FormPanel, {
	/**
	 * @cfg {array} additional toolbar items
	 */
	tbarItems: false,
    /**
     * @cfg {reference} handlerScope scope to be aplied to the handlers
     */
    handlerScope: null,
    bodyStyle:'padding:5px',
    //layout: 'fit',
    anchor:'100% 100%',
    region: 'center',
    deferredRender: false,
    buttonAlign: 'right',
	
	//private
    initComponent: function(){
        this.addEvents(
            /**
             * @event cancle
             * Fired when user pressed cancle button
             */
            'cancle',
            /**
             * @event saveAndClose
             * Fired when user pressed OK button
             */
            'saveAndClose',
            /**
             * @event apply
             * Fired when user pressed apply button
             */
            'apply'
        );
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
    
        this.action_applyChanges =new Ext.Action({
            requiredGrant: 'editGrant',
            text: _('Apply'),
            //tooltip: 'Save changes',
            minWidth: 70,
            handler: this.handlerApplyChanges,
            iconCls: 'action_applyChanges',
            scope: this.handlerScope
            //disabled: true
        });
        
        this.action_delete = new Ext.Action({
            requiredGrant: 'deleteGrant',
            text: 'delete',
            minWidth: 70,
            handler: this.handlerDelete,
            iconCls: 'action_delete',
            scope: this.handlerScope,
            disabled: true
        });
        this.action_cancel = new Ext.Action({
            text: _('Cancel'),
            //tooltip: 'Reject changes and close this window',
            minWidth: 70,
            handler: this.handlerCancle ? this.handlerCancle : function(){window.close();},
            iconCls: 'action_cancel',
            scope: this.handlerScope
        });
        
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
                id: 'applicationToolbar',
                items: this.tbarItems
            });
        }
		
		Tine.widgets.dialog.EditRecord.superclass.initComponent.call(this);
	},
    updateToolbars: function(record, containerField) {
        var actions = [
            this.action_saveAndClose,
            this.action_applyChanges,
            this.action_delete,
            this.action_cancel
        ];
        Tine.widgets.ActionUpdater(record, actions, containerField);
        Tine.widgets.ActionUpdater(record, this.getTopToolbar(), containerField);
    },
	getToolbar: function() {
		return this.getTopToolbar();
	},
    onCancel: function(){
        
        this.fireEvent('cancle');
        //console.log('cancel');
    },
    onSaveAndClose: function(){
        this.fireEvent('saveAndClose');
        //console.log('save');
    },
    onApply: function(){
        this.fireEvent('apply');
        //console.log('apply');
    }
});

Ext.reg('tineeditrecord', Tine.widgets.dialog.EditRecord);

