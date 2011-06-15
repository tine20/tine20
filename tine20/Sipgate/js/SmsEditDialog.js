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

	// private
	initComponent : function() {
		this.addEvents('cancel', 'send', 'close');
		if (!this.app) {
			this.app = Tine.Tinebase.appMgr.get(this.appName);
		}
		Tine.log.debug('initComponent: appName: ', this.appName);
		Tine.log.debug('initComponent: app: ', this.app);
		Tine.log.debug(this.number);

		// init actions
		this.initActions();
		// init buttons and tbar
		this.initButtons();
		// get items for this dialog
		this.items = this.getFormItems();

		Tine.Sipgate.SmsEditDialog.superclass.initComponent.call(this);
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
			id : 'action-send-sms',
			iconCls : 'SmsIconCls'
		});
		this.action_cancel = new Ext.Action({
			text : this.app.i18n._(this.cancelButtonText) ? this.app.i18n._(this.cancelButtonText) : _('Cancel'),
			minWidth : 70,
			scope : this,
			handler : this.onCancel,
			id : 'action-cancel-sms',
			iconCls : 'action_cancel'
		});
		this.action_close = new Ext.Action({
			text : this.app.i18n._(this.cancelButtonText) ? this.app.i18n._(this.cancelButtonText) : _('Close'),
			minWidth : 70,
			scope : this,
			handler : this.onCancel,
			id : 'action-close-sms',
			iconCls : 'action_saveAndClose',
			// x-btn-text
			hidden : true
		});
	},

	initButtons : function() {

		this.fbar = [ '->', this.action_cancel, this.action_send, this.action_close, ];

	},

	onRender : function(ct, position) {
		Tine.widgets.dialog.EditDialog.superclass.onRender.call(this, ct, position);

		// generalized keybord map for edit dlgs
		var map = new Ext.KeyMap(this.el, [ {
			key : [ 10, 13 ], // ctrl + return
			ctrl : true,
			fn : this.onSend,
			scope : this
		} ]);

	},

	onSend : function() {

		Tine.log.debug('SMS Text:', this.textEditor.getValue());

		if (this.textEditor.isValid()) {
			// Tine.Sipgate.sendSms(this.number,this.textEditor.getValue(),this.window);

			Ext.Ajax.request({
				url : 'index.php',

				params : {
					method : 'Sipgate.sendSms',
					_number : this.number,
					_content : this.textEditor.getValue()

				},
				success : function(_result, _request) {
					Tine.log.debug('SMS Send Result: ', _result);
					result = Ext.decode(_result.responseText);
					Tine.log.debug('SMS Send Result2: ', result);
					if (result.response.StatusCode == 200) {
						Ext.getCmp('sipgate-sms-form').update('<div class="SipgateSendSms ok">' + result.response.StatusString + '</div>');
						Ext.getCmp('action-send-sms').hide();
						Ext.getCmp('action-cancel-sms').hide();
						Ext.getCmp('action-close-sms').show();
					}
					else {

						Ext.getCmp('action-send-sms').hide();
						Ext.getCmp('sipgate-sms-form').update('<div class="SipgateSendSms error">' + result.response.StatusString + '</div>');
					}					

				},
				failure : function(result, request) {
					Tine.log.debug('SMS Send Result: ', _result);
				}
			});

		}

		// Tine.log.debug(this.textEditor.getValue());
		// //Tine.Sipgate.sendSms(this.number);
		// this.purgeListeners();
		// this.window.close();
	},

	onCancel : function() {
		this.fireEvent('cancel');
		this.purgeListeners();
		this.window.close();
	},

	getFormItems : function() {

		this.textEditor = new Ext.form.TextArea({
//			fieldLabel : '',
			width: 264,
			height: 126,
			id : 'sipgate-sms-textarea'
		});

		return {
			border : false,
			id : 'sipgate-sms-form',
			frame : true,
			layout : 'border',
			items : [ {
				region : 'center',
				layout : {
					align : 'stretch',
					type : 'vbox'
				}

			}, this.textEditor ]
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
Tine.Sipgate.SmsEditDialog.openWindow = function(number) {
	var window = Tine.WindowFactory.getExtWindow({
		title : Tine.Tinebase.appMgr.get('Sipgate').i18n._('Send'),
		width : 300,
		height : 200,
		contentPanelConstructor : 'Tine.Sipgate.SmsEditDialog',
		contentPanelConstructorConfig : number
	});
	return window;
};
