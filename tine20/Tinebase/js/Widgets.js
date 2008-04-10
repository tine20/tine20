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
	//labelAlign: 'top',
    bodyStyle:'padding:5px',
    layout: 'fit',
    anchor:'100%',
    region: 'center',
    deferredRender: false,
    //layoutOnTabChange:true,
    handlerScope: null,
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
            text: 'Ok',
            minWidth: 70,
            //handler: this.onSaveAndClose,
            handler: this.handlerSaveAndClose,
            iconCls: 'action_saveAndClose',
            scope: this.handlerScope
        });
    
        this.action_applyChanges =new Ext.Action({
            text: 'Apply',
            minWidth: 70,
            handler: this.handlerApplyChanges,
            iconCls: 'action_applyChanges',
            scope: this.handlerScope
            //disabled: true
        });
        
        this.action_delete = new Ext.Action({
            text: 'delete',
            minWidth: 70,
            handler: this.handlerDelete,
            iconCls: 'action_delete',
            scope: this.handlerScope,
            disabled: true
        });
        this.action_cancel = new Ext.Action({
            text: 'Cancel',
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
	getToolbar: function() {
		return this.getTopToolbar();
	},
    onCancel: function(){
        
        this.fireEvent('cancle');
        console.log('cancel');
    },
    onSaveAndClose: function(){
        this.fireEvent('saveAndClose');
        console.log('save');
    },
    onApply: function(){
        this.fireEvent('apply');
        console.log('apply');
    }
});



Ext.namespace('Tine.widgets.Priority');
Tine.widgets.Priority.store = new Ext.data.SimpleStore({
	storeId: 'Priorities',
	id: 'key',
    fields: ['key','value', 'icon'],
    data: [
            ['0', 'low',    '' ],
            ['1', 'normal', '' ],
            ['2', 'high',   '' ],
            ['3', 'urgent', '' ]
        ]
});

Tine.widgets.Priority.Combo = Ext.extend(Ext.form.ComboBox, {
	/**
     * @cfg {bool} autoExpand Autoexpand comboBox on focus.
     */
    autoExpand: false,
    /**
     * @cfg {bool} blurOnSelect blurs combobox when item gets selected
     */
    blurOnSelect: false,
    
    displayField: 'value',
    valueField: 'key',
    mode: 'local',
    triggerAction: 'all',
    //selectOnFocus: true,
    editable: false,
    lazyInit: false,
    
    //private
    initComponent: function(){
        Tine.widgets.Priority.Combo.superclass.initComponent.call(this);
        // allways set a default
        if(!this.value) {
            this.value = 1;
        }
            
        this.store = Tine.widgets.Priority.store;
        
        if (this.autoExpand) {
            this.on('focus', function(){
                this.lazyInit = false;
                this.expand();
            });
        }
        if (this.blurOnSelect){
            this.on('select', function(){
                this.fireEvent('blur', this);
            }, this);
        }
    }
});

Tine.widgets.Priority.renderer = function(priority) {
	var s = Tine.widgets.Priority.store;
	var idx = s.find('key', priority);
	return idx !== undefined ? s.getAt(idx).data.value : priority;
};
