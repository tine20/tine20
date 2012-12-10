/*
 * Tine 2.0
 * 
 * @package     Sipgate
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <alex@stintzing.net>
 * @copyright   Copyright (c) 2007-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.namespace('Tine.Sipgate', 'Tine.Sipgate.Model');

// Account

Tine.Sipgate.Model.AccountArray = [
    { name: 'id' },
    { name: 'accounttype' },
    { name: 'description' },
    { name: 'is_valid' },
    
    { name: 'lines' },
    { name: 'username' },
    { name: 'password' },
    { name: 'type' },
    { name: 'mobile_number' }
];

Tine.Sipgate.Model.Account = Tine.Tinebase.data.Record.create(Tine.Tinebase.Model.modlogFields.concat(Tine.Sipgate.Model.AccountArray), {
    appName: 'Sipgate',
    modelName: 'Account',
    idProperty: 'id',
    titleProperty: 'description',

    recordName: 'Account',
    recordsName: 'Accounts',
    
    containerName: 'Account',
    containersName: 'Accounts'
});

Tine.Sipgate.accountBackend = new Tine.Tinebase.data.RecordProxy({
    appName: 'Sipgate',
    modelName: 'Account',
    recordClass: Tine.Sipgate.Model.Account,
    validateAccount: function(account, success, failure, scope)
    {
        var options = {};
        options.success = success;
        options.failure = failure;
        options.scope = scope;
        
        options.params = {};
        options.params.account = account;
        options.params.method = 'Sipgate.validateAccount';
        
        // increase timeout as this can take longer
        options.timeout = 300000;
        
        return this.doXHTTPRequest(options);
    },
    syncLines: function(accountId, success, failure, scope)
    {
        var options = {};

        options.callback = function(request, ready, response) {
            if(response.responseText) {
                var record = new this.recordClass(Ext.decode(response.responseText));
                success.call(scope, record);
            }
        }
        options.failure = failure;
        options.scope = scope;
        
        options.params = {};
        options.params.accountId = accountId;
        options.params.method = this.appName + '.syncLines';
        
        // increase timeout as this can take longer
        options.timeout = 300000;
        
        return this.doXHTTPRequest(options);
    },
    /**
     * default exception handler
     * 
     * @param {Object} exception
     */
    handleRequestException: function(exception) {
        Tine.Sipgate.handleRequestException(exception);
    }
});

Tine.Sipgate.Model.Account.getFilterModel = function() {
    var app = Tine.Tinebase.appMgr.get('Sipgate');
    
    return [ 
        { label: _('Quick search'), field: 'query', operators: ['contains'] },
        { label: _('Created By'), field: 'created_by', valueType: 'user'},
        {
            label: app.i18n._('Account-Type'),
            field: 'accounttype',
            filtertype: 'tine.widget.keyfield.filter', 
            app: app, 
            keyfieldName: 'accountType'
        },
        {
            label: app.i18n._('Status'),
            field: 'status',
            filtertype: 'tine.widget.keyfield.filter', 
            app: app, 
            keyfieldName: 'accountAccountType'
        }
    ];
};

// LINE

Tine.Sipgate.Model.LineArray = [
    { name: 'id' },
    { name: 'account_id', type: Tine.Sipgate.Model.Account },
    { name: 'user_id',   type: Tine.Tinebase.Model.Account },
    { name: 'uri_alias' },
    { name: 'sip_uri' },
    { name: 'e164_out' },
    { name: 'e164_in' },
    { name: 'tos' },
    { name: 'creation_time', type: 'date' }
];

Tine.Sipgate.Model.Line = Tine.Tinebase.data.Record.create(Tine.Sipgate.Model.LineArray, {
    appName: 'Sipgate',
    modelName: 'Line',
    idProperty: 'id',
    titleProperty: 'uri_alias',

    recordName: 'Line',
    recordsName: 'Lines'
});

Tine.Sipgate.Model.Line.getFilterModel = function() {
    var app = Tine.Tinebase.appMgr.get('Sipgate');
    
    return [ 
        { label: _('Quick search'), field: 'query', operators: ['contains'] },
        { filtertype: 'sipgate.account' },
        { label: app.i18n._('Assigned to'), field: 'user_id', valueType: 'user'}
    ];
};

