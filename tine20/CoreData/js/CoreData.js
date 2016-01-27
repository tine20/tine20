/**
 * Tine 2.0
 * 
 * @package     CoreData
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2016 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

Ext.ns('Tine', 'Tine.CoreData');

/**
 * @namespace   Tine.CoreData
 * @class       Tine.CoreData.Application
 * @extends     Tine.Tinebase.Application
 * CoreData Application Object <br>
 * 
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */
Tine.CoreData.Application = Ext.extend(Tine.Tinebase.Application, {
});

// default mainscreen
Tine.CoreData.MainScreen = Ext.extend(Tine.widgets.MainScreen, {

    /**
     * get north panel for given contentType
     *
     * template method to be overridden by subclasses to modify default behaviour
     *
     * @param {String} contentType
     * @return {Ext.Panel}
     */
    getNorthPanel: function(contentType) {
        contentType = contentType || this.getActiveContentType();

        if (! this[contentType + 'ActionToolbar']) {

            if (contentType === '') {
                // return empty toolbar
                this[contentType + 'ActionToolbar'] = new Ext.Toolbar({
                    items: []
                });

            } else {
                this[contentType + 'ActionToolbar'] = Ext.isFunction(this[contentType + this.centerPanelClassNameSuffix].getActionToolbar)
                    ? this[contentType + this.centerPanelClassNameSuffix].getActionToolbar()
                    : Tine.CoreData.Manager.getToolbar(contentType);
            }
        }

        return this[contentType + 'ActionToolbar'];
    },

    /**
     * get center panel for given contentType / core data
     *
     * template method to be overridden by subclasses to modify default behaviour
     *
     * @param {String} contentType
     * @return {Ext.Panel}
     */
    getCenterPanel: function(contentType) {
        contentType = contentType || this.getActiveContentType();

        if (! this[contentType + this.centerPanelClassNameSuffix]) {

            if (contentType === '') {
                // show some information text or remove this as mainscreen is responsibilty of core data models/apps
                this[contentType + this.centerPanelClassNameSuffix] = new Ext.Panel({
                    // TODO improve wording/styling
                    html: this.app.i18n._('Please select core data from tree ...')
                });
            } else {
                // try to find grid in Core Data Manager
                this[contentType + this.centerPanelClassNameSuffix] = Tine.CoreData.Manager.getGrid(contentType);
            }
        }

        return this[contentType + this.centerPanelClassNameSuffix];
    },

    /**
     * get west panel for given contentType
     *
     * template method to be overridden by subclasses to modify default behaviour
     *
     * @return {Ext.Panel}
     *
     * overwrites parent to remove active content type here
     *
     * TODO remove code duplication
     */
    getWestPanel: function() {
        var wpName = 'WestPanel';

        if (! this[wpName]) {
            var wpconfig = {
                app: this.app,
                //contentTypes: this.contentTypes,
                //contentType: contentType,
                listeners: {
                    scope: this,
                    selectionchange: function() {
                        var cp = this.getCenterPanel();
                        if(cp) {
                            try {
                                var grid = cp.getGrid();
                                if(grid) {
                                    var sm = grid.getSelectionModel();
                                    if(sm) {
                                        sm.clearSelections();
                                        cp.actionUpdater.updateActions(sm.getSelectionsCollection());
                                    }
                                }
                            } catch (e) {
                                // do nothing - no grid
                            }
                        }
                    }
                }
            };
            try {
                if(Tine[this.app.name].hasOwnProperty(wpName)) this[wpName] = new Tine[this.app.appName][wpName](wpconfig);
                else this[wpName] = new Tine.widgets.mainscreen.WestPanel(wpconfig);
            } catch (e) {
                Tine.log.error('Could not create westPanel');
                Tine.log.error(e.stack ? e.stack : e);
                this[wpName] = new Ext.Panel({html: 'ERROR'});
            }
        }
        return this[wpName];
    },
});
