/*
 * Tine 2.0
 * 
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */
Ext.ns('Tine.Felamimail.Model');

/**
 * @namespace Tine.Felamimail.Model
 * @class Tine.Felamimail.Model.Message
 * @extends Tine.Tinebase.data.Record
 * 
 * Message Record Definition
 */ 
Tine.Felamimail.Model.Message = Tine.Tinebase.data.Record.create([
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
      { name: 'body',     defaultValue: undefined },
      { name: 'headers' },
      { name: 'content_type' },
      { name: 'attachments' },
      { name: 'original_id' },
      { name: 'folder_id' },
      { name: 'note' }
    ], {
    appName: 'Felamimail',
    modelName: 'Message',
    idProperty: 'id',
    titleProperty: 'subject',
    // ngettext('Message', 'Messages', n);
    recordName: 'Message',
    recordsName: 'Messages',
    containerProperty: 'folder_id',
    // ngettext('Folder', 'Folders', n);
    containerName: 'Folder',
    containersName: 'Folders',
    
    /**
     * check if message has given flag
     * 
     * @param  {String} flag
     * @return {Boolean}
     */
    hasFlag: function(flag) {
        var flags = this.get('flags') || [];
        return flags.indexOf(flag) >= 0;
    },
    
    /**
     * adds given flag to message
     * 
     * @param  {String} flag
     * @return {Boolean} false if flag was already set before, else true
     */
    addFlag: function(flag) {
        if (! this.hasFlag(flag)) {
            var flags = Ext.unique(this.get('flags'));
            flags.push(flag);
            
            this.set('flags', flags);
            return true;
        }
        
        return false;
    },
    
    bodyIsFetched: function() {
        return this.get('body') !== undefined;
    },
    
    /**
     * clears given flag from message
     * 
     * @param {String} flag
     * @return {Boolean} false if flag was not set before, else true
     */
    clearFlag: function(flag) {
        if (this.hasFlag(flag)) {
            var flags = Ext.unique(this.get('flags'));
            flags.remove(flag);
            
            this.set('flags', flags);
            return true;
        }
        
        return false;
    }
});

/**
 * get default message data
 * 
 * @return {Object}
 */
Tine.Felamimail.Model.Message.getDefaultData = function() {
    var autoAttachNote = Tine.Felamimail.registry.get('preferences').get('autoAttachNote');
    return {
        note: autoAttachNote,
        content_type: 'text/html'
    };
};

/**
 * @namespace Tine.Felamimail
 * @class Tine.Felamimail.messageBackend
 * @extends Tine.Tinebase.data.RecordProxy
 * 
 * Message Backend
 */ 
Tine.Felamimail.messageBackend = new Tine.Tinebase.data.RecordProxy({
    appName: 'Felamimail',
    modelName: 'Message',
    recordClass: Tine.Felamimail.Model.Message,
    
    /**
     * move messsages to folder
     *
     * @param  array $filterData filter data
     * @param  string $targetFolderId
     * @return  {Number} Ext.Ajax transaction id
     */
    moveMessages: function(filter, targetFolderId, options) {
        options = options || {};
        options.params = options.params || {};
        
        var p = options.params;
        
        p.method = this.appName + '.moveMessages';
        p.filterData = filter;
        p.targetFolderId = targetFolderId;
        
        options.beforeSuccess = function(response) {
            return [Tine.Felamimail.folderBackend.recordReader(response)];
        };
        
        return this.doXHTTPRequest(options);
    },
    
    /**
     * fetches body into given message
     * 
     * @param {Message} message
     */
    fetchBody: function(message, callback) {
        this.loadRecord(message, {
            timeout: 120000, // 2 minutes
            callback: function(options, success, response) {
                var msgData = Ext.decode(response.responseText);
                Ext.copyTo(message.data, msgData, 'body, flags, headers, attachments, to, cc, bcc, received');
                if(Ext.isFunction(callback)){
                    callback(message);
                } else{
                    Ext.callback(callback[success ? 'success' : 'failure'], callback.scope, [message]);
                    Ext.callback(callback.callback, callback.scope, [message]);
                }
            }
        });
    },

    
    /**
     * add given flags to given messages
     *
     * @param  {String/Array} ids
     * @param  {String/Array} flags
     */
    addFlags: function(ids, flags, options)
    {
        options = options || {};
        options.params = options.params || {};
        
        var p = options.params;
        
        p.method = this.appName + '.addFlags';
        p.ids = ids;
        p.flags = flags;
        
        return this.doXHTTPRequest(options);
    },
    
    /**
     * clear given flags from given messages
     *
     * @param  {String/Array} ids
     * @param  {String/Array} flags
     */
    clearFlags: function(ids, flags, options)
    {
        options = options || {};
        options.params = options.params || {};
        
        var p = options.params;
        
        p.method = this.appName + '.clearFlags';
        p.ids = ids;
        p.flags = flags;
        
        return this.doXHTTPRequest(options);
    }
});


