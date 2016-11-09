/*
 * Tine 2.0
 * 
 * @package     Events
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Stefanie Stamer <s.stamer@metaways.de>
 * @copyright   Copyright (c) 2007-2015 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.ns('Tine.Events');

/**
 * Events grid panel
 * 
 * @namespace   Tine.Events
 * @class       Tine.Events.EventGridPanel
 * @extends     Tine.widgets.grid.GridPanel
 * 
 * <p>Events Grid Panel</p>
 * <p><pre>
 * </pre></p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Stefanie Stamer <s.stamer@metaways.de>
 * @copyright   Copyright (c) 2007-2015 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Events.EventGridPanel
 */
Tine.Events.EventGridPanel = Ext.extend(Tine.widgets.grid.GridPanel, {
    copyEditAction: true,
    initComponent: function() {
        this.initDetailsPanel();
        Tine.Events.EventGridPanel.superclass.initComponent.call(this);
    },
    
    /**
     * @private
     */
    initDetailsPanel: function() {
        this.detailsPanel = new Tine.Events.EventDetailsPanel({
            grid : this,
            app: this.app
        });
    },
    
    /**
     * returns view row class (scope is this.grid.view)
     */
    getViewRowClass: function(record, index, rp, ds) {
        
        var className = Tine.Events.EventGridPanel.superclass.getViewRowClass(record, index, rp, ds),
            color = '',
            actionTypeConfig = Tine.Events.registry.get('config').actionType.value.records,
            config;
       
        if(record.get('action')) {
           for (var i = 0; i < actionTypeConfig.length; i++) {
                if (actionTypeConfig[i].id === record.get('action')) {
                    config = actionTypeConfig[i];
                }
            }
        }
        
        if (config && config.color) {
            rp.tstyle += 'color: ' + config.color + ';';
        }
        return className;
    }
});
