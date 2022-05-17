/* 
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 */
 
Ext.ns('Tine.Calendar');

/**
 * @class Tine.Calendar.EventDetailsPanel
 * @namespace Tine.Calendar
 * @extends Tine.widgets.grid.DetailsPanel
 * @author Cornelius Weiss <c.weiss@metaways.de>
 */
Tine.Calendar.EventDetailsPanel = Ext.extend(Tine.widgets.grid.DetailsPanel, {
    border: false,
    defaultHeight: 135,
    
    /**
     * renders attendee names
     * 
     * @param {Array} attendeeData
     * @return {String}
     */
    attendeeRenderer: function(attendeeData) {
        if (! attendeeData) {
            return i18n._('No Information');
        }
        var attendeeStore = Tine.Calendar.Model.Attender.getAttendeeStore(attendeeData);
        
        var a = [];
        attendeeStore.each(function(attender) {
            var name = Tine.Calendar.AttendeeGridPanel.prototype.renderAttenderName.call(Tine.Calendar.AttendeeGridPanel.prototype, attender.get('user_id'), false, attender),
                status = Tine.Tinebase.widgets.keyfield.Renderer.render('Calendar', 'attendeeStatus', attender.get('status')),
                role = Tine.Tinebase.widgets.keyfield.Renderer.render('Calendar', 'attendeeRoles', attender.get('role'));
            a.push(name + ' (' + role + ', ' + status + ')');
        });
        
        return a.join("<br />");
    },
    
    /**
     * renders datetime
     * 
     * @param {Date} dt
     * @return {String}
     */
    datetimeRenderer: function(dt) {
        return Tine.Calendar.Model.Event.datetimeRenderer(dt);
    },
    
    transpRenderer: function(transp) {
        return Tine.Tinebase.common.booleanRenderer(transp == 'OPAQUE');
    },
    
    statusRenderer: function(transp) {
        return Tine.Tinebase.common.booleanRenderer(transp == 'TENTATIVE');
    },
    
    summaryRenderer: function(summary) {
        if (! this.record) {
            // no record, no summary
            return '';
        }
        
        var myAttenderRecord = this.record.getMyAttenderRecord(),
            ret = Tine.Tinebase.common.tagsRenderer(this.record.get('tags')),
            status = null,
            recur = null;
        
        ret += Ext.util.Format.htmlEncode(this.record.getTitle());
        
        if (myAttenderRecord) {
            status = Tine.Tinebase.widgets.keyfield.Renderer.render('Calendar', 'attendeeStatus', myAttenderRecord.get('status'));
        }
        
        if (this.record.isRecurBase() || this.record.isRecurInstance()) {
            recur = '<img class="cal-recurring" unselectable="on" src="' + Ext.BLANK_IMAGE_URL + '">' + this.app.i18n._('recurring event');
        } else if (this.record.isRecurException()) {
            recur = '<img class="cal-recurring exception" unselectable="on" src="' + Ext.BLANK_IMAGE_URL + '">' + this.app.i18n._('recurring event exception');
        }
        
        if (status || recur) {
            ret += '&nbsp;&nbsp;&nbsp;(&nbsp;';
            if(status) ret += status;
            if(status && recur) ret += '&nbsp;&nbsp;';
            if(recur) ret += recur;
            ret += '&nbsp;)';
        }
        
        return ret;
    },
    
    /**
     * inits this component
     */
    initComponent: function() {
        this.app = Tine.Tinebase.appMgr.get('Calendar');

        Tine.Calendar.EventDetailsPanel.superclass.initComponent.call(this);
    },
    
    /**
     * default panel w.o. data
     * 
     * @return {Ext.ux.display.DisplayPanel}
     */
    getDefaultInfosPanel: function() {
        if (! this.defaultInfosPanel) {
            this.defaultInfosPanel = new Ext.ux.display.DisplayPanel ({
                layout: 'fit',
                border: false,
                items: [{
                    layout: 'hbox',
                    border: false,
                    defaults:{margins:'0 5 0 0'},
                    layoutConfig: {
                        padding:'5',
                        align:'stretch'
                    },
                    items: [{
                        flex: 1,
                        border: false,
                        layout: 'ux.display',
                        layoutConfig: {
                            background: 'solid',
                            declaration: this.app.i18n.n_('Event', 'Events', 50)
                        }
                    }, {
                        flex: 1,
                        border: false,
                        layout: 'ux.display',
                        layoutConfig: {
                            background: 'border'
                        }
                    }]
                }]
            });
        }
        
        return this.defaultInfosPanel;
    },
    
    /**
     * main event details panel
     * 
     * @return {Ext.ux.display.DisplayPanel}
     */
    getSingleRecordPanel: function() {
        var me = this;
        if (! this.singleRecordPanel) {
            this.singleRecordPanel = new Tine.widgets.display.RecordDisplayPanel({
                recordClass: Tine.Calendar.Model.Event,
                titleRenderer: this.summaryRenderer.createDelegate(this),
                getBodyItems: function() {
                    return [{
                        layout: 'hbox',
                        flex: 1,
                        border: false,
                        layoutConfig: {
                            padding: '0',
                            align: 'stretch'
                        },
                        defaults: {
                            margins: '0 5 0 0'
                        },
                        items: [{
                            flex: 2,
                            layout: 'ux.display',
                            labelWidth: 60,
                            autoScroll: true,
                            layoutConfig: {
                                background: 'solid'
                            },
                            items: [{
                                xtype: 'ux.displayfield',
                                name: 'dtstart',
                                fieldLabel: this.app.i18n._('Start Time'),
                                renderer: me.datetimeRenderer.createDelegate(me)
                            }, {
                                xtype: 'ux.displayfield',
                                name: 'dtend',
                                fieldLabel: this.app.i18n._('End Time'),
                                renderer: me.datetimeRenderer.createDelegate(me)
                            }, {
                                xtype: 'ux.displayfield',
                                name: 'transp',
                                fieldLabel: this.app.i18n._('Blocking'),
                                renderer: me.transpRenderer.createDelegate(me)
                            }, {
                                xtype: 'ux.displayfield',
                                name: 'status',
                                fieldLabel: this.app.i18n._('Tentative'),
                                renderer: me.statusRenderer.createDelegate(me)
                            }, {
                                xtype: 'ux.displayfield',
                                name: 'location',
                                linkify: true,
                                fieldLabel: this.app.i18n._('Event Location')
                            }, {
                                xtype: 'ux.displayfield',
                                name: 'organizer',
                                fieldLabel: this.app.i18n._('Organizer'),
                                renderer: function (organizer) {
                                    return organizer && organizer.n_fileas ? organizer.n_fileas : '';
                                }
                            }]
                        }, {
                            flex: 3,
                            layout: 'ux.display',
                            labelAlign: 'top',
                            autoScroll: true,
                            layoutConfig: {
                                background: 'solid'
                            },
                            items: [{
                                xtype: 'ux.displayfield',
                                name: 'attendee',
                                nl2br: true,
                                htmlEncode: false,
                                fieldLabel: this.app.i18n._('Attendee'),
                                renderer: me.attendeeRenderer
                            }]
                        }, {
                            flex: 3,
                            layout: 'ux.display',
                            hideLabels: true,
                            border: false,
                            autoScroll: true,
                            items: [{
                                xtype: 'ux.displayfield',
                                name: 'url',
                                ctCls: 'x-ux-dislplay-no-label',
                                itemCls: 'x-ux-dislplay-no-label',
                                fieldLabel: this.app.i18n._('URL'),
                                linkify: true
                            },{
                                cls: 'x-ux-display-background-border',
                                xtype: 'ux.displaytextarea',
                                name: 'description'
                            }]
                        }],
                    }];
                }
            });
        }
        
        return this.singleRecordPanel;
    },
    
    /**
     * update event details panel
     * 
     * @param {Tine.Tinebase.data.Record} record
     * @param {Mixed} body
     */
    updateDetails: function(record, body) {
        //this.cardPanel.layout.setActiveItem(this.cardPanel.items.getKey(this.eventDetailsPanel));
        
        this.getSingleRecordPanel().loadRecord.defer(100, this.getSingleRecordPanel(), [record]);

        //return this.supr().updateDetails.apply(this, arguments);
    }
    
//    /**
//     * show default panel
//     * 
//     * @param {Mixed} body
//     */
//    showDefault: function(body) {
//        this.cardPanel.layout.setActiveItem(this.cardPanel.items.getKey(this.defaultPanel));
//    },
//    
//    /**
//     * show template for multiple rows
//     * 
//     * @param {Ext.grid.RowSelectionModel} sm
//     * @param {Mixed} body
//     */
//    showMulti: function(sm, body) {
//        //if (this.multiTpl) {
//        //    this.multiTpl.overwrite(body);
//        //}
//    }
});
