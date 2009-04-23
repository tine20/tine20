/**
 * Tine 2.0
 * 
 * @package     ExampleApplication
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
 
Ext.namespace('Tine.ExampleApplication');

Tine.ExampleApplication.TreePanel = Ext.extend(Tine.widgets.grid.PersistentFilterPicker, {
    
    // quick hack to get filter saving grid working
    //recordClass: Tine.ExampleApplication.Model.ExampleRecord,
    
    initComponent: function() {
        this.filterMountId = 'ExampleRecord';
        
        this.root = {
            id: 'root',
            leaf: false,
            expanded: true,
            children: [{
                text: this.app.i18n._('ExampleRecords'),
                id: 'ExampleRecord',
                iconCls: 'ExampleApplicationExampleRecord',
                expanded: true,
                children: [{
                    text: this.app.i18n._('All ExampleRecords'),
                    id: 'allrecords',
                    leaf: true
                }]
            }]
        };
        
    	Tine.ExampleApplication.TreePanel.superclass.initComponent.call(this);
        
    	/*
        this.on('click', function(node) {
            if (node.attributes.isPersistentFilter != true) {
                var contentType = node.getPath().split('/')[2];
                
                this.app.getMainScreen().activeContentType = contentType;
                this.app.getMainScreen().show();
            }
        }, this);
        */
	},
    
    /**
     * @private
     */
    afterRender: function() {
        Tine.ExampleApplication.TreePanel.superclass.afterRender.call(this);
        var type = this.app.getMainScreen().activeContentType;

        this.expandPath('/root/' + type + '/allrecords');
        this.selectPath('/root/' + type + '/allrecords');
    },
    
    /**
     * returns a filter plugin to be used in a grid
     * 
     * ???
     */
    getFilterPlugin: function() {
        if (!this.filterPlugin) {
            var scope = this;
            this.filterPlugin = new Tine.widgets.grid.FilterPlugin({
                getValue: function() {
                    var nodeAttributes = scope.getSelectionModel().getSelectedNode().attributes || {};
                    return [
                        //{field: 'containerType', operator: 'equals', value: nodeAttributes.containerType ? nodeAttributes.containerType : 'all' },
                        //{field: 'container',     operator: 'equals', value: nodeAttributes.container ? nodeAttributes.container.id : null       },
                        //{field: 'owner',         operator: 'equals', value: nodeAttributes.owner ? nodeAttributes.owner.accountId : null        }
                    ];
                }
            });
        }
        
        return this.filterPlugin;
    }
});

//Tine.ExampleApplication.FilterPanel = Tine.widgets.grid.PersistentFilterPicker

/**
 * default ExampleRecord backend
 */
Tine.ExampleApplication.recordBackend = new Tine.Tinebase.widgets.app.JsonBackend({
    appName: 'ExampleApplication',
    modelName: 'ExampleRecord',
    recordClass: Tine.ExampleApplication.Model.ExampleRecord
});
