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
 * @class       Tine.Expressomail.TreeLoader
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
 * Create a new Tine.Expressomail.TreeLoader
 * 
 */
Tine.Expressomail.TreeLoader = Ext.extend(Tine.widgets.tree.Loader, {
    
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
            cls: Boolean(attr.sharing_with.length) ? 'x-tree-node-collapsed-overlay-share'
                                    : 'x-tree-node-collapsed',
            folder_id: attr.id,
            folderNode: true,
            allowDrop: true,
            text: this.app.i18n._hidden(attr.localname)
        });
        
        // show standard folders icons 
        if (account) {
            if (account.get('trash_folder') === attr.globalname) {
                if (attr.cache_totalcount > 1) {
                    attr.cls = Boolean(attr.sharing_with.length) ? 'expressomail-node-trash-full-overlay-share'
                                                : 'expressomail-node-trash-full';
                } else {
                    attr.cls = Boolean(attr.sharing_with.length) ? 'expressomail-node-trash-overlay-share'
                                                : 'expressomail-node-trash';
                }
            }
            if (account.get('sent_folder') === attr.globalname) {
                attr.cls = Boolean(attr.sharing_with.length) ? 'expressomail-node-sent-overlay-share'
                                            : 'expressomail-node-sent';
            }
            if (account.get('drafts_folder') === attr.globalname) {
                attr.cls = Boolean(attr.sharing_with.length) ? 'expressomail-node-drafts-overlay-share'
                                            : 'expressomail-node-drafts';
            }
            if (account.get('templates_folder') === attr.globalname) {
                attr.cls = Boolean(attr.sharing_with.length) ? 'expressomail-node-templates-overlay-share'
                                            : 'expressomail-node-templates';
            }
        }
        if (attr.globalname.match(/^inbox$/i)) {
            attr.cls = Boolean(attr.sharing_with.length) ? 'expressomail-node-inbox-overlay-share'
                                        : 'expressomail-node-inbox';
            attr.text = this.app.i18n._hidden('INBOX');
        }
        if (attr.globalname.match(/^junk$/i)) {
            attr.cls = Boolean(attr.sharing_with.length) ? 'expressomail-node-junk-overlay-share'
                                        : 'expressomail-node-junk';
        }
        if (attr.globalname.match(/^inbox\/arquivo remoto$/i) || attr.globalname.match(/^inbox\/arquivo remoto\//i)) {
            attr.cls = Boolean(attr.sharing_with.length) ? 'expressomail-node-remote-overlay-share'
                                        : 'expressomail-node-remote';
        }

        if (! attr.is_selectable) {
            attr.cls = 'expressomail-node-unselectable';
        }
    }
});

Ext.override(Ext.tree.TreeNodeUI, {
    updateExpandIcon : function() {
        if(this.rendered){
            var n = this.node,
                c1,
                c2,
                rc1,
                rc2,
                cls = n.isLast() ? "x-tree-elbow-end" : "x-tree-elbow",
                hasChild = n.hasChildNodes(),
                is_sharing = Boolean(Ext.isEmpty(n.attributes.sharing_with)
                            ? 0 : n.attributes.sharing_with.length);
            if(hasChild || n.attributes.expandable){
                if(n.expanded){
                    cls += "-minus";
                    c1 = is_sharing ? "x-tree-node-collapsed-overlay-share" : "x-tree-node-collapsed";
                    c2 = is_sharing ? "x-tree-node-expanded-overlay-share" : "x-tree-node-expanded";
                    rc1 = is_sharing ? "expressomail-node-remote-overlay-share" : "expressomail-node-remote";
                    rc2 = is_sharing ? "expressomail-node-remote-open-overlay-share" : "expressomail-node-remote-open";
                }else{
                    cls += "-plus";
                    c1 = is_sharing ? "x-tree-node-expanded-overlay-share" : "x-tree-node-expanded";
                    c2 = is_sharing ? "x-tree-node-collapsed-overlay-share" : "x-tree-node-collapsed";
                    rc1 = is_sharing ? "expressomail-node-remote-open-overlay-share" : "expressomail-node-remote-open";
                    rc2 = is_sharing ? "expressomail-node-remote-overlay-share" : "expressomail-node-remote";
                }

                if(this.wasLeaf){
                    if (!is_sharing) {
                        Ext.fly(this.elNode).removeClass("x-tree-node-leaf");
                    }
                    this.wasLeaf = false;
                } else {
                    if (is_sharing && !Ext.fly(this.elNode).hasClass("x-tree-node-leaf")) {
                        Ext.fly(this.elNode).addClass("x-tree-node-leaf");
                    } else if (!is_sharing && Ext.fly(this.elNode).hasClass("x-tree-node-leaf")) {
                        Ext.fly(this.elNode).removeClass("x-tree-node-leaf");
                    }
                }
                if(this.c1 != c1 || this.c2 != c2){
                        Ext.fly(this.elNode).replaceClass(c1, c2);
                        if (Ext.fly(this.elNode).hasClass(rc1)) {
                            Ext.fly(this.elNode).replaceClass(rc1, rc2);
                        }
                        this.c1 = c1; this.c2 = c2;
                    }
            }else{
                if(!this.wasLeaf){
                    Ext.fly(this.elNode).replaceClass(
                        is_sharing ? "x-tree-node-expanded-overlay-share" : "x-tree-node-expanded",
                        is_sharing ? "x-tree-node-collapsed-overlay-share" : "x-tree-node-collapsed");
                    delete this.c1;
                    delete this.c2;
                    this.wasLeaf = true;
                }
            }
            var ecc = "x-tree-ec-icon "+cls;
            if(this.ecc != ecc){
                this.ecNode.className = ecc;
                this.ecc = ecc;
            }
        }
    }
});
