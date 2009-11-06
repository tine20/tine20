/* 
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */
 
Ext.ns('Tine.Calendar');

/**
 * @class Tine.Calendar.EventDetailsPanel
 * @namespace Tine.Calendar
 * @extends Tine.Tinebase.widgets.grid.DetailsPanel
 * @author Cornelius Weiss <c.weiss@metaways.de>
 * @version $Id$
 */
Tine.Calendar.EventDetailsPanel = Ext.extend(Tine.Tinebase.widgets.grid.DetailsPanel, {
    
    initComponent: function() {
        this.app = Tine.Tinebase.appMgr.get('Calendar');
        
        this.eventDetailsPanel = this.getEventDetailsPanel();
        
        this.items = [
            this.eventDetailsPanel
        ];
        
        this.supr().initComponent.call(this);
    },
    
    getEventDetailsPanel: function() {
        return new Ext.ux.display.DisplayPanel ({
            //xtype: 'displaypanel',
            layout: 'fit',
            items: [{
                layout: 'hbox',
                layoutConfig: {
                    padding:'5',
                    align:'stretch'
                },
                defaults:{margins:'0 5 0 0'},
                items: [{
                    flex: 2,
                    layout: 'ux.display',
                    layoutConfig: {
                        background: 'solid'
                    },
                    items: [{
                        xtype: 'ux.displayfield',
                        name: 'location',
                        fieldLabel: this.app.i18n._('Location')
                    }, {
                        xtype: 'ux.displayfield',
                        name: 'dtstart',
                        fieldLabel: this.app.i18n._('Start Time'),
                        renderer: Tine.Tinebase.common.dateTimeRenderer
                    }, {
                        xtype: 'ux.displayfield',
                        name: 'dtend',
                        fieldLabel: this.app.i18n._('End Time'),
                        renderer: Tine.Tinebase.common.dateTimeRenderer
                    }]
                }, {
                    flex: 2,
                    html: 'attendee'
                }, {
                    flex: 3,
                    layout: 'fit',
                    items: [{
                        xtype: 'ux.displaytextarea',
                        name: 'description'
                    }]
                }]
            }]
        });
    },
    
    /**
     * update template
     * 
     * @param {Tine.Tinebase.data.Record} record
     * @param {Mixed} body
     */
    updateDetails: function(record, body) {
        this.eventDetailsPanel.loadRecord(record);
        //body.update(record.get('summary'));
        //this.tpl.overwrite(body, record.data);
    },
    
    /**
     * show default template
     * 
     * @param {Mixed} body
     */
    showDefault: function(body) {
        //if (this.defaultTpl) {
        //    this.defaultTpl.overwrite(body);
        //}
    },
    
    /**
     * show template for multiple rows
     * 
     * @param {Ext.grid.RowSelectionModel} sm
     * @param {Mixed} body
     */
    showMulti: function(sm, body) {
        //if (this.multiTpl) {
        //    this.multiTpl.overwrite(body);
        //}
    }
});

