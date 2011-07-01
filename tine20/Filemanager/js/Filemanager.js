/*
 * Tine 2.0
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine.Filemanager');

/**
 * @namespace Tine.Filemanager
 * @class Tine.Filemanager.Application
 * @extends Tine.Tinebase.Application
 * 
 * @author Philipp Schüle <p.schuele@metaways.de>
 */
Tine.Filemanager.Application = Ext.extend(Tine.Tinebase.Application, {

	hasMainScreen : true,

	/**
	 * Get translated application title of this application /test
	 * 
	 * @return {String}
	 */
	getTitle : function() {
		return this.i18n.gettext('Filemanager');
	}
});

/**
 * @namespace Tine.Filemanager
 * @class Tine.Filemanager.MainScreen
 * @extends Tine.widgets.MainScreen
 * 
 * @author Martin Jatho <m.jatho@metaways.de>
 */
Tine.Filemanager.MainScreen = Ext.extend(Tine.widgets.MainScreen, {});

/**
 * @namespace Tine.Filemanager
 * @class Tine.Filemanager.TreePanel
 * @extends Tine.widgets.container.TreePanel
 * 
 * @author Martin Jatho <m.jatho@metaways.de>
 */
Tine.Filemanager.TreePanel = Ext.extend(Tine.widgets.container.TreePanel, {
//	filterMode : 'filterToolbar',
	recordClass : Tine.Filemanager.Model.Node,
	allowMultiSelection : false, 
	plugins : [ {
		ptype : 'ux.browseplugin',
		enableFileDialog: false,
		multiple : true,
		handler : function() {
			alert("tree drop");
		}
	} ],
	
	/**
     * returns a filter plugin to be used in a grid
     */
    getFilterPlugin: function() {
        if (!this.filterPlugin) {
            this.filterPlugin = new Tine.widgets.tree.FilterPlugin({
                treePanel: this,
                field: 'parent_id'
            });
        }
        
        return this.filterPlugin;
    },
    
    onClick: function(node, e) {
       	
    	var actionToolbar = this.app.mainScreen.ActionToolbar;
    	var items = actionToolbar.get(0).items.items;
    	
    	if(node.attributes.account_grants) {
	    	if(node.attributes.account_grants.addGrant) {
	    		items[0].enable();
	    	}
	    	else items[0].disable();
	    	
	    	if(node.attributes.account_grants.deleteGrant) {
	    		items[1].enable();
	    	}
	    	else items[1].disable();
	    	
	    	if(node.attributes.account_grants.addGrant) {
	    		items[2].enable();
	    	}
	    	else items[2].disable();
	    	
	    	if(node.attributes.account_grants.exportGrant || node.attributes.account_grants.readGrant) {
	    		items[4].enable();
	    	}
	    	else items[4].disable();
    	}
    	else {
    		items[0].disable();
    		items[1].disable();
    		items[4].disable();
    		items[2].enable();
			items[3].enable();
    		
    		if(node.isRoot) {
    			items[2].disable();
    			items[3].disable();
    		}
    	}
    	
    	Tine.Filemanager.TreePanel.superclass.onClick.call(this, node, e);

    }
    
    
});
