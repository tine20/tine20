/*
 * Tine 2.0
 * 
 * @package     Tine
 * @subpackage  Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */
 
Ext.ns('Tine.Tinebase.sync');

/**
 * @namespace   Tine.Tinebase.sync
 * @class       Tine.Tinebase.sync.Ping
 * @extends     Ext.util.Observable
 */
Tine.Tinebase.sync.Ping = function(config) {
    Ext.apply(this, config);
    Tine.Tinebase.sync.Ping.superclass.constructor.call(this);
    
    this.sendPing();
};

Ext.extend(Tine.Tinebase.sync.Ping, Ext.util.Observable, {
    sendPing: function() {
        this.connectionId = Ext.Ajax.request({
            timeout: 45000,
            success: this.onPingSuccess,
            fail: this.onPingFail,
            scope: this,
            params: {
                method: 'Tinebase.ping'
            }
        });
    },
    
    onPingSuccess: function() {
        this.sendPing();
    },
    
    onPingFail: function() {
        Ext.Msg.alert('Ping', 'Failed');
    }
});