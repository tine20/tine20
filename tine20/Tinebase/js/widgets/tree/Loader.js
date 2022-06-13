/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
Ext.ns('Tine.widgets', 'Tine.widgets.tree');

/**
 * generic tree loader for tine trees
 * - calls json method with a filter to return children of a node
 * 
 * @namespace   Tine.widgets.tree
 * @class       Tine.widgets.tree.Loader
 * @extends     Ext.tree.TreeLoader
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

    initComponent: function () {
        this.addEvents(
            /**
             * Fires when virtual nodes are selected
             *
             * @param selected nodes
             */
            'virtualNodesSelected'
        );

        this.supr().initComponent.apply(this, arguments);
    },

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
        };
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
        response.responseData = o.hasOwnProperty('totalcount') ? o.results : o;
        
        // processed nodes / structures
        var newResponse = [];

        // read every node
        Ext.each(response.responseData, function (node) {
            var containerName,
                parentNode = newResponse;

            if (!Ext.isString(node.name)) {
                parentNode.push(node);
                return;
            }

            // Get folder name to final container
            // NOTE: if hierarchy ends with a "/" name gets appended otherwise last part of hierarchy is the display name
            var hierarchy = String(node.hierarchy).match(/\/$/) || !node.hierarchy ? node.hierarchy || '' + node.name : node.hierarchy,
                parts = Ext.isString(hierarchy) ? hierarchy.split("/") : [''];
            containerName = parts[parts.length - 1];

            // Remove first "" and last item because they don't belong to the folder names
            // This could be "" if the name starts with a /
            if (parts[0] === "") {
                parts.shift();
            }
            
            parts.pop();

            Ext.each(parts, function (part, idx, parts, node) {
                var child = this.findNodeByName(part, parentNode);

                this.preloadChildren = true;
                
                if (! child) {
                    var reducedParts = [];
                    
                    // add shared or personal
                    reducedParts.push(node.path.split('/')[1]);
                    if(node.path.match(/^\/personal/)) {
                        // add accountId
                        reducedParts.push(node.path.split('/')[2]);
                    }
                    
                    // create a path for each virtual node, this path is actually invalid and won't be send to server!
                    for(var i = 0; i <= parts.indexOf(part); ++i) {
                        reducedParts.push(parts[i]);    
                    }
                    
                    child = {
                        'name': part,
                        'path': '/' + reducedParts.join('/'),
                        'id': Ext.id(),
                        'children': [],
                        'leaf': false,
                        'editable': false,
                        'draggable': false,
                        'allowDrag': false,
                        'allowDrop': false,
                        'singleClickExpand': true,
                        'listeners': {
                            'click': function (node) {
                                node.expand(true, true, function (node) {
                                    var nodes = [];
                                    this.findAllNodes(node, nodes);
                                    this.fireEvent('virtualNodesSelected', nodes);
                                }.createDelegate(this, [node], false));

                                return false;
                            }.createDelegate(this)
                        }
                    };
                    parentNode.push(child);
                }

                parentNode = child.children;
            }.createDelegate(this, [node], true), this);

            var nodePathSegments = node.path.split('/');
            var containerId = nodePathSegments.pop();
            nodePathSegments = nodePathSegments.concat(parts);
            nodePathSegments.push(containerId);
            
            node.longName = node.name;
            node.text = node.name = containerName;
            
            node.originalPath = node.path;
            node.path = nodePathSegments.join('/');
            
            
            parentNode.leaf = true;
            parentNode.push(node);
        }, this);

        response.responseData = newResponse;

        return Tine.widgets.tree.Loader.superclass.processResponse.apply(this, arguments);
    },

    /**
     * Finds children of a node
     *
     * @param node
     * @param nodes
     */
    findAllNodes: function (node, nodes) {
        if (node.leaf && !node.hasChildNodes()) {
            nodes.push(node);
        }

        for (var i = 0; i < node.childNodes.length; i++) {
            this.findAllNodes(node.childNodes[i], nodes);
        }
    },

    /**
     * Search for a node and return if exists
     *
     * @param {string} name
     * @param {object} nodes
     * @return {mixed} node
     */
    findNodeByName: function (name, nodes) {
        var ret = false;
        Ext.each(nodes, function (node) {
            if (node && node.name && node.name == name && Ext.isArray(node.children)) {
                ret = node;
            }
        }, this);
        return ret;
    },
    
    expandChildNode: function (parentNode, childNode) {
        parentNode.expand(1, false, () => {
            const node = parentNode.findChild('name', childNode.text);
            if (!node) {
                parentNode.appendChild(childNode);
            } else if (node?.text !== childNode?.text) {
                // node got duplicated by expand load
                try {
                    node.cancelExpand();
                    node.remove(true);
                } catch (e) {
                }
            }
        });
    }
 });
