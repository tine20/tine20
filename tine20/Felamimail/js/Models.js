/**
 * Tine 2.0
 * 
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
 
Ext.ns('Tine.Felamimail', 'Tine.Felamimail.Model');

/**************************** message model *******************************/

/**
 * @type {Array}
 * Message model fields
 */
Tine.Felamimail.Model.MessageArray = Tine.Tinebase.Model.genericFields.concat([
    { name: 'id' },
    { name: 'subject' },
    { name: 'from' },
    { name: 'to' },
    { name: 'cc' },
    { name: 'bcc' },
    { name: 'sent',     type: 'date', dateFormat: Date.patterns.ISO8601Long },
    { name: 'received', type: 'date', dateFormat: Date.patterns.ISO8601Long },
    { name: 'flags' },
    { name: 'size' },
    { name: 'body' },
    { name: 'headers' },
    { name: 'hasAttachment' },
    { name: 'attachments' }
]);

/**
 * @type {Tine.Tinebase.Message}
 * record definition
 */
Tine.Felamimail.Model.Message = Tine.Tinebase.data.Record.create(Tine.Felamimail.Model.MessageArray, {
    appName: 'Felamimail',
    modelName: 'Message',
    idProperty: 'id',
    titleProperty: 'title',
    // ngettext('Message', 'Messages', n);
    recordName: 'Message',
    recordsName: 'Messages',
    containerProperty: 'container_id',
    // ngettext('record list', 'record lists', n);
    containerName: 'record list',
    containersName: 'record lists',
    getTitle: function() {
        return this.get('number') ? (this.get('number') + ' ' + this.get('title')) : false;
    }
});

/**
 * get default message data (i.e. account id)
 * 
 * @return {Object}
 */
Tine.Felamimail.Model.Message.getDefaultData = function() {
    var defaultFrom = Tine.Felamimail.registry.get('preferences').get('defaultEmailAccount');
    return {
        from: defaultFrom
    };
};

/**************************** account model *******************************/
/**
 * @type {Array}
 * Account model fields
 */
Tine.Felamimail.Model.AccountArray = Tine.Tinebase.Model.genericFields.concat([
    { name: 'id' },
    { name: 'user_id' },
    { name: 'name' },
    { name: 'user' },
    { name: 'host' },
    { name: 'email' },
    { name: 'password' },
    { name: 'from' },
    { name: 'port' },
    { name: 'secure_connection' },
    { name: 'signature' },
    { name: 'smtp_port' },
    { name: 'smtp_hostname' },
    { name: 'smtp_auth' }
]);

/**
 * @type {Tine.Tinebase.Account}
 * record definition
 */
Tine.Felamimail.Model.Account = Tine.Tinebase.data.Record.create(Tine.Felamimail.Model.AccountArray, {
    appName: 'Felamimail',
    modelName: 'Account',
    idProperty: 'id',
    titleProperty: 'name',
    // ngettext('Account', 'Accounts', n);
    recordName: 'Account',
    recordsName: 'Accounts',
    containerProperty: 'container_id',
    // ngettext('record list', 'record lists', n);
    containerName: 'record list',
    containersName: 'record lists' /*,
    getTitle: function() {
        return this.get('number') ? (this.get('number') + ' ' + this.get('title')) : false;
    } */
});

/**
 * get default data for account
 * 
 * @return {Object}
 */
Tine.Felamimail.Model.Account.getDefaultData = function() { 
    var defaults = (Tine.Felamimail.registry.get('defaults')) 
        ? Tine.Felamimail.registry.get('defaults')
        : {
            host: '',
            port: 143,
            smtp: {
                hostname: '',
                port: 25,
                auth: 'tls'
            }
        };
    
    return {
        host: defaults.host,
        port: defaults.port,
        smtp_hostname: defaults.smtp.hostname,
        smtp_auth: defaults.smtp.auth,
        smtp_port: defaults.smtp.port
    };
};

/**************************** attachment model *******************************/

/**
 * @type {Tine.Tinebase.Message}
 * record definition
 */
Tine.Felamimail.Model.Attachment = Tine.Tinebase.data.Record.create([
   { name: 'name' },
   { name: 'size' },
   { name: 'path' },
   { name: 'type' }
]);
