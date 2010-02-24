/*
 * Tine 2.0
 * 
 * @package     Ext
 * @subpackage  ux
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
Ext.namespace('Ext.ux');

/**
 * @class Ext.ux.ConnectionStatus
 * @constructor
 * 
 */
Ext.ux.ConnectionStatus = function(config) {
    Ext.apply(this, config);
    Ext.ux.ConnectionStatus.superclass.constructor.call(this);
    
};

Ext.extend(Ext.ux.ConnectionStatus, Ext.Button, {
    /**
     * @cfg {boolean} showIcon
     */
    showIcon: true,
    
    /**
     * @property {String}
     */
    status: 'unknown',
    /**
     * @private
     */
    iconCls: 'x-ux-connectionstatus-unknown',
    
    /**
     * @property {bool} Browser supports online offline detection
     */
    isSuppoeted: null,
    
    /**
     * @private
     */
    handler: function() {
        if (this.isSuppoeted){
            this.toggleStatus();
        }
    },
    
    initComponent: function() {
        Ext.ux.ConnectionStatus.superclass.initComponent.call(this);
        
        this.onlineText = '(' + _('online') + ')';
        this.offlineText = '(' + _('offline') + ')';
        this.unknownText = '(' + _('unknown') + ')';
        
        // M$ IE has not online/offline events yet
        if (Ext.isIE6 || Ext.isIE7 || ! window.navigator || window.navigator.onLine === undefined) {
            this.setStatus('unknown');
            this.isSupported = false;
        } else {
            this.setStatus(window.navigator.onLine ? 'online' : 'offline');
            this.isSupported = true;
        }
       
        //if (Ext.isGecko3) {
            Ext.getBody().on('offline', function() {
                this.setStatus('offline', true);
            }, this);
            
            Ext.getBody().on('online', function() {
                this.setStatus('online', true);
            }, this);
        //}
    },

    /**
     * toggles online status
     */
    toggleStatus: function() {
        this.setStatus(this.status == 'online' ? 'offline' : 'online');
    },
    
    setIconClass: function(iconCls) {
        this.supr().setIconClass.call(this, this.showIcon ? iconCls : '');
    },
    
    /**
     * sets online status
     */
    setStatus: function(status) {
        switch (status) {
            case 'online':
                this.status = status;
                this.setText(this.onlineText);
                this.setIconClass('x-ux-connectionstatus-online');
                break;
            case 'offline':
                this.status = status;
                this.setText(this.offlineText);
                this.setIconClass('x-ux-connectionstatus-offline');
                break;
            case 'unknown':
                this.status = status;
                this.setText(this.unknownText);
                this.setIconClass('x-ux-connectionstatus-unknown');
                // as HTML implementation status is verry poor atm. don't bother the user with this state
                this.hide();
                break;
            default:
                console.error('no such status:"' + status + '"');
                break;
        }
    }
});