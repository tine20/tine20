/*
 * Tine 2.0
 * 
 * @package     RequestTracker
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:Phone.css 4159 2008-09-02 14:15:05Z p.schuele@metaways.de $
 */
 
Ext.ns('Tine.RequestTracker');

/**
 * @class Tine.RequestTracker.TreePanel
 * @extends Ext.tree.TreePanel
 * @constructor
 */
Tine.RequestTracker.TreePanel = Ext.extend(Ext.tree.TreePanel, {
    initComponent: function() {
        this.root = {
            id: 'queues',
            text: this.app.i18n._('All Queues'),
            leaf: false,
            expanded: true
        };
        
        this.loader = new Ext.tree.TreeLoader({
            url: 'index.php',
            baseParams: {
                jsonKey: Tine.Tinebase.registry.get('jsonKey'),
                requestType: 'JSON',
                method: 'RequestTracker.searchQueues'
            },
            baseAttrs: {
                leaf: true,
                iconCls: 'x-tree-node-icon'
            }
        });
        Tine.RequestTracker.TreePanel.superclass.initComponent.call(this);
    }
});