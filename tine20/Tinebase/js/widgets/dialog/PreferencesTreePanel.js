/*
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  widgets
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 * @todo        create generic app tree panel?
 * @todo        add button: set default value(s)
 */

Ext.ns('Tine.widgets', 'Tine.widgets.dialog');

/**
 * preferences application tree panel
 * 
 * @namespace   Tine.widgets.dialog
 * @class       Tine.widgets.dialog.PreferencesTreePanel
 * @extends     Ext.tree.TreePanel
 */
Tine.widgets.dialog.PreferencesTreePanel = Ext.extend(Ext.tree.TreePanel, {

    // presets
    iconCls: 'x-new-application',
    rootVisible: true,
    border: false,
    autoScroll: true,
    
    /**
     * initComponent
     * 
     */
    initComponent: function(){
        
        Tine.widgets.dialog.PreferencesTreePanel.superclass.initComponent.call(this);
        
        this.initTreeNodes();
        this.initHandlers();
        this.selectRoot.defer(200, this);
    },

    /**
     * select root node
     */
    selectRoot: function() {
    	this.fireEvent('click', this.getRootNode());
    },
    
    /**
     * initTreeNodes with Tinebase and apps prefs
     * 
     * @private
     */
    initTreeNodes: function() {
    	
    	// general preferences are tree root
        var treeRoot = new Ext.tree.TreeNode({
            text: _('General Preferences'),
            id: 'Tinebase',
            draggable: false,
            allowDrop: false,
            expanded: true
        });
        this.setRootNode(treeRoot);
        
        // add all apps
        var allApps = Tine.Tinebase.appMgr.getAll();

        // sort nodes by translated title (text property)
        new Ext.tree.TreeSorter(this, {
            folderSort: true,
            dir: "asc"
        });        

        // console.log(allApps);
        allApps.each(function(app) {
            var node = new Ext.tree.TreeNode({
                text: app.getTitle(),
                cls: 'file',
                id: app.appName,
                leaf: null
            });
    
            treeRoot.appendChild(node);
        }, this);
    },
    
    /**
     * initTreeNodes with Tinebase and apps prefs
     * 
     * @private
     */
    initHandlers: function() {
        this.on('click', function(node){
            // note: if node is clicked, it is not selected!
            node.getOwnerTree().selectPath(node.getPath());
            node.expand();
            
            // get parent pref panel
            var parentPanel = this.findParentByType(Tine.widgets.dialog.Preferences);

            // add panel to card panel to show prefs for chosen app
            parentPanel.showPrefsForApp(node.id);
            
        }, this);
        
        this.on('beforeexpand', function(_panel) {
            if(_panel.getSelectionModel().getSelectedNode() === null) {
                _panel.expandPath('/Tinebase');
                _panel.selectPath('/Tinebase');
            }
            _panel.fireEvent('click', _panel.getSelectionModel().getSelectedNode());
        }, this);
    },

    /**
     * check grants for tree nodes / apps
     * 
     * @param {Bool} adminMode
     */
    checkGrants: function(adminMode) {
        var root = this.getRootNode();
                
        root.eachChild(function(node) {
            // enable or disable according to admin rights / admin mode
            if (!Tine.Tinebase.common.hasRight('admin', node.id) && adminMode) {
                node.disable();
            } else {
                node.enable();
            }
        });
    }
});
