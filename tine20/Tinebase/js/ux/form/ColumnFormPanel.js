/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
 
Ext.ns('Ext.ux', 'Ext.ux.form');

/**
 * @namespace   Ext.ux.form
 * @class       Ext.ux.form.ColumnFormPanel
 * @description
 * Helper Class for creating form panels with a horizontal layout. This class could be directly
 * created using the new keyword or by specifying the xtype: 'columnform'
 * Example usage:</p>
 * <pre><code>
var p = new Ext.ux.form.ColumnFormPanel({
    title: 'Horizontal Form Layout',
    items: [
        [
            {
                columnWidth: .6,
                fieldLabel:'Company Name', 
                name:'org_name'
            },
            {
                columnWidth: .4,
                fieldLabel:'Street', 
                name:'adr_one_street'
            }
        ],
        [
            {
                columnWidth: .7,
                fieldLabel:'Region',
                name:'adr_one_region'
            },
            {
                columnWidth: .3,
                fieldLabel:'Postal Code', 
                name:'adr_one_postalcode'
            }
        ]
    ]
});
</code></pre>
 */
Ext.ux.form.ColumnFormPanel = Ext.extend(Ext.Panel, {

    formDefaults: {
        xtype:'icontextfield',
        anchor: '100%',
        labelSeparator: '',
        columnWidth: .333
    },
    
    layout: 'hfit',
    labelAlign: 'top',
    /**
     * @private
     */
    initComponent: function() {
        var items = [];
            
        // each item is an array with the config of one row
        for (var i=0,j=this.items.length; i<j; i++) {
            
            var initialRowConfig = this.items[i];
            var rowConfig = {
                border: false,
                layout: 'column',
                items: []
            };
            // each row consits n column objects 
            for (var n=0,m=initialRowConfig.length; n<m; n++) {
                var column = initialRowConfig[n];
                var idx = rowConfig.items.push({
                    columnWidth: column.columnWidth ? column.columnWidth : this.formDefaults.columnWidth,
                    layout: 'form',
                    labelAlign: this.labelAlign,
                    defaults: this.formDefaults,
                    bodyStyle: 'padding-right: 5px;',
                    border: false,
                    items: column
                });
                
                if (column.width) {
                    rowConfig.items[idx-1].width = column.width;
                    delete rowConfig.items[idx-1].columnWidth;
                }
            }
            items.push(rowConfig);
        }
        this.items = items;
        
        Ext.ux.form.ColumnFormPanel.superclass.initComponent.call(this);
    }
});

Ext.reg('columnform', Ext.ux.form.ColumnFormPanel);