/**
 * @namespace Tine.Felamimail.Model
 * @class Tine.Felamimail.Model.Account
 * @extends Tine.Tinebase.data.Record
 * 
 * Account Record Definition
 */ 
Tine.Felamimail.Model.Account = Tine.Tinebase.data.Record.create(Tine.Tinebase.Model.genericFields.concat([
    { name: 'id' },
    { name: 'user_id' },
    { name: 'name' },
    { name: 'type' },
    { name: 'user' },
    { name: 'host' },
    { name: 'email' },
    { name: 'password' },
    { name: 'from' },
    { name: 'organization' },
    { name: 'port' },
    { name: 'ssl' },
    { name: 'imap_status' }, // client only {success|failure}
    { name: 'sent_folder' },
    { name: 'trash_folder' },
    { name: 'intelligent_folders' },
    { name: 'has_children_support', type: 'bool' },
    { name: 'delimiter' },
    { name: 'display_format' },
    { name: 'ns_personal' },
    { name: 'signature' },
    { name: 'smtp_port' },
    { name: 'smtp_hostname' },
    { name: 'smtp_auth' },
    { name: 'smtp_ssl' },
    { name: 'smtp_user' },
    { name: 'smtp_password' }
]), {
    appName: 'Felamimail',
    modelName: 'Account',
    idProperty: 'id',
    titleProperty: 'name',
    // ngettext('Account', 'Accounts', n);
    recordName: 'Account',
    recordsName: 'Accounts',
    containerProperty: 'container_id',
    // ngettext('record list', 'record lists', n);
    containerName: 'Account list',
    containersName: 'Account lists',
    
    /**
     * @type Object
     */
    lastIMAPException: null,
    
    /**
     * get the last IMAP exception
     * 
     * @return {Object}
     */
    getLastIMAPException: function() {
        return this.lastIMAPException;
    },
    
    /**
     * returns sendfolder id
     * -> needed as trash is saved as globname :(
     */
    getSendFolderId: function() {
        var app = Tine.Tinebase.appMgr.get('Felamimail'),
            sendName = this.get('sent_folder'),
            accountId = this.id,
            send = sendName ? app.getFolderStore().queryBy(function(record) {
                return record.get('account_id') === accountId && record.get('globalname') === sendName;
            }, this).first() : null;
            
        return send ? send.id : null;
    },
    
    /**
     * returns trashfolder id
     * -> needed as trash is saved as globname :(
     */
    getTrashFolderId: function() {
        var app = Tine.Tinebase.appMgr.get('Felamimail'),
            trashName = this.get('trash_folder'),
            accountId = this.id,
            trash = trashName ? app.getFolderStore().queryBy(function(record) {
                return record.get('account_id') === accountId && record.get('globalname') === trashName;
            }, this).first() : null;
            
        return trash ? trash.id : null;
    },
    
    /**
     * set or clear IMAP exception and update imap_state
     * 
     * @param {Object} exception
     */
    setLastIMAPException: function(exception) {
        this.lastIMAPException = exception;
        this.set('imap_status', exception ? 'failure' : 'success');
        this.commit();
    }
});

