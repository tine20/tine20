/*
 * Tine 2.0
 * 
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
 
/**
 * @namespace   Tine.Felamimail
 * @class       Tine.Felamimail.TreeLoader
 * @extends     Ext.tree.TreeLoader
 * 
 * <p>Felamimail Account/Folder Tree Loader</p>
 * <p>
 * TODO         remove obsolete code
 * </p>
 * 
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @version     $Id$
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Felamimail.TreeLoader
 * 
 */
Tine.Felamimail.TreeLoader = Ext.extend(Ext.tree.TreeLoader, {
	
    /**
     * 
     * @param {Ext.tree.TreeNode} node
     * @param {Function} callback Function to call after the node has been loaded. The
     * function is passed the TreeNode which was requested to be loaded.
     * @param (Object) scope The cope (this reference) in which the callback is executed.
     * defaults to the loaded TreeNode.
     */
    requestData : function(node, callback, scope){
        if(this.fireEvent("beforeload", this, node, callback) !== false) {
            var fstore = Tine.Tinebase.appMgr.get('Felamimail').getFolderStore();
            
            // we need to call doQuery fn from store to transparently do async request
            fstore.asyncQuery('parent_path', node.attributes.path, function(node, callback, scope, data) {
                if (data) {
                    node.beginUpdate();
                    data.each(function(folderRecord) {
                        var n = this.createNode(folderRecord.data);
                        if (n) {
                            node.appendChild(n);
                        }
                    }, this);
                    node.endUpdate();
                }
                this.runCallback(callback, scope || node, [node]);
            }, [node, callback, scope], this, fstore);
            
        } else {
            // if the load is cancelled, make sure we notify
            // the node that we are done
            this.runCallback(callback, scope || node, []);
        }
    },
   
    /**
     * @private
     * 
     * TODO     add qtip again (problem: it can't be changed later)?
     */
    inspectCreateNode: function(attr) {
        var account = Tine.Felamimail.loadAccountStore().getById(attr.account_id);
        
        // check for account setting
        attr.has_children = (
            account 
            && account.get('has_children_support') 
            && account.get('has_children_support') == '1'
        ) ? attr.has_children : true;
        attr.has_children = (attr.has_children == '0') ? false : attr.has_children;
        
        //var qtiptext = this.app.i18n._('Totalcount') + ': ' + attr.totalcount 
        //    + ' / ' + this.app.i18n._('Cache') + ': ' + attr.cache_status;
        Ext.apply(attr, {
    		leaf: false,
    		text: attr.localname,
            folder_id: attr.id,
    		folderNode: true,
            allowDrop: true,
            //qtip: qtiptext,
            systemFolder: (attr.system_folder == '1'),
            unreadcount: attr.cache_unreadcount,
            totalcount: attr.cache_totalcount,
            
            // if it has no children, it shouldn't have an expand icon 
            expandable: attr.has_children,
            expanded: ! attr.has_children
    	});
        
        // if it has no children, it shouldn't have an expand icon 
        if (! attr.has_children) {
            attr.children = [];
            attr.cls = 'x-tree-node-collapsed';
        }

        // show standard folders icons 
        if (account) {
            if (account.get('trash_folder') == attr.globalname) {
                if (attr.totalcount > 0) {
                    attr.cls = 'felamimail-node-trash-full';
                } else {
                    attr.cls = 'felamimail-node-trash';
                }
            }
            if (account.get('sent_folder') == attr.globalname) {
                attr.cls = 'felamimail-node-sent';
            }
        }
        if ('INBOX' == attr.globalname) {
            attr.cls = 'felamimail-node-inbox';
        }
        if ('Drafts' == attr.globalname) {
            attr.cls = 'felamimail-node-drafts';
        }
        if ('Templates' == attr.globalname) {
            attr.cls = 'felamimail-node-templates';
        }
        if ('Junk' == attr.globalname) {
            attr.cls = 'felamimail-node-junk';
        }

        // add unread class to node
        if (attr.cache_unreadcount > 0) {
            attr.text = attr.text + ' (' + attr.cache_unreadcount + ')';
            attr.cls = attr.cls + ' felamimail-node-unread'; // x-tree-node-collapsed';
        }
    }
});
