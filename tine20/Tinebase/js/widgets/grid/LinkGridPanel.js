/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * TODO         add relations stuff
 */
Ext.ns('Tine.widgets.grid');

/**
 * Link GridPanel
 * 
 * @namespace   Tine.widgets.grid
 * @class       Tine.widgets.grid.LinkGridPanel
 * @extends     Tine.widgets.grid.PickerGridPanel
 * 
 * <p>Link GridPanel</p>
 * <p><pre>
 * </pre></p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.widgets.grid.LinkGridPanel
 */
Tine.widgets.grid.LinkGridPanel = Ext.extend(Tine.widgets.grid.PickerGridPanel, {
    /**
     * @private
     */
    initComponent: function() {
        Tine.widgets.grid.LinkGridPanel.superclass.initComponent.call(this);
    }
});

Ext.reg('wdgt.linkgrid', Tine.widgets.grid.LinkGridPanel);
