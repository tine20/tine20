/**
 * Tine 2.0
 *
 * @package     CoreData
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2016 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

Ext.ns('Tine', 'Tine.CoreData');

/**
 * @namespace   Tine.CoreData
 * @class       Tine.CoreData.TreePanel
 * @extends     Tine.Tinebase.Application
 * CoreData TreePanel<br>
 *
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */
Tine.CoreData.TreePanel = function (config) {
    Ext.apply(this, config);
    this.id = 'TreePanel';
    Tine.CoreData.TreePanel.superclass.constructor.call(this);
};
Ext.extend(Tine.CoreData.TreePanel, Ext.tree.TreePanel, {

    autoScroll: true,
    border: false,

    /**
     * init this treePanel
     */
    initComponent: function () {
        if (!this.app) {
            this.app = Tine.Tinebase.appMgr.get('CoreData');
        }

        var generalChildren = this.getCoreDataNodes('/general'),
            applicationChildren = this.getCoreDataNodes('/applications');

        this.root = {
            path: '/',
            cls: 'tinebase-tree-hide-collapsetool',
            text: this.app.i18n._('Core Data'),
            expanded: true,
            children: [{
                path: '/general',
                id: 'general',
                expanded: true,
                leaf: (generalChildren.length == 0),
                text: this.app.i18n._('General Data'),
                children: generalChildren
            }, {
                path: '/applications',
                id: 'applications',
                expanded: true,
                text: this.app.i18n._('Application Data'),
                children: applicationChildren
            }]
        };

        this.on('click', this.onClick, this);

        Tine.CoreData.TreePanel.superclass.initComponent.call(this);
    },

    /**
     * get core data nodes
     *
     * @param path
     * @returns {*}
     *
     * TODO translate application names
     */
    getCoreDataNodes: function (path) {
        var applicationNodes = [],
            coreDataNodes = {}; // applications => [core data nodes]

        Ext.each(Tine.CoreData.registry.get('coreData')['results'], function (coreData) {
            if ((path === '/applications' && coreData.application_id.name !== 'Tinebase') ||
                (path === '/general' && coreData.application_id.name === 'Tinebase')) {

                if (! coreDataNodes[coreData.application_id.name]) {
                    coreDataNodes[coreData.application_id.name] = [];
                    applicationNodes.push({
                        path: path + '/' + coreData.application_id.id,
                        id: coreData.application_id.id,
                        text: _(coreData.application_id.name),
                        attributes: coreData.application_id
                    });
                }

                coreDataNodes[coreData.application_id.name].push({
                    path: path + '/' + coreData.application_id.id + '/' + coreData.id,
                    id: coreData.id,
                    text: this.app.i18n._(coreData.label),
                    leaf: true,
                    attributes: coreData
                });
            }
        }, this);

        if (path === '/general') {
            return (coreDataNodes['Tinebase']) ? coreDataNodes['Tinebase'] : [];
        } else {
            Ext.each(applicationNodes, function(node) {
                node.children = coreDataNodes[node.attributes.name]
            });
            return applicationNodes;
        }
    },

    /**
     * on node click
     *
     * @param {} node
     * @param {} e
     */
    onClick: function (node, e) {
        // switch content type and set north + center panels
        if (node.attributes.attributes && node.attributes.attributes.id && node.leaf) {
            Tine.log.debug('Tine.CoreData.TreePanel::onClick');
            Tine.log.debug(node);

            var mainscreen = this.app.getMainScreen();

            mainscreen.activeContentType = node.attributes.attributes.id;
            mainscreen.show();
            mainscreen.getCenterPanel().getStore().reload();
        } else {
            return false;
        }
    }
});
