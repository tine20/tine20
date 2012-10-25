/*
 * Tine 2.0
 * 
 * @package     Sipgate
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <alex@stintzing.net>
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.ns('Tine.Sipgate');

/**
 * @namespace   Tine.widgets.dialog
 * @class       Tine.widgets.dialog.AddToRecordPanel
 * @extends     Ext.FormPanel
 * @author      Alexander Stintzing <alex@stintzing.net>
 */

Tine.Sipgate.DialNumberDialog = Ext.extend(Ext.FormPanel, {

    /**
     * the assigned contact if any
     * @type Tine.Addressbook.Model.Contact
     */
    contact: null,
    /**
     * the number to call
     * @type {String}
     */
    number: null,
    
    // private
    appName : 'Sipgate',
    app: null,
    layout : 'fit',
    border : false,
    cls : 'tw-editdialog',
    labelAlign : 'top',
    anchor : '100% 100%',
    deferredRender : false,
    buttonAlign : null,
    bufferResize : 500,
    
    /**
     * initializes the component
     */
    initComponent: function() {
         
        if (!this.app) {
            this.app = Tine.Tinebase.appMgr.get(this.appName);
        }

        Tine.log.debug('initComponent: appName: ', this.appName);
        Tine.log.debug('initComponent: app: ', this.app);

        // init actions
        this.initActions();
        // init buttons and tbar
        this.initButtons();
        
        // get items for this dialog
        this.items = this.getFormItems();

        Tine.Sipgate.DialNumberDialog.superclass.initComponent.call(this);
        
        var phoneId = Tine.Sipgate.registry.get('preferences').get('phoneId');
        
        this.on('callstatewindowclose', this.onCancel, this);
        
        if(phoneId) {
            this.linePicker.getStore().baseParams.filter = [{field: 'id', operator: 'equals', value: phoneId}];
            this.linePicker.getStore().suspendEvents();
            this.linePicker.getStore().load({callback: this.lineAutoSet, scope:this});
        }
    },
    
    lineAutoSet: function() {
        var value = this.linePicker.getStore().getById(Tine.Sipgate.registry.get('preferences').get('phoneId'))
        this.linePicker.setValue(value);
        this.linePicker.getStore().resumeEvents();
    },
    
    /**
     * initializes the actions
     */
    initActions: function() {
        this.action_cancel = new Ext.Action({
            text : _('Cancel'),
            minWidth : 70,
            scope : this,
            handler : this.onCancel,
            iconCls : 'action_cancel'
        });
        
        this.action_update = new Ext.Action({
            text : _('Ok'),
            minWidth : 70,
            scope : this,
            handler : this.onUpdate,
            iconCls : 'action_saveAndClose'
        });
    },

    /**
     * create the buttons
     */
    initButtons : function() {
        this.fbar = [ '->', this.action_cancel, this.action_update ];
    },
    
    /**
     * is called on render, creates keymap
     * @param {} ct
     * @param {} position
     */
    onRender : function(ct, position) {
        Tine.Sipgate.DialNumberDialog.superclass.onRender.call(this, ct, position);

        // generalized keybord map for edit dlgs
        new Ext.KeyMap(this.el, [ {
            key : [ 10, 13 ], // ctrl + return
            ctrl : true,
            fn : this.onUpdate,
            scope : this
        }]);
    },
    
    /**
     * is called on cancel
     */
    onCancel: function() {
        this.fireEvent('cancel');
        this.purgeListeners();
        this.window.close();
    },

    /**
     * returns the form items
     * @return {Object}
     */
    getFormItems: function() {
        return {
            border: false,
            frame: true,
            layout: 'border',
            items: [{
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
                items: [[
                    Tine.widgets.form.RecordPickerManager.get('Sipgate', 'Line', {
                        name: 'line',
                        fieldLabel: this.app.i18n._('Select Line'),
                        onlyUsable: true,
                        ref: '../../../../linePicker'
                    })],[
                    {
                        fieldLabel:this.app.i18n. _('Number to call'),
                        name: 'number',
                        value: this.number ? this.number : Tine.Sipgate.registry.get('preferences').get('internationalPrefix'),
                        regex: /^\+?\d{5,}$/i,
                        allowBlank: false,
                        regexText: this.app.i18n._('Please use a valid telephone number!')
                    }
                ]] 
            }]
        }
    },
    /**
     * checks if line is selected
     * @return {Boolean}
     */
    isValid: function() {
        if ((this.getForm().findField('line').getValue() != '') && (this.getForm().findField('number').isValid())) {
            return true;
        }
        return false;
    },
    
    /**
     * is called when ok-button is pressed and edit dialog should be opened
     */
    onUpdate: function() {
        Tine.Sipgate.lineBackend.dialNumber(
            this.getForm().findField('line').getValue(),
            this.getForm().findField('number').getValue(),
            this.contact,
            null,
            null,
            null,
            this
            );
    }
});

/**
 * Select Line Window
 * @return {Ext.ux.Window}
 */
Tine.Sipgate.DialNumberDialog.openWindow = function(config) {
    var number = (config && config.hasOwnProperty('number')) ? config.number : '';
    return Tine.WindowFactory.getExtWindow({
        title : String.format(Tine.Tinebase.appMgr.get('Sipgate').i18n._('Dial Number {0}'), number),
        width : 250,
        height : 170,
        contentPanelConstructor : 'Tine.Sipgate.DialNumberDialog',
        contentPanelConstructorConfig : config
    });
};