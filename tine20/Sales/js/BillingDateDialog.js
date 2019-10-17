/*
 * Tine 2.0
 * 
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2014 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
Ext.namespace('Tine.Sales');

/**
 * Billing Date Dialog
 * 
 * @namespace   Tine.Sales
 * @class       Tine.Sales.BillingDateDialog
 * @extends     Ext.FormPanel
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @constructor
 * @param {Object} config The configuration options.
 */

Tine.Sales.BillingDateDialog = Ext.extend(Ext.FormPanel, {
    
    // private
    layout : 'fit',
    border : false,
    cls : 'tw-editdialog',
    labelAlign : 'top',
    anchor : '100% 100%',
    deferredRender : false,
    buttonAlign : null,
    bufferResize : 500,
    
    /**
     * the calling application
     * 
     * @type Tine.Tinebase.Application
     */
    app: null,
    
    initComponent: function() {
        
        this.app = Tine.Tinebase.appMgr.get('Sales');
        
        this.initActions();
        this.initButtons();
        this.initFormItems();
        
        Tine.Sales.BillingDateDialog.superclass.initComponent.call(this);
        
        this.getForm().findField('billing_date').focus(true, 200);
    },
    
    /**
     * create the buttons
     */
    initButtons : function() {
        this.fbar = [ '->', this.action_cancel, this.action_update];
    },
    
    /**
     * populates this.items with the form items
     */
    initFormItems: function() {
        this.items = {
            title: null,
            border: false,
            frame: true,
            layout: 'border',
            cls: 'x-window-dlg',
            items: [{
                title: null,
                region: 'center',
                xtype: 'columnform',
                labelAlign: 'top',
                items: [[{
                    fieldLabel: this.panelDialog,
                    xtype: 'datefield',
                    name: 'billing_date',
                    anchor: '100%',
                    columnWidth: 1,
                    allowBlank: false,
                    value: new Date()
                }]]
            }]
        };
    },
    
    /**
     * is called on render, creates keymap
     * @param {} ct
     * @param {} position
     */
    onRender : function(ct, position) {
        Tine.Sales.BillingDateDialog.superclass.onRender.call(this, ct, position);

        // generalized keybord map for edit dlgs
        new Ext.KeyMap(ct, [ {
            key : [ 10, 13 ], // ctrl + return
            ctrl : true,
            fn : this.onOk,
            scope : this
        }]);
    },
    
    /**
     * called on clicking the OK button
     */
    onOk: function() {
        this.window.fireEvent('submit', this.getForm().findField('billing_date').getValue(), this.contractId);
        this.window.close();
    },
    
    /**
     * close window
     */
    onCancel: function() {
        this.window.close();
    },
    
    /**
     * initializes the actions
     */
    initActions: function() {
        this.action_cancel = new Ext.Action({
            text: i18n._('Cancel'),
            minWidth: 70,
            scope: this,
            handler: this.onCancel,
            iconCls: 'action_cancel'
        });
        
        this.action_update = new Ext.Action({
            text : i18n._('Ok'),
            minWidth : 70,
            scope : this,
            handler: this.onOk,
            iconCls : 'action_saveAndClose'
        });
    }
});


/**
 * @param {Object}
 * 
 * @return {Ext.ux.Window}
 */
Tine.Sales.BillingDateDialog.openWindow = function(config) {
    var window = Tine.WindowFactory.getWindow({
        title: config.winTitle,
        modal: true,
        width: 350,
        height: 150,
        contentPanelConstructor: 'Tine.Sales.BillingDateDialog',
        contentPanelConstructorConfig: config
    });
    
    window.addEvents('submit');
    
    return window;
};