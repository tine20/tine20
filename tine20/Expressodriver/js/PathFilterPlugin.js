/*
 * Tine 2.0
 *
 * @package     Expressodriver
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @copyright   Copyright (c) 2014 Serpro (http://www.serpro.gov.br)
 * @author      Marcelo Teixeira <marcelo.teixeira@serpro.gov.br>
 * @author      Edgar de Lucca <edgar.lucca@serpro.gov.br>
 *
 */
Ext.ns('Tine.Expressodriver');

/**
 * filter plugin for container tree
 *
 * @namespace Tine.widgets.tree
 * @class     Tine.Expressodriver.PathFilterPlugin
 * @extends   Tine.widgets.grid.FilterPlugin
 */
Tine.Expressodriver.PathFilterPlugin = Ext.extend(Tine.widgets.tree.FilterPlugin, {

    /**
     * select tree node(s)
     *
     * @param {String} value
     */
    selectValue: function(value) {
        var values = Ext.isArray(value) ? value : [value];
        Ext.each(values, function(value) {
            var path = Ext.isString(value) ? value : (value ? value.path : '') || '/',
                treePath = this.treePanel.getTreePath(path),
                attr = null;

            if (treePath.split('/').length === 3){
                attr = 'name';
            }
            this.selectPath.call(this.treePanel, treePath, attr, function() {
                // mark this expansion as done and check if all are done
                value.isExpanded = true;
                var allValuesExpanded = true;
                Ext.each(values, function(v) {
                    allValuesExpanded &= v.isExpanded;
                }, this);

                if (allValuesExpanded) {
                    this.treePanel.getSelectionModel().resumeEvents();

                    // @TODO remove this code when fm is cleaned up conceptually
                    //       currentFolderNode -> currentFolder
                    this.treePanel.updateActions(this.treePanel.getSelectionModel(), this.treePanel.getSelectionModel().getSelectedNode());
                    Tine.Tinebase.appMgr.get('Expressodriver').getMainScreen().getCenterPanel().currentFolderNode = this.treePanel.getSelectionModel().getSelectedNode();
                }
            }.createDelegate(this), true);
        }, this);
    }
});
