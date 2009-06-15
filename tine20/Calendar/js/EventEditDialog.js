/**
 * Tine 2.0
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
 
Ext.namespace('Tine.Calendar');

/**
 * Calendar Edit Dialog
 */
Tine.Calendar.EventEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {
    /**
     * @cfg {Number}
     */
    containerId: -1,
    /**
     * @private
     */
    labelAlign: 'side',
    
    /**
     * @private
     */
    windowNamePrefix: 'EventEditWindow_',
    appName: 'Calendar',
    recordClass: Tine.Calendar.Event,
    recordProxy: Tine.Calendar.backend,
    showContainerSelector: true,
    tbarItems: [{xtype: 'widget-activitiesaddbutton'}],
    
    mode: 'local',
    
    // note: we need up use new action updater here or generally in the widget!
    evalGrants: false,
    
    afterRender: function() {
        Tine.Calendar.EventEditDialog.superclass.afterRender.apply(this, arguments);
        //this.setTabHeight();
    },
    
    /**
     * returns dialog
     * 
     * NOTE: when this method gets called, all initalisation is done.
     */
    getFormItems: function() { 
        return {
            xtype: 'tabpanel',
            border: false,
            plain:true,
            activeTab: 0,
            border: false,
            items:[{
                title: this.app.i18n.n_('Event', 'Calendar', 1),
                border: false,
                frame: true,
                layout: 'border',
                items: [{
                    region: 'center',
                    autoScroll: true,
                    layout: 'hfit',
                    border: false,
                    items: [{
                        xtype: 'fieldset',
                        layout: 'hfit',
                        autoHeight:true,
                        title: this.app.i18n._('Event'),
                        items: [{
                            xtype: 'columnform',
                            labelAlign: 'side',
                            labelWidth: 100,
                            formDefaults: {
                                xtype:'textfield',
                                anchor: '100%',
                                labelSeparator: '',
                                columnWidth: .5
                            },
                            items: [[{
                                columnWidth: 1,
                                fieldLabel: this.app.i18n._('Summary'),
                                name: 'summary',
                                listeners: {render: function(field){field.focus(false, 250);}},
                                allowBlank: false
                            }], [{
                                columnWidth: 1,
                                fieldLabel: this.app.i18n._('Location'),
                                name: 'location'
                            }], [{
                                xtype: 'datetimefield',
                                fieldLabel: this.app.i18n._('Start Time'),
                                name: 'dtstart'
                            }, {
                                xtype: 'combo',
                                hideLabel: true,
                                readOnly: true,
                                hideTrigger: true,
                                disabled: true,
                                name: 'originator_tz'
                            }], [{
                                xtype: 'datetimefield',
                                fieldLabel: this.app.i18n._('End Time'),
                                name: 'dtend'
                            }, {
                                xtype: 'checkbox',
                                hideLabel: true,
                                boxLabel: this.app.i18n._('whole day'),
                                name: 'is_all_day_event'
                            }]]
                        }]
                    }, {
                        xtype: 'tabpanel',
                        activeTab: 0,
                        border: true,
                        height: 235,
                        form: true,
                        listeners: {
                            scope: this,
                            render: function(p) {this.innerTabPanel = p;},
                            resize: function(p) {
                                //console.log(p.container.getHeight());
                                //console.log(p.getEl().getTop());
                                //console.log(this.getItemAt(0));
                            }
                        },
                        items: [{
                            title: this.app.i18n._('Attendee'),
                            html: 'some attendee'
                        }]
                    }]
                }, {
                    // activities and tags
                    region: 'east',
                    layout: 'accordion',
                    animate: true,
                    width: 210,
                    split: true,
                    collapsible: true,
                    collapseMode: 'mini',
                    margins: '0 5 0 5',
                    border: true,
                    items: [
                        new Ext.Panel({
                            // @todo generalise!
                            title: this.app.i18n._('Description'),
                            iconCls: 'descriptionIcon',
                            layout: 'form',
                            labelAlign: 'top',
                            border: false,
                            items: [{
                                style: 'margin-top: -4px; border 0px;',
                                labelSeparator: '',
                                xtype:'textarea',
                                name: 'note',
                                hideLabel: true,
                                grow: false,
                                preventScrollbars:false,
                                anchor:'100% 100%',
                                emptyText: this.app.i18n._('Enter description')                            
                            }]
                        }),
                        new Tine.widgets.activities.ActivitiesPanel({
                            app: 'Calendar',
                            showAddNoteForm: false,
                            border: false,
                            bodyStyle: 'border:1px solid #B5B8C8;'
                        }),
                        new Tine.widgets.tags.TagPanel({
                            app: 'Calendar',
                            border: false,
                            bodyStyle: 'border:1px solid #B5B8C8;'
                        })
                    ]
                }]
            }, new Tine.widgets.activities.ActivitiesTabPanel({
                app: this.appName,
                record_id: (this.record) ? this.record.id : '',
                record_model: this.appName + '_Model_' + this.recordClass.getMeta('modelName')
            })]
        };
    },
    
    setTabHeight: function() {
        var summaryField = this.getForm().findField('summary');
        console.log(summaryField);
        
    }
});

/**
 * Event Edit Window
 */
Tine.Calendar.EventEditDialog.openWindow = function (config) {
    var id = (config.record && config.record.id) ? config.record.id : 0;
    var window = Tine.WindowFactory.getWindow({
        width: 800,
        height: 470,
        name: Tine.Calendar.EventEditDialog.prototype.windowNamePrefix + id,
        contentPanelConstructor: 'Tine.Calendar.EventEditDialog',
        contentPanelConstructorConfig: config
    });
    return window;
};