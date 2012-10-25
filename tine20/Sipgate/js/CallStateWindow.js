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
    
    /**
     * the session id of the call to control
     * @type {String} sessionId
     */
    sessionId : null,
    
    /**
     * number
     * @type {String} number
     */
    number: null,
    
    /**
     * line
     * @type Tine.Sipgate.Model.Line
     */
    line: null,
     
    /**
     * contact
     * @type Tine.Addressbook.Model.Contact
     */
    contact: null,
    
    /**
     * name of the callee or number (auto)
     * @type {String}
     */
    calleeName: null,
    
    /**
     * task
     * @type 
     */
    task: null,
    
    initComponent : function() {
        
        this.addEvents('cancel');
        if (!this.app) {
            this.app = Tine.Tinebase.appMgr.get(this.appName);
        }
        this.calleeName = this.contact ? Ext.util.Format.htmlEncode(this.contact.get('n_fn')) : Ext.util.Format.htmlEncode(this.number);
        // init actions
        this.initActions();
        // init buttons and tbar
        this.initButtons();
        // init items
        this.items = this.getFormItems();
        Tine.Sipgate.CallStateWindow.superclass.initComponent.call(this);
    },
    
    start: function(config) {
        this.line = config.hasOwnProperty('line') ? config.line : null;
        this.sessionId = config.hasOwnProperty('sessionId') ? config.sessionId /*.replace(/monitor\d_/,'')*/ : null;
        Tine.log.info('observing call with sessionId:');
        Tine.log.info(this.sessionId);
        this.action_cancel.enable();
        this.startTask();
        this.myPhoneContainer.getEl().frame("ff0000", 1);
    },
    
    startTask: function() {
        this.task = Ext.TaskMgr.start({
            scope: this,
            interval : 1000,
            run : function() {
                this.getState();
            }
        });
    },
    
    stopTask: function() {
        if (this.task) {
            Ext.TaskMgr.stop(this.task);
        }
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
        this.stopTask();
        Tine.Sipgate.lineBackend.closeSession(this.sessionId, this.line, this.updateState, null, this);
        this.purgeListeners();
        this.window.close();
    },
    
    getState: function() {
        Tine.Sipgate.lineBackend.getSessionStatus(this.sessionId, this.line, this.updateState, null, this);
    },
    
    updateState: function(state) {
        Tine.log.info('updating state:')
        Tine.log.info(state);
        var callingState = 2;
        
        if(! state.hasOwnProperty('SessionStatus')) {
            switch (state.StatusCode) {
                case 512:
                    this.callInfoContainer.update(this.app.i18n._('The call can\'t be observed anymore. Lost SessionID.'));
                    this.stopTask();
                    break;
                default:
                    this.callInfoContainer.update(this.app.i18n._('The call can\'t be observed anymore due to an unknown error.'));
                    this.stopTask();
            }
            return;
        }
        
        switch (state.SessionStatus) {
            case 'first dial' :
                callingState = 0;
                this.callInfoContainer.update(String.format(this.app.i18n._('Please pick up your phone to get connected to {0}.'), this.calleeName));
                this.myPhoneContainer.getEl().frame("ff0000", 1);
                break;
            case 'second dial' :
                this.callInfoContainer.update(String.format(this.app.i18n._('Connecting to {0}.'), this.calleeName));
                this.myPhoneContainer.addClass('established');
                this.otherPhoneContainer.getEl().frame("ff0000",1);
                callingState = 1;
                break;
            case 'established' :
                this.otherPhoneContainer.addClass('established');
                this.callInfoContainer.update(String.format(this.app.i18n._('Connected to {0}.'), this.calleeName));
                break;
            case 'call 1 busy':
            case 'call 1 failed':
                this.myPhoneContainer.addClass('error');
                this.callInfoContainer.update(this.app.i18n._('call 1 busy'));
                this.stopTask();
                break;
            case 'call 2 busy':
            case 'call 2 failed':
                this.otherPhoneContainer.addClass('error');
                this.myPhoneContainer.removeClass('established');
                this.callInfoContainer.update(String.format(this.app.i18n._('Connecting to {0} failed. {0} is busy or has rejected the call.'), this.calleeName));
                this.stopTask();
                break;
            case 'hungup' :
                switch (callingState) {
                    case 1 :
                        this.callInfoContainer.update(this.app.i18n._('hungup before other called'));
                        break;
                    default :
                        this.callInfoContainer.update(this.app.i18n._('hungup'));
                }
                this.myPhoneContainer.removeClass('established');
                this.otherPhoneContainer.removeClass('established');
                this.stopTask();
                break;
            case 'call canceled':
                this.stopTask();
                break;
            default :
                this.callInfoContainer.update(state.SessionStatus);
                this.stopTask();
        }
    },
    
    getFormItems: function() {
        return {
            border: false,
            frame: true,
            layout: 'border',
            items: [{
                layout: 'border',
                region : 'center',
                xtype: 'container',
                ref: '../updateContainer',
                items: [
                    {
                        region: 'west',
                        xtype: 'container',
                        boxMaxHeight: 64,
                        width: 64,
                        ref:'../../myPhoneContainer',
                        cls: 'SipgateCallStatePhone',
                        margins: '15'
                    }, {
                        margins: {top:20, right:10, bottom:0, left:10},
                        region: 'center',
                        xtype: 'displayfield',
                        value: String.format(this.app.i18n._('Initializing call to {0}'), this.calleeName),
                        ref: '../../callInfoContainer'
                    }, {
                        margins: '15',
                        xtype: 'container',
                        width: 64,
                        boxMaxHeight: 64,
                        region: 'east',
                        ref: '../../otherPhoneContainer',
                        cls: 'SipgateCallStatePhone'
                    }
                ]
            }]
        };
    }
});

/**
 * @param {Object} info
 * @return {Ext.ux.Window}
 */
Tine.Sipgate.CallStateWindow.openWindow = function(config) {
    var window = Tine.WindowFactory.getExtWindow({
        title : Tine.Tinebase.appMgr.get('Sipgate').i18n._('Connecting to: ') + ' ' + config.number,
        width : 360,
        height : 160,
        contentPanelConstructor : 'Tine.Sipgate.CallStateWindow',
        contentPanelConstructorConfig : config
    });
    return window;
};