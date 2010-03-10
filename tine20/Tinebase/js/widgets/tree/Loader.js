/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
Ext.namespace('Tine.widgets', 'Tine.widgets.tree');

/**
 * generic tree loader for tine trees
 * - calls json method with a filter to return children of a node
 * 
 * @class Tine.widgets.tree.Loader
 * @extends Ext.tree.TreeLoader
 */
Tine.widgets.tree.Loader = Ext.extend(Ext.tree.TreeLoader, {
    /**
     * @cfg {Number} how many chars of the containername to display
     */
    displayLength: 25,
    
    /**
     * @cfg {application}
     */
    app: null,
    
    /**
     * 
     * @cfg {String} method
     */
    method: null,
    
    /**
     * 
     * @cfg {Array} of filter objects for search method 
     */
    filter: null,
    
    url: 'index.php',
    
    /**
     * @private
     */
    createNode: function() {
        this.inspectCreateNode.apply(this, arguments);
        return Tine.widgets.tree.Loader.superclass.createNode.apply(this, arguments);
    },
    
    /**
     * returns params for async request
     * 
     * @param {Ext.tree.TreeNode} node
     * @return {Object}
     */
    getParams: function(node) {
        return {
            method: this.method,
            filter: this.filter
        }
    },
    
    /**
     * template fn for subclasses to inspect createNode
     * 
     * @param {Object} attr
     */
    inspectCreateNode: Ext.EmptyFn,
    
    processResponse: function(response, node, callback, scope) {
        // convert tine search response into usual treeLoader structure
        var o = response.responseData || Ext.decode(response.responseText);
        if (o.totalcount) {
            // take results part as response only
            response.responseData = o.results;
        }
        
        return Tine.widgets.tree.Loader.superclass.processResponse.apply(this, arguments);
    }
 });
