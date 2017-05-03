/*
 * Tine 2.0
 *
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Michael Spahn <m.spahn@metaways.de>
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
Ext.ns('Tine.Addressbook');

Ext.onReady(function() {
    Tine.Addressbook.ResourceGridPanel = Ext.extend(Tine.Calendar.ResourcesGridPanel, {
        initLayout: function () {
            return Tine.Calendar.ResourcesGridPanel.superclass.initLayout.apply(this, arguments)
        }
    });

    Tine.Addressbook.ResourceTreePanel = Ext.extend(Ext.Panel, {
        getFilterPlugin: function () {
            return {
                init: Ext.emptyFn
            };
        }
    });
});