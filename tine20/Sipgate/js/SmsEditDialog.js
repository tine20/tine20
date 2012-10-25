/**
 * Tine 2.0
 * 
 * @package Sipgate
 * @license http://www.gnu.org/licenses/agpl.html AGPL3
 * @author Alexander Stintzing <alex@stintzing.net>
 * @copyright Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version $Id: SmsEditDialog.js 26 2011-05-03 01:42:01Z alex $
 * 
 */

Ext.namespace('Tine.Sipgate');

Tine.Sipgate.SmsEditDialog = Ext.extend(Ext.FormPanel, {

    // private
    appName : 'Sipgate',
    bodyStyle : 'padding:5px',
    layout : 'fit',
    border : false,
    cls : 'tw-editdialog',
    anchor : '100% 100%',
    deferredRender : false,
    buttonAlign : null,
    bufferResize : 500,

    /**
     * the contact to send number to if any
     * @type Addressbook.Model.Contact
     */
    contact: null,
    
    /**
     * the number to send to if any
     * @type {String}
     */
    number: null,

    initComponent : function() {
        this.addEvents('cancel', 'send', 'close');
        if (!this.app) {
            this.app = Tine.Tinebase.appMgr.get(this.appName);
        }
        Tine.log.debug('initComponent: appName: ', this.appName);
        Tine.log.debug('initComponent: app: ', this.app);
        Tine.log.debug(this.contact, this.number);

        // init actions
        this.initActions();
        // init buttons and tbar
        this.initButtons();
        // get items for this dialog
        this.items = this.getFormItems();
        
        Tine.Sipgate.SmsEditDialog.superclass.initComponent.call(this);
        
        if (this.contact) {
            this.onRecordLoad();
        }
    },
    
    /**
     * init actions
     */
    initActions : function() {
        this.action_send = new Ext.Action({
            text : this.app.i18n._('Send'),
            minWidth : 70,
            scope : this,
            handler : this.onSend,
            iconCls : 'SmsIconCls'
        });
        this.action_cancel = new Ext.Action({
            text : this.app.i18n._(this.cancelButtonText) ? this.app.i18n._(this.cancelButtonText) : _('Cancel'),
            minWidth : 70,
            scope : this,
            handler : this.onCancel,
            iconCls : 'action_cancel'
        });
        this.action_close = new Ext.Action({
            text : this.app.i18n._(this.cancelButtonText) ? this.app.i18n._(this.cancelButtonText) : _('Close'),
            minWidth : 70,
            scope : this,
            handler : this.onCancel,
            iconCls : 'action_saveAndClose',
            // x-btn-text
            hidden : true
        });
    },

    /**
     * initializes the buttons
     */
    initButtons : function() {
        this.fbar = [ '->', this.action_cancel, this.action_send, this.action_close ];
    },

    /**
     * see parent
     */
    onRender : function(ct, position) {
        Tine.widgets.dialog.EditDialog.superclass.onRender.call(this, ct, position);

        // generalized keybord map for edit dlgs
        var map = new Ext.KeyMap(this.el, [ {
            key : [ 10, 13 ], // ctrl + return
            ctrl : true,
            fn : this.onSend,
            scope : this
        }]);
        
        this.loadMask = new Ext.LoadMask(ct, {msg: this.app.i18n._('Loading contact...')});
        this.sendMask = new Ext.LoadMask(ct, {msg: this.app.i18n._('Sending message...')});
    },
    
    /**
     * is called on sending the message
     */
    onSend : function() {
        if (this.getForm().isValid()) {

            this.loadMask.show();

            Ext.Ajax.request({
                url : 'index.php',
                scope: this,
                params : {
                    method : 'Sipgate.sendMessage',
                    values : this.getForm().getValues(),
                    lineId: Tine.Sipgate.registry.get('preferences').get('phoneId')
                },
                success : function(_result, _request) {
                    this.sendMask.hide();
                    var result = Ext.decode(_result.responseText);
                    if(result.success) {

                        this.action_send.hide();
                        this.action_cancel.hide();
                        this.action_close.show();

                        Ext.each(['message', 'recipient_number', 'contact', 'own_number'], function(p) {
                            this.getForm().findField(p).disable();
                        }, this);

                        this.window.setTitle('The message was successfully sent to "' + this.contactPicker.selectedRecord.get('n_fn') + '"');
                    }

                },
                failure : function(result, request) {
                    this.sendMask.hide();
                    Tine.Sipgate.handleRequestException(result);
                }
            });
        }
    },

    /**
     * returns the value pair number -> label for the combo box
     * 
     * @param {Tine.Tinebase.data.Record} record
     * @param {String} property
     * @param {String} label
     * 
     * @return {Array}
     */
    getRecipientNumber: function(record, property, label) {
        label = record.get(property) + ' (' + Tine.Tinebase.appMgr.get('Addressbook').i18n._(label) + ')';
        return [record.get(property), label];
    },
    
    /**
     * update combo boxes if another contact is choosen
     * @param {Object} combo
     * @param {Tine.Tinebase.data.Record} record
     * @param {Int} index
     * @param {String} number
     */
    updateRecipientCombo: function(combo, record, index, number) {
        var foreignNumbers = [];
        
        if(record.get('tel_car')) {
            foreignNumbers.push(this.getRecipientNumber(record, 'tel_car', 'Car Phone'));
        }
        if(record.get('tel_cell')) {
            foreignNumbers.push(this.getRecipientNumber(record, 'tel_cell', 'Mobile'));
        }
        if(record.get('tel_cell_private')) {
            foreignNumbers.push(this.getRecipientNumber(record, 'tel_cell_private', 'Mobile (private)'));
        }

        this.foreignNumberChooser.getStore().removeAll();
        this.foreignNumberChooser.getStore().loadData(foreignNumbers);
        
        if(number) {
            this.foreignNumberChooser.setValue(number);
        } else if (foreignNumbers.length > 0) {
            this.foreignNumberChooser.setValue(foreignNumbers[0][0]);
        }
    },
    
    /**
     * if a contact is given, use this and the corresponding number
     */
    onRecordLoad: function() {
        if(!this.rendered) {
            this.onRecordLoad.defer(100, this);
            return;
        }
        this.loadMask.show();
        
        this.contactPicker.suspendEvents();
        this.contactPicker.selectedRecord = this.contact;
        this.contactPicker.setValue(this.contact);
        this.contactPicker.getStore().resumeEvents();
        this.updateRecipientCombo(null, this.contact, null, this.number);
        
        this.loadMask.hide();
    },
    
    /**
     * called on cancel
     */
    onCancel: function() {
        this.fireEvent('cancel');
        this.purgeListeners();
        this.window.close();
    },

    /**
     * returns he form items
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
                    anchor: '100%',
                    columnWidth: 1,
                    allowBlank: false
                },
                items : [[
                    Tine.widgets.form.RecordPickerManager.get('Addressbook', 'Contact', { 
                        ref: '../../../../contactPicker',
                        fieldLabel: this.app.i18n._('Recipient'),
                        listeners: {
                            scope: this,
                            select: this.updateRecipientCombo
                            },
                        name: 'contact'
                        })
                        
                    ],[{
                        fieldLabel: this.app.i18n._("Recipient's phone number"),
                        xtype: 'combo',
                        store: [],
                        mode: 'local',
                        emptyText: this.app.i18n._("please choose the recipient's phone number..."),
                        ref: '../../../../foreignNumberChooser',
                        name: 'recipient_number'
                    }], [{
                        fieldLabel: this.app.i18n._('Your phone number'),
                        xtype: 'extuxclearablecombofield',
                        store: [],
                        emptyText: this.app.i18n._('please choose your phone number...'),
                        ref: '../../../../ownNumberChooser',
                        name: 'own_number',
                        allowBlank: true,
                        disabled: true
                    }], [{
                        fieldLabel: this.app.i18n._('The message'),
                        xtype: 'textarea',
                        ref: '../../../../textEditor',
                        emptyText: this.app.i18n._('enter the text...'),
                        height: 125,
                        name: 'message'
                    }]
                ]
            }]
        };
    }

});

/**
 * SMS-Create Popup
 * 
 * @param {Object}
 *            number
 * 
 * @return {Ext.ux.Window}
 */
Tine.Sipgate.SmsEditDialog.openWindow = function(config) {
    var recipient = (config && config.hasOwnProperty('contact')) ? config.contact.get('n_fn') : null;
    var t = Tine.Tinebase.appMgr.get('Sipgate').i18n;
    var window = Tine.WindowFactory.getExtWindow({
        title : recipient ? String.format(t._('Send message to "{0}"'), recipient) : t._('Send message'),
        width : 390,
        height : 350,
        contentPanelConstructor : 'Tine.Sipgate.SmsEditDialog',
        contentPanelConstructorConfig : config
    });
    return window;
};
