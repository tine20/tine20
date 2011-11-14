/**
 * Tine 2.0
 * 
 * @package Sipgate
 * @license http://www.gnu.org/licenses/agpl.html AGPL3
 * @author Alexander Stintzing <alex@stintzing.net>
 * @copyright Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version $Id: CallStateWindow.js 22 2011-05-01 21:00:08Z alex $
 * 
 */

Ext.namespace('Tine.Sipgate');

Tine.Sipgate.CallStateWindow = Ext.extend(Ext.FormPanel, {

	appName : 'Sipgate',
	bodyStyle : 'padding:5px',
	layout : 'fit',
	border : false,
	cls : 'tw-editdialog',
	anchor : '100% 100%',
	deferredRender : false,
	buttonAlign : null,
	bufferResize : 500,
	id: 'callstate-window',
	sessionId : null,
	
	initComponent : function() {
		
		this.addEvents('cancel');
		if (!this.app) {
			this.app = Tine.Tinebase.appMgr.get(this.appName);
		}
		Tine.log.debug('initComponent: appName: ', this.appName);
		Tine.log.debug('initComponent: app: ', this.app);
		Tine.log.debug('Info After: ',this.info);

		// init actions
		this.initActions();
		// init buttons and tbar
		this.initButtons();
		// init items
		this.items = this.getFormItems();
		
		Tine.Sipgate.CallStateWindow.superclass.initComponent.call(this);
	},
	
	initActions : function() {		
		this.action_cancel = new Ext.Action({
			text : this.app.i18n._(this.cancelButtonText) ? this.app.i18n._(this.cancelButtonText) : _('Cancel'),
			minWidth : 70,
			scope : this,
			handler : this.onCancel,
			iconCls : 'action_cancel',
			disabled : true,
			id : 'call-cancel-button'
		});
	},
	initButtons: function() {
		this.fbar = [ '->', this.action_cancel ];

	},
	
	onCancel : function() {
		Tine.log.debug('in onCancel csw',this.sessionId);
		if(this.sessionId != false) Tine.Sipgate.closeSession(this.sessionId);
		Tine.log.debug('in onCancel csw2');
		this.fireEvent('cancel');
		
		this.purgeListeners();
		this.window.close();
	},
	
	getFormItems: function() {
		return {
	
			border : false,
			frame : true,
			layout : 'border',	
			
			    items : [
			            {
			            	region : 'center',
			                xtype: 'container',
			                height: 64,
			                id: 'csw-update-container',
			                items: [
			                    {
			                        xtype: 'container',
			                        height: 64,
			                        width: 64,
			                        id: 'csw-my-phone',
			                        cls: 'SipgateCallStatePhone'
			                    },
			                    {
			                        xtype: 'container',
			                        height: 58,
			                        width: 77,
			                        style: '{float:left}',
			                        
			                        id: 'csw-line',
			                        cls: 'SipgateCallStateLine',
			                        items: [
			                            {
			                                xtype: 'displayfield',
			                                value: this.app.i18n._('connecting...') + ' ' + this.info.name,
			                                width: 77,
			                                height: 48,
			                                id: 'csw-call-info'
			                            }
			                        ]
			                    },
			                    {
			                        xtype: 'container',
			                        width: 64,
			                        height: 64,
			                        
			                        style: '[{float:left},{clear:both}]',
			                        id: 'csw-other-phone',
			                        cls: 'SipgateCallStatePhone'
			                    }
			                ]
			            }
			        ]
		
		};
	}

});

/**
 * @param {Object} info
 * 
 * @return {Ext.ux.Window}
 */

Tine.Sipgate.CallStateWindow.openWindow = function(info) {

	Tine.log.debug('Info: ', info);
	var _number = info.info.number;
	Tine.log.debug('Num: ', _number);
	
	var window = Tine.WindowFactory.getExtWindow({
		title : Tine.Tinebase.appMgr.get('Sipgate').i18n._('Connecting to: ') + ' ' + _number,
		width : 320,
		height : 160,
		contentPanelConstructor : 'Tine.Sipgate.CallStateWindow',
		contentPanelConstructorConfig : info
		

	});
	window.addListener('close', function() {	
		Tine.Sipgate.CallStateWindow.stopTask();
		});

	return window;
};

Tine.Sipgate.CallStateWindow.startTask = function(sessionId,contact) {

	CallUpdateWindowTask = Ext.TaskMgr.start({
		interval : 2000,
		run : function() {
			Tine.Sipgate.updateCallStateWindow(sessionId,contact);
		}
	});
};


Tine.Sipgate.CallStateWindow.stopTask = function() {
	if(CallUpdateWindowTask) {
		Ext.TaskMgr.stop(Tine.Sipgate.CallUpdateWindowTask);		
		CallUpdateWindowTask = null;
	}
};



