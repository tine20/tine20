/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.widgets.dialog');

/**
 * Base class for all Exception Handler dialogs
 * 
 * @namespace   Tine.widgets.dialog
 * @class       Tine.widgets.dialog.ExceptionHandlerDialog
 * @extends     Ext.FormPanel
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @constructor
 * @param {Object} config The configuration options.
 */

Tine.widgets.dialog.ExceptionHandlerDialog = Ext.extend(Ext.FormPanel, {
    
    // private
    layout : 'fit',
    border : false,
    cls : 'tw-editdialog',
    labelAlign : 'top',
    anchor : '100% 100%',
    deferredRender : false,
    buttonAlign : null,
    bufferResize : 500,
    
    fields: null,
    
    // private, auto set by exception
    /**
     * the calling application
     * 
     * @type Tine.Tinebase.Application
     */
    app: null,
    message: null,
    
    /**
     * the type of this exception handler dialog. May be error, warning, info, question
     * @type String
     */
    type: 'error',
    
    /**
     * the exception thrown by the server
     * 
     * @type {Object}
     */
    exception: null,
    
    /**
     * the callback function on OK
     * 
     * @type {Function}
     */
    callbackOnOk: null,
    
    /**
     * the callback scope on OK
     * @type {Object}
     */
    callbackOnOkScope: null,
    
    /**
     * the callback function on Cancel
     * 
     * @type {Function}
     */
    callbackOnCancel: null,
    
    /**
     * the callback scope on Cancel
     * @type {Object}
     */
    callbackOnCancelScope: null,
    
    /**
     * the callback function of Cancel and Ok, if not overwritten by callbackOnCancel or callbackOnOk
     * 
     * @type {Function}
     */
    callback: null,
    
    /**
     * the callback scope of Cancel and Ok, if not overwritten by callbackOnCancel or callbackOnOk
     * 
     * @type {Object}
     */
    callbackScope: null,
    
    messageHeight: 40,
    
    initComponent: function() {
        this.app = Tine.Tinebase.appMgr.get(this.exception.appName);
        this.message = this.app.i18n._hidden(this.exception.message);
        
        this.callbackOnOk = this.callbackOnOk ? this.callbackOnOk : (this.callback ? this.callback : this.onClose);
        this.callbackOnCancel = this.callbackOnCancel ? this.callbackOnCancel : (this.callback ? this.callback : this.onClose);
        this.callbackOnCancelScope = this.callbackOnCancelScope ? this.callbackOnCancelScope : (this.callbackScope ? this.callbackScope : this);
        this.callbackOnOkScope = this.callbackOnOkScope ? this.callbackOnOkScope : (this.callbackScope ? this.callbackScope : this);
        
        this.initActions();
        this.initButtons();
        this.initFormItems();
        
        Tine.widgets.dialog.ExceptionHandlerDialog.superclass.initComponent.call(this);
    },
    
    /**
     * create the buttons
     */
    initButtons : function() {
        this.fbar = [ '->', this.action_cancel, this.action_update ];
    },
    
    initFormItems: function() {
        this.items = {
            border: false,
            frame: true,
            layout: 'border',
            cls: 'x-window-dlg',
            items: [{
                region: 'north',
                xtype: 'displayfield',
                style: {
                    minHeight: '40px',
                    paddingLeft: '40px'
                },
                height: this.messageHeight,
                'name': 'message',
                value: this.message,
                hideLabel: true,
                cls: 'ext-mb-' + this.type
            }, {
                region: 'center',
                xtype: 'columnform',
                labelAlign: 'top',
                formDefaults: {
                    xtype:'textfield',
                    anchor: '100%',
                    labelSeparator: '',
                    columnWidth: 1,
                    allowBlank: false
                },
                items: this.fields
            }]
        };
    },
    
    /**
     * is called on render, creates keymap
     * @param {} ct
     * @param {} position
     */
    onRender : function(ct, position) {
        Tine.widgets.dialog.ExceptionHandlerDialog.superclass.onRender.call(this, ct, position);

        // generalized keybord map for edit dlgs
        new Ext.KeyMap(this.getEl(), [ {
            key : [ 10, 13 ], // ctrl + return
            ctrl : true,
            fn : this.callbackOnOk,
            scope : this
        }]);
    },
    
    onClose: function() {
        this.purgeListeners();
        this.window.close();
    },
    
    onOk: function() {
        var data = this.getForm().getFieldValues();
        this.callbackOnOk.call(this.callbackOnOkScope, data);
        this.onClose();
    },
    
    /**
     * initializes the actions
     */
    initActions: function() {
        this.action_cancel = new Ext.Action({
            text : i18n._('Cancel'),
            minWidth : 70,
            handler : this.callbackOnCancel,
            scope: this.callbackOnCancelScope,
            iconCls : 'action_cancel'
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
Tine.widgets.dialog.ExceptionHandlerDialog.openWindow = function(config) {
    
    var app = Tine.Tinebase.appMgr.get(config.exception.appName);
    
    if (config.hasOwnProperty('exception')) {
        if (config.exception.hasOwnProperty('title') && config.exception.title) {
            var title = app.i18n._hidden(config.exception.title);
        } else if(config.exception.hasOwnProperty('number') && config.exception.number) {
            var title = String.format(i18n._('{1} - Exception {0}'), config.exception.code, app.getTitle());
        } else {
            var title = String.format(i18n._('{0} - Unknown Exception'), app.getTitle());
        }
    }
    
    var window = Tine.WindowFactory.getWindow({
        title: title,
        modal: true,
        width: config.hasOwnProperty('width') ? config.width: 290,
        height: config.hasOwnProperty('height') ? config.height : 95,
        contentPanelConstructor: 'Tine.widgets.dialog.ExceptionHandlerDialog',
        contentPanelConstructorConfig: config
    });
    
    return window;
};