/**
 * get default data for account
 * 
 * @return {Object}
 */
Tine.Felamimail.Model.Account.getDefaultData = function() { 
    var defaults = (Tine.Felamimail.registry.get('defaults')) 
        ? Tine.Felamimail.registry.get('defaults')
        : {};
    
    return {
        host: (defaults.host) ? defaults.host : '',
        port: (defaults.port) ? defaults.port : 143,
        smtp_hostname: (defaults.smtp && defaults.smtp.hostname) ? defaults.smtp.hostname : '',
        smtp_port: (defaults.smtp && defaults.smtp.port) ? defaults.smtp.port : 25,
        signature: 'Sent with love from the new tine 2.0 email client ...<br/>'
            + 'Please visit <a href="http://tine20.org">http://tine20.org</a>',
        sent_folder: (defaults.sent_folder) ? defaults.sent_folder : 'Sent',
        trash_folder: (defaults.trash_folder) ? defaults.trash_folder : 'Trash',
        smtp_ssl: (defaults.smtp && defaults.smtp.ssl) ? defaults.smtp.ssl : 'none'
    };
};

/**
 * @namespace Tine.Felamimail
 * @class Tine.Felamimail.accountBackend
 * @extends Tine.Tinebase.data.RecordProxy
 * 
 * Account Backend
 */ 
Tine.Felamimail.accountBackend = new Tine.Tinebase.data.RecordProxy({
    appName: 'Felamimail',
    modelName: 'Account',
    recordClass: Tine.Felamimail.Model.Account
});

/**
 * @namespace Tine.Felamimail.Model
 * @class Tine.Felamimail.Model.Record
 * @extends Ext.data.Record
 * 
 * Folder Record Definition
 */ 
Tine.Felamimail.Model.Folder = Tine.Tinebase.data.Record.create([
      { name: 'id' },
      { name: 'localname' },
      { name: 'globalname' },
      { name: 'path' }, // /accountid/folderid/...
      { name: 'parent' },
      { name: 'parent_path' }, // /accountid/folderid/...
      { name: 'account_id' },
      { name: 'has_children',       type: 'bool' },
      { name: 'system_folder',      type: 'bool' },
      { name: 'imap_status' },
      { name: 'imap_timestamp',     type: 'date', dateFormat: Date.patterns.ISO8601Long },
      { name: 'imap_uidnext',       type: 'int' },
      { name: 'imap_uidvalidity',   type: 'int' },
      { name: 'imap_totalcount',    type: 'int' },
      { name: 'cache_status' },
      { name: 'cache_uidnext',      type: 'int' },
      { name: 'cache_recentcount',  type: 'int' },
      { name: 'cache_totalcount',   type: 'int' },
      { name: 'cache_unreadcount',  type: 'int' },
      { name: 'cache_timestamp',    type: 'date', dateFormat: Date.patterns.ISO8601Long  },
      { name: 'cache_job_actions_estimate',     type: 'int' },
      { name: 'cache_job_actions_done',         type: 'int' }
], {
    // translations for system folder:
    // _('INBOX') _('Drafts') _('Sent') _('Templates') _('Junk') _('Trash')

    appName: 'Felamimail',
    modelName: 'Folder',
    idProperty: 'id',
    titleProperty: 'localname',
    // ngettext('Folder', 'Folders', n);
    recordName: 'Folder',
    recordsName: 'Folders',
    // ngettext('record list', 'record lists', n);
    containerName: 'Folder list',
    containersName: 'Folder lists',
    
    /**
     * is this folder the currently selected folder
     * 
     * @return {Boolean}
     */
    isCurrentSelection: function() {
        if (Tine.Tinebase.appMgr.get(this.appName).getMainScreen().getTreePanel()) {
            // get active node
            var node = Tine.Tinebase.appMgr.get(this.appName).getMainScreen().getTreePanel().getSelectionModel().getSelectedNode();
            if (node && node.attributes.folder_id) {
                return node.id == this.id;
            }
        }
        
        return false;
    }
});

