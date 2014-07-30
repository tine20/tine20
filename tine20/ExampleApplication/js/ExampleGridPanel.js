/*
 * Tine 2.0
 * 
 * @package     ExampleApplication
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.namespace('Tine.ExampleApplication');

/**
 * Example grid panel
 * 
 * @namespace   Tine.ExampleApplication
 * @class       Tine.ExampleApplication.ExampleGridPanel
 * @extends     Tine.widgets.grid.GridPanel
 * 
 * <p>Example Grid Panel</p>
 * <p><pre>
 * </pre></p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.ExampleApplication.ExampleGridPanel
 */
Tine.ExampleApplication.ExampleGridPanel = Ext.extend(Tine.widgets.grid.GridPanel, {
    initComponent: function() {
        this.initDetailsPanel();
        Tine.ExampleApplication.ExampleGridPanel.superclass.initComponent.call(this);
    },
    
    /**
     * @private
     */
    initDetailsPanel: function() {
        this.detailsPanel = new Tine.ExampleApplication.ExampleDetailsPanel({
            grid : this,
            app: this.app
        });
    }
});
