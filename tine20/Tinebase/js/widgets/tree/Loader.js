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

    // trick parent
    url: true,
    
    /**
     * request data
     * 
     * @param {} node
     * @param {} callback
     * @private
     */
    requestData: function(node, callback, scope){
        if(this.fireEvent("beforeload", this, node, callback) !== false){
            
            this.transId = Ext.Ajax.request({
                params: {
                    method: this.method,
                    filter: this.filter
                },
                success: this.handleResponse,
                failure: this.handleFailure,
                scope: this,
                argument: {callback: callback, node: node, scope: scope}
            });
        } else {
            // if the load is cancelled, make sure we notify
            // the node that we are done
            this.runCallback(callback, scope || node, []);
        }
    },
    
    /**
     * process response
     * 
     * @param {} response
     * @param {} node
     * @param {} callback
     */
    processResponse : function(response, node, callback){
        var data = Ext.util.JSON.decode(response.responseText);
        var o = data.results;
        
        try {
            node.beginUpdate();
            for(var i = 0, len = o.length; i < len; i++){
                var n = this.createNode(o[i]);
                if(n){
                    node.appendChild(n);
                }
            }
            node.endUpdate();
            if(typeof callback == "function"){
                callback(this, node);
            }
        }catch(e){
            this.handleFailure(response);
        }
    }    
 });