/**
 * @namespace Tine.Felamimail
 * @class Tine.Felamimail.folderBackend
 * @extends Tine.Tinebase.data.RecordProxy
 * 
 * Folder Backend
 */ 
Tine.Felamimail.folderBackend = new Tine.Tinebase.data.RecordProxy({
    appName: 'Felamimail',
    modelName: 'Folder',
    recordClass: Tine.Felamimail.Model.Folder,
    
    /**
     * update message cache of given folder for given execution time
     * 
     * @param   {String} folderId
     * @param   {Number} executionTime (seconds)
     * @return  {Number} Ext.Ajax transaction id
     */
    updateMessageCache: function(folderId, executionTime, options) {
        options = options || {};
        options.params = options.params || {};
        
        var p = options.params;
        
        p.method = this.appName + '.updateMessageCache';
        p.folderId = folderId;
        p.time = executionTime;
        
        options.beforeSuccess = function(response) {
            return [this.recordReader(response)];
        };
        
        // give 5 times more before timeout
        options.timeout = executionTime * 5000;
                
        return this.doXHTTPRequest(options);
    },
    
    /**
     * generic exception handler for this proxy
     * 
     * @todo move all 902 exception handling here!
     * @todo invent requery on 902 with cred. dialog
     * 
     * @param {Tine.Exception} exception
     */
    handleRequestException: function(exception) {
        Tine.log.warn('request exception :');
        Tine.log.warn(exception);
        
        var app = Tine.Tinebase.appMgr.get('Felamimail');
        
        switch(exception.code) {
            case 910: // Felamimail_Exception_IMAP
            case 911: // Felamimail_Exception_IMAPServiceUnavailable
                Ext.Msg.show({
                   title:   app.i18n._('IMAP Error'),
                   msg:     exception.message ? exception.message : app.i18n._('No connection to IMAP server.'),
                   icon:    Ext.MessageBox.ERROR,
                   buttons: Ext.Msg.OK
                });
                break;
                
            case 912: // Felamimail_Exception_IMAPInvalidCredentials
                var accountId   = Ext.decode(exception.request).params.accountId,
                    account     = accountId ? Tine.Felamimail.loadAccountStore().getById(accountId): null,
                    imapStatus  = account ? account.get('imap_status') : null;
                    
                if (account) {
                    Tine.Felamimail.credentialsDialog = Tine.widgets.dialog.CredentialsDialog.openWindow({
                        title: String.format(app.i18n._('IMAP Credentials for {0}'), account.get('name')),
                        appName: 'Felamimail',
                        credentialsId: accountId,
                        i18nRecordName: app.i18n._('Credentials'),
                        recordClass: Tine.Tinebase.Model.Credentials,
                        // WTF? Why don't we have a username here?
//                        record: new Tine.Tinebase.Model.Credentials({
//                            id: account.id,
//                            username: account.get('user')
//                        }),
                        listeners: {
                            scope: this,
                            'update': function(data) {
                                app.checkMailsDelayedTask.delay(0);
                            }
                        }
                    });
                } else {
                    exception.code = 910;
                    return this.handleRequestException(exception);
                }
                break;
            case 913: // Felamimail_Exception_IMAPFolderNotFound
                Ext.Msg.show({
                   title:   app.i18n._('IMAP Error'),
                   msg:     app.i18n._('One of your folders was deleted from an other client, please reload you browser'),
                   icon:    Ext.MessageBox.ERROR,
                   buttons: Ext.Msg.OK
                });
                break;
            case 920: // Felamimail_Exception_SMTP
                Ext.Msg.show({
                   title:   app.i18n._('SMTP Error'),
                   msg:     exception.message ? exception.message : app.i18n._('No connection to SMTP server.'),
                   icon:    Ext.MessageBox.ERROR,
                   buttons: Ext.Msg.OK
                });
                break;
                
            default:
                Tine.Tinebase.ExceptionHandler.handleRequestException(exception);
                break;
        }
    }
});

