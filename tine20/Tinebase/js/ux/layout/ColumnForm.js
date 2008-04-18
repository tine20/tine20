/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
 
Ext.namespace('Ext.ux', 'Ext.ux.layout');

/**
 * Helper function to create multi column forms
 */
Ext.ux.layout.ColumnFormLayout = function(config) {
    var items = [];
        
    // each item is an array with the config of one row
    for (var i=0,j=config.items.length; i<j; i++) {
        
        var initialRowConfig = config.items[i];
        var rowConfig = {
            layout: 'column',
            items: [],
        };
        // each row consits n column objects 
        for (var n=0,m=initialRowConfig.length; n<m; n++) {
            var column = initialRowConfig[n];
            rowConfig.items.push({
                columnWidth: column.columnWidth ? column.columnWidth : config.defaults.columnWidth,
                layout: 'form',
                labelAlign: config.labelAlign,
                defaults: config.defaults,
                bodyStyle: 'padding-right: 5px;',
                items: column
            });
        }
        items.push(rowConfig);
    }

    return {
        layout: 'anchor',
        onResize: function(w, h){
            this.items.each(function(item){
                if(item.rendered) {
                    item.setWidth(w);
                }
            });
        },
        items: items
    }
    
}
