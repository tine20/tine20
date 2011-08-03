/**
 * Tine 2.0
 * 
 * @package Sipgate
 * @license http://www.gnu.org/licenses/agpl.html AGPL3
 * @author Alexander Stintzing <alex@stintzing.net>
 * @copyright Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version $Id: DialNumberDialog.js 26 2011-05-03 01:42:01Z alex $
 * 
 */

Ext.namespace('Tine.Sipgate');

Tine.Sipgate.DialNumberDialog = Ext.extend(Ext.FormPanel, {

	// private
	appName : 'Sipgate',
	
	layout : 'fit',
	border : false,
	cls : 'tw-editdialog',	
	
	bodyStyle : 'padding:5px',
	labelAlign : 'top',

	anchor : '100% 100%',
	deferredRender : false,
	buttonAlign : null,
	id: 'sipgate-dialnumber-dialog',
	bufferResize : 500,

	// private
	initComponent : function() {
		this.addEvents('cancel', 'send', 'close');
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
	},

	/**
	 * init actions
	 */
	initActions : function() {
		this.action_send = new Ext.Action({
			text : this.app.i18n._('Dial Number'),
			minWidth : 70,
			scope : this,
			handler : this.onSend,
			id : 'action-add-number',
			iconCls : 'action_DialNumber'
		});
		this.action_cancel = new Ext.Action({
			text : this.app.i18n._(this.cancelButtonText) ? this.app.i18n._(this.cancelButtonText) : _('Cancel'),
			minWidth : 70,
			scope : this,
			handler : this.onCancel,
			id : 'action-cancel-dialnumber',
			iconCls : 'action_cancel'
		});
		this.action_close = new Ext.Action({
			text : this.app.i18n._(this.cancelButtonText) ? this.app.i18n._(this.cancelButtonText) : _('Close'),
			minWidth : 70,
			scope : this,
			handler : this.onCancel,
			id : 'action-close-dialnumber',
			iconCls : 'action_saveAndClose',
			// x-btn-text
			hidden : true
		});
	},

	initButtons : function() {

		this.fbar = [ '->', this.action_cancel, this.action_send, this.action_close ];

	},

	onRender : function(ct, position) {
		Tine.widgets.dialog.EditDialog.superclass.onRender.call(this, ct, position);

		// generalized keybord map for edit dlgs
		var map = new Ext.KeyMap(this.el, [ {
			key : [ 13 ],
			ctrl : false,
			fn : this.onSend,
			scope : this
		} ]);

	},

	onSend : function() {
		
	if(Tine.Sipgate.dialPhoneNumber(this.inputField.getValue(),null)) {
		this.onCancel();
	}

	},
	
	onCancel : function() {
		this.fireEvent('cancel');
		this.purgeListeners();
		this.window.close();
	},

	getFormItems : function() {
		
		this.inputField = new Ext.form.TextField({
			allowBlank: false,
			fieldLabel: _('Number to call'),
			regex: /^\+?\d{2,}$/i
			
		});
		
		
		return {
			border : false,
			id : 'sipgate-dialnumber-form',
			frame : true,
			layout : 'form',
			items : [ {
				region : 'center',
				layout : {
					align: 'stretch',
					type: 'vbox'
				}

			}, this.inputField ]
		};
	}

});

/**
 * Addressbook-Add Window (preselect)
 * 
 * @param {Object} sipId
 * @return {Ext.ux.Window}
 */
Tine.Sipgate.DialNumberDialog.openWindow = function(sipId) {
	var window = Tine.WindowFactory.getExtWindow({
		title : Tine.Tinebase.appMgr.get('Sipgate').i18n._('Dial Number'),
		width : 300,
		height : 140,
		contentPanelConstructor : 'Tine.Sipgate.DialNumberDialog',
		contentPanelConstructorConfig : null
	});
	return window;
};
