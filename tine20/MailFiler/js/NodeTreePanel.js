/*
 * Tine 2.0
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine.MailFiler');

require('./nodeContextMenu');

Tine.MailFiler.NodeTreePanel = Ext.extend(Tine.Filemanager.NodeTreePanel, {
    recordClass : Tine.MailFiler.Model.Node,

    /**
     * initiates tree context menus
     *
     * @private
     */
    initContextMenu: function() {
        this.ctxMenu = Tine.MailFiler.nodeContextMenu.getMenu({
            actionMgr: Tine.MailFiler.nodeActionsMgr,
            nodeName: this.recordClass.getContainerName(),
            actions: ['reload', 'createFolder', 'delete', 'rename', 'move' , 'edit'],
            scope: this,
            backend: 'MailFiler',
            backendModel: 'Node'
        });

        this.actionUpdater = new Tine.widgets.ActionUpdater({
            recordClass: this.recordClass,
            actions: this.ctxMenu.items
        });
    }
});
