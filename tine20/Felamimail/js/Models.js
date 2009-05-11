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

/**
 * @type {Array}
 * Message model fields
 */
Tine.Felamimail.Model.MessageArray = Tine.Tinebase.Model.genericFields.concat([
    { name: 'id' },
    { name: 'subject' },
    { name: 'from' },
    { name: 'to' },
    { name: 'sent',     type: 'date', dateFormat: Date.patterns.ISO8601Long },
    { name: 'received', type: 'date', dateFormat: Date.patterns.ISO8601Long },
    { name: 'flags' },
    { name: 'size' },
    { name: 'body' }
    // tine 2.0 notes + tags
    //{ name: 'notes'},
    //{ name: 'tags' }
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
 * @type {Array}
 * Account model fields
 */
Tine.Felamimail.Model.AccountArray = Tine.Tinebase.Model.genericFields.concat([
    { name: 'id' },
    { name: 'name' },
    { name: 'user' }
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

Tine.Felamimail.Model.Message.getDefaultData = function() { 
    return {
        from: 'default'
    };
};
