Ext.namespace('Tine', 'Tine.iPhoneClient');


Tine.iPhoneClient.Connection = function(config) {
    Ext.apply(this, config);
    
    this.url = config.url;
    
    Ext.Ajax.on('beforerequest', function(connection, options) {
        options.url = this.url;
        options.params.jsonKey = this.jsonKey;
        
        options.headers = options.headers ? options.headers : {};
        options.headers['X-Tine20-Request-Type'] = 'JSON';
    }, this);
};

Tine.iPhoneClient.Connection.prototype = {
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