Tine.Sipgate.lineBackend = new Tine.Tinebase.data.RecordProxy({
    appName: 'Sipgate',
    modelName: 'Line',
    recordClass: Tine.Sipgate.Model.Line,
    
    activeRequests: {},
    cachedResponses: {},
    
    syncConnections: function(ids, success, failure, scope) {
        var options = {
            success: success,
            failure: failure ? failure : Tine.Sipgate.handleRequestException,
            scope: scope,
            timeout: 300000,
            params: {
                lines: [],
                method: 'Sipgate.syncConnections'
           }
        };
        Ext.each(ids, function(rec) {
            options.params.lines.push(rec.data);    
        }, this);
        return this.doXHTTPRequest(options);
    },
    
    closeSession: function(sessionId, line, success, failure, scope) {
        if(this.activeRequests[sessionId]) {
            Ext.Ajax.abort(this.activeRequests[sessionId]);
            delete this.activeRequests[sessionId];
        }
        var options = {
            callback: function(request, ready, response) {
                if(response.responseText) {
                    var responseText = Ext.decode(response.responseText);
                    if(!responseText.SessionStatus) responseText.SessionStatus = 'call canceled';
                    success.call(scope, responseText);
                    delete this.activeRequests[sessionId];
                    delete this.cachedResponses[sessionId];
                }
            },
            failure: failure ? failure : Tine.Sipgate.handleRequestException,
            scope: scope ? scope : null,
            params: {
                method: 'Sipgate.closeSession',
                sessionId: sessionId,
                line: line
            }
        };
        return this.doXHTTPRequest(options);
    },
    
    getSessionStatus: function(sessionId, line, success, failure, scope) {
        if(this.activeRequests[sessionId]) {
            if(this.cachedResponses[sessionId]) {
                success.call(scope, this.cachedResponses[sessionId]);
            }
            return;
        }
        var options = {
            callback: function(request, ready, response) {
                if(response.responseText) {
                    var responseText = Ext.decode(response.responseText);
                    success.call(scope, responseText);
                    delete this.activeRequests[sessionId];
                    this.cachedResponses[sessionId] = responseText;
                }
            },
            failure: failure ? failure : Tine.Sipgate.handleRequestException,
            scope: scope ? scope : null,
            params: {
                method: 'Sipgate.getSessionStatus',
                sessionId: sessionId,
                line: line
            }
        };
        
        this.activeRequests[sessionId] = this.doXHTTPRequest(options);
    },
    
    dialNumber: function(lineId, number, contact, success, failure, scope, callingComponent) {
        var window = Tine.Sipgate.CallStateWindow.openWindow({number: number, contact: contact});
        if(callingComponent) {
            window.on('close', function(){
                callingComponent.fireEvent('callstatewindowclose');
            });
        }
        var options = {
            callback: function(request, ready, response) {
                if(response.responseText) {
                    var responseText = Ext.decode(response.responseText);
                    var config = {
                        line: new Tine.Sipgate.Model.Line(responseText.line),
                        sessionId: responseText.result.SessionID
                    };
                    if(! window.isDestroyed) {
                        var panel = window.items.items[0].items.items[0];
                        panel.start(config);
                    }
                }
            },
            failure: failure ? failure : Tine.Sipgate.handleRequestException,
            scope: scope ? scope : null,
            params: {
                lineId: lineId,
                number: number,
                method: this.appName + '.dialNumber'
            }
        };
        return this.doXHTTPRequest(options);
    },
    
    /**
     * default exception handler
     * 
     * @param {Object} exception
     */
    handleRequestException: function(exception) {
        Tine.Sipgate.handleRequestException(exception);
    }
});


// CONNECTIONS

Tine.Sipgate.Model.ConnectionArray = [
    { name: 'id' },
    { name: 'status' },
    { name: 'entry_id' },
    { name: 'tos' },
    { name: 'local_uri' },
    { name: 'remote_uri' },
    { name: 'local_number' },
    { name: 'remote_number' },
    { name: 'line_id', type: Tine.Sipgate.Model.Line},
    { name: 'timestamp' },
    { name: 'creation_time' },
    { name: 'tarif' },
    { name: 'duration' },
    { name: 'units_charged' },
    { name: 'price_unit' },
    { name: 'price_total' },
    { name: 'ticks_a' },
    { name: 'ticks_b' },
    { name: 'contact_id' },
    { name: 'contact_name' }
];

Tine.Sipgate.Model.Connection = Tine.Tinebase.data.Record.create(Tine.Sipgate.Model.ConnectionArray, {
    appName: 'Sipgate',
    modelName: 'Connection',
    idProperty: 'id',
    titleProperty: 'source_uri',
    
    // ngettext('record list', 'record lists', n);
    containerName: 'Connection',
    containersName: 'Connections',
    
    recordName: 'Connection',
    recordsName: 'Connections'
});

Tine.Sipgate.connectionBackend = new Tine.Tinebase.data.RecordProxy({
    appName: 'Sipgate',
    modelName: 'Connection',
    recordClass: Tine.Sipgate.Model.Connection
});


Tine.Sipgate.Model.Connection.getFilterModel = function() {
    var app = Tine.Tinebase.appMgr.get('Sipgate');
    return [ 
        { label: _('Quick search'), field: 'query', operators: ['contains'] },
        { filtertype: 'sipgate.line' },
        { filtertype: 'addressbook.contact' },
        {
            label: app.i18n._('Status'),
            field: 'status',
            filtertype: 'tine.widget.keyfield.filter', 
            app: app, 
            keyfieldName: 'connectionStatus'
        },
        {
            label: app.i18n._('TOS'),
            field: 'tos',
            filtertype: 'tine.widget.keyfield.filter', 
            app: app, 
            keyfieldName: 'connectionTos'
        }
    ];
};
