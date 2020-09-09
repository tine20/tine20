/*
 * Tine 2.0
 * 
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
Ext.namespace('Tine.Sales');

/**
 * Copy Address Dialog
 * 
 * @namespace   Tine.Sales
 * @class       Tine.Sales.CopyAddressDialog
 * @extends     Ext.FormPanel
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @constructor
 * @param {Object} config The configuration options.
 */

Tine.Sales.CopyAddressDialog = Ext.extend(Ext.FormPanel, {
    
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
        this.initActions();
        this.initButtons();
        this.initFormItems();
        
        Tine.Sales.CopyAddressDialog.superclass.initComponent.call(this);
        
        this.getForm().findField('content').setValue(this.content);
        this.getForm().findField('content').focus(true, 200);
    },
    
    /**
     * create the buttons
     */
    initButtons : function() {
        this.fbar = [ '->', this.action_update];
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
                    fieldLabel: this.app.i18n.n_('Address', 'Addresses', 1),
                    xtype: 'textarea',
                    name: 'content',
                    anchor: '100%',
                    height: 120,
                    labelSeparator: '',
                    columnWidth: 1,
                    allowBlank: true
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
        Tine.Sales.CopyAddressDialog.superclass.onRender.call(this, ct, position);

        // generalized keybord map for edit dlgs
        new Ext.KeyMap(ct, [ {
            key : [ 10, 13 ], // ctrl + return
            ctrl : true,
            fn : this.onOk,
            scope : this
        }]);
        
        this.window.setTitle(this.app.i18n._hidden(this.winTitle));
    },
    
    /**
     * called on clicking the OK button
     */
    onOk: function() {
        this.window.close();
        this.onClose();
    },
    
    /**
     * initializes the actions
     */
    initActions: function() {
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
Tine.Sales.CopyAddressDialog.openWindow = function(config) {
    var window = Tine.WindowFactory.getWindow({
        title: config.winTitle,
        modal: true,
        width: 290,
        height: 225,
        contentPanelConstructor : 'Tine.Sales.CopyAddressDialog',
        contentPanelConstructorConfig : config
    });
    
    return window;
};