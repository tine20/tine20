/*
 * Tine 2.0
 * 
 * @package     Expressomail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */
 
/**
 * @namespace   Tine.Expressomail
 * @class       Tine.Expressomail.TreeLoaderSieve
 * @extends     Tine.widgets.tree.Loader
 * 
 * <p>Expressomail Account/Folder Tree Loader</p>
 * <p>
 * </p>
 * 
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Expressomail.TreeLoaderSieve
 * 
 */
Tine.Expressomail.TreeLoaderSieve = Ext.extend(Tine.widgets.tree.Loader, {
    
    /**
     * request data
     * 
     * @param {Ext.tree.TreeNode} node
     * @param {Function} callback Function to call after the node has been loaded. The
     * function is passed the TreeNode which was requested to be loaded.
     * @param (Object) scope The cope (this reference) in which the callback is executed.
     * defaults to the loaded TreeNode.
     */
    requestData : function(node, callback, scope){
        
        if(this.fireEvent("beforeload", this, node, callback) !== false) {
            var fstore = Tine.Tinebase.appMgr.get('Expressomail').getFolderStore(),
                folder = fstore.getById(node.attributes.folder_id),
                path = (folder) ? folder.get('path') : node.attributes.path;
            
            // we need to call doQuery fn from store to transparently do async request
            fstore.asyncQuery('parent_path', path, function(node, callback, scope, data) {
                if (data) {
                    node.beginUpdate();
                    data.each(function(folderRecord) {
                        var n = this.createNode(folderRecord.copy().data);
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
    */
    createNode: function() {
        if(!arguments[0].globalname.match(/^user\/.+$/i) 
                && !arguments[0].globalname.match(/^user$/i) 
                && !arguments[0].globalname.match(/^inbox\/Arquivo Remoto\/.+$/i) 
                && !arguments[0].globalname.match(/^inbox\/Arquivo Remoto$/i)){
            this.inspectCreateNode.apply(this, arguments);
            return Tine.widgets.tree.Loader.superclass.createNode.apply(this, arguments);
            }
        },
    
    
    
    /**
     * inspectCreateNode
     * 
     * @private
     */
    inspectCreateNode: function(attr) {
        var account = Tine.Tinebase.appMgr.get('Expressomail').getAccountStore().getById(attr.account_id);
        
        // NOTE cweiss 2010-06-15 this has to be precomputed on server side!
        attr.has_children = (account && account.get('has_children_support')) ? attr.has_children : true;
        if (attr.has_children == "0") {
            attr.has_children = false;
        }
        
        
        
        
        Ext.apply(attr, {
            leaf: !attr.has_children,
            expandable: attr.has_children,
            cls: 'x-tree-node-collapsed',
            folder_id: attr.id,
            folderNode: true,
            allowDrop: true,
            text: this.app.i18n._hidden(attr.localname)
        });
        
        // show standard folders icons 
        if (account) {
            if (account.get('trash_folder') === attr.globalname) {
                if (attr.cache_totalcount > 0) {
                    attr.cls = 'expressomail-node-trash-full';
                } else {
                    attr.cls = 'expressomail-node-trash';
                }
            }
            if (account.get('sent_folder') === attr.globalname) {
                attr.cls = 'expressomail-node-sent';
            }
            if (account.get('drafts_folder') === attr.globalname) {
                attr.cls = 'expressomail-node-drafts';
            }
            if (account.get('templates_folder') === attr.globalname) {
                attr.cls = 'expressomail-node-templates';
            }
        }
        if (attr.globalname.match(/^inbox$/i)) {
            attr.cls = 'expressomail-node-inbox';
            attr.text = this.app.i18n._hidden('INBOX');
        }
        
        if (attr.globalname.match(/^inbox\/Arquivo Remoto\/.+$/i) || attr.globalname.match(/^inbox\/Arquivo Remoto$/i) ) {
            attr.cls = 'expressomail-node-unselectable';
            attr.is_selectable = false;
            //attr.text = this.app.i18n._hidden('INBOX');
        }
        if (attr.globalname.match(/^junk$/i)) {
            attr.cls = 'expressomail-node-junk';
        }

        if (! attr.is_selectable) {
            attr.cls = 'expressomail-node-unselectable';
        }
    }
});
