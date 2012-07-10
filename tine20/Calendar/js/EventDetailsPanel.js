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
    
    /**
     * renders attendee names
     * 
     * @param {Array} attendeeData
     * @return {String}
     */
    attendeeRenderer: function(attendeeData) {
        if (! attendeeData) {
            return _('No Information');
        }
        var attendeeStore = Tine.Calendar.Model.Attender.getAttendeeStore(attendeeData);
        
        var a = [];
        attendeeStore.each(function(attender) {
            var name = Tine.Calendar.AttendeeGridPanel.prototype.renderAttenderName.call(Tine.Calendar.AttendeeGridPanel.prototype, attender.get('user_id'), false, attender),
                status = Tine.Tinebase.widgets.keyfield.Renderer.render('Calendar', 'attendeeStatus', attender.get('status')),
                role = Tine.Tinebase.widgets.keyfield.Renderer.render('Calendar', 'attendeeRoles', attender.get('role'));
            a.push(name + ' (' + role + ', ' + status + ')');
        });
        
        return a.join("\n");
    },
    
    /**
     * renders container name + color
     * 
     * @param {Array} container
     * @return {String} html
     */
    containerRenderer: function(container) {
        if(this.record) {   // no record after delete
            var renderer = Tine.widgets.grid.RendererManager.get('Calendar', 'Event', 'container_id');
            return renderer.call(this, container, {}, this.record);
        } else {
            return '';
        }
    },
    
    /**
     * renders datetime
     * 
     * @param {Date} dt
     * @return {String}
     */
    datetimeRenderer: function(dt) {
        return String.format(this.app.i18n._("{0} {1} o'clock"), Tine.Tinebase.common.dateRenderer(dt), dt.format('H:i'));
    },
    
    transpRenderer: function(transp) {
        return Tine.Tinebase.common.booleanRenderer(transp == 'OPAQUE');
    },
    
    statusRenderer: function(transp) {
        return Tine.Tinebase.common.booleanRenderer(transp == 'TENTATIVE');
    },
    
    summaryRenderer: function(summary) {
        var myAttenderRecord = this.record.getMyAttenderRecord(),
            ret = Tine.Tinebase.common.tagsRenderer(this.record.get('tags')),
            status = null,
            recur = null;
            
        ret += Ext.util.Format.htmlEncode(summary);
        
        if(myAttenderRecord) {
            status = Tine.Tinebase.widgets.keyfield.Renderer.render('Calendar', 'attendeeStatus', myAttenderRecord.get('status'));
        }
           
        if(this.record.isRecurBase() || this.record.isRecurInstance()) {
            recur = '<img class="cal-recurring" unselectable="on" src="' + Ext.BLANK_IMAGE_URL + '">' + this.app.i18n._('recurring event');
        } else if (this.record.isRecurException()) {
            recur = '<img class="cal-recurring exception" unselectable="on" src="' + Ext.BLANK_IMAGE_URL + '">' + this.app.i18n._('recurring event exception');
        }   

        if(status || recur) {
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
        
        /*
        this.defaultPanel = this.getDefaultPanel();
        this.eventDetailsPanel = this.getEventDetailsPanel();
        
        this.cardPanel = new Ext.Panel({
            layout: 'card',
            border: false,
            activeItem: 0,
            items: [
                this.defaultPanel,
                this.eventDetailsPanel
            ]
        });
        
        this.items = [
            this.cardPanel
        ];
        */
        
        // TODO generalize this
        this.containerTpl = new Ext.XTemplate(
            '<div class="x-tree-node-leaf x-unselectable file">',
                '<img class="x-tree-node-icon" unselectable="on" src="', Ext.BLANK_IMAGE_URL, '">',
                '<span style="color: {color};">&nbsp;&#9673;&nbsp</span>',
                '<span>{name}</span>',
            '</div>'
        ).compile();
        
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
        if (! this.singleRecordPanel) {
            this.singleRecordPanel = new Ext.ux.display.DisplayPanel ({
                layout: 'fit',
                border: false,
                items: [{
                    layout: 'vbox',
                    border: false,
                    layoutConfig: {
                        align:'stretch'
                    },
                    items: [{
                        layout: 'hbox',
                        flex: 0,
                        height: 16,
                        border: false,
                        style: 'padding-left: 5px; padding-right: 5px',
                        layoutConfig: {
                            align:'stretch'
                        },
                        items: [{
                            flex: 0.5,
                            xtype: 'ux.displayfield',
                            name: 'summary',
                            style: 'padding-top: 2px',
                            cls: 'x-ux-display-header',
                            htmlEncode: false,
                            renderer: this.summaryRenderer.createDelegate(this)
                        }, {
                            flex: 0.5,
                            xtype: 'ux.displayfield',
                            style: 'text-align: right;',
                            cls: 'x-ux-display-header',
                            name: 'container_id',
                            htmlEncode: false,
                            renderer: this.containerRenderer.createDelegate(this)
                        }]
                    }, {
                        layout: 'hbox',
                        flex: 1,
                        border: false,
                        layoutConfig: {
                            padding:'5',
                            align:'stretch'
                        },
                        defaults:{
                            margins:'0 5 0 0'
                        },
                        items: [{
                            flex: 2,
                            layout: 'ux.display',
                            labelWidth: 60,
                            layoutConfig: {
                                background: 'solid'
                            },
                            items: [{
                                // TODO try to increase padding/margin of first element
                                /*
                                style: 'margin-top: 4px',
                                labelStyle: 'margin-top: 4px',
                                */
                                xtype: 'ux.displayfield',
                                name: 'dtstart',
                                fieldLabel: this.app.i18n._('Start Time'),
                                renderer: this.datetimeRenderer.createDelegate(this)
                            }, {
                                xtype: 'ux.displayfield',
                                name: 'dtend',
                                fieldLabel: this.app.i18n._('End Time'),
                                renderer: this.datetimeRenderer.createDelegate(this)
                            }, {
                                xtype: 'ux.displayfield',
                                name: 'transp',
                                fieldLabel: this.app.i18n._('Blocking'),
                                renderer: this.transpRenderer.createDelegate(this)
                            }, {
                                xtype: 'ux.displayfield',
                                name: 'status',
                                fieldLabel: this.app.i18n._('Tentative'),
                                renderer: this.statusRenderer.createDelegate(this)
                            }, {
                                xtype: 'ux.displayfield',
                                name: 'location',
                                fieldLabel: this.app.i18n._('Location')
                            }, {
                                xtype: 'ux.displayfield',
                                name: 'organizer',
                                fieldLabel: this.app.i18n._('Organizer'),
                                renderer: function(organizer) {
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
                                renderer: this.attendeeRenderer
                            }]
                        }, {
                            flex: 3,
                            layout: 'fit',
                            
                            border: false,
                            items: [{
                                cls: 'x-ux-display-background-border',
                                xtype: 'ux.displaytextarea',
                                name: 'description'
                            }]
                        }]
                    }]
                }]
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
