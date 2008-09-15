/*
 * Tine 2.0
 * 
 * @package     mobileClient
 * @subpackage  Tasks
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
 
Ext.namespace('Tine', 'Tine.mobileClient');


Tine.mobileClient.Connection = function(config) {
    Ext.apply(this, config);
    
    this.url = config.url;
    
    Ext.Ajax.on('beforerequest', function(connection, options) {
        options.url = this.url;
        options.params.jsonKey = this.jsonKey;
        
        options.headers = options.headers ? options.headers : {};
        options.headers['X-Tine20-Request-Type'] = 'JSON';
    }, this);
};

Tine.mobileClient.Connection.prototype = {
    /**
     * @property {Object}
     */
    account: null,
    /**
     * @property {String}
     */
    jsonKey: null,
    
    login: function(username, password, callback) {
        Ext.Ajax.request({
            scope: this,
            params: {
                method: 'Tinebase.login',
                username: username,
                password: password
            },
            callback: function(options, success, response) {
                var data = Ext.util.JSON.decode(response.responseText);
                this.jsonKey = data.jsonKey;
                this.account = data.account;
                delete data.jsonKey;
                
                if (callback) callback(data);                
            },
        });
    }
};
