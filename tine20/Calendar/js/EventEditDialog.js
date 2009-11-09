/*
 * Tine 2.0
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
 
Ext.ns('Tine.Calendar');

/**
 * @namespace Tine.Calendar
 * @class Tine.Calendar.EventEditDialog
 * @extends Tine.widgets.dialog.EditDialog
 * Calendar Edit Dialog <br>
 * 
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 */
Tine.Calendar.EventEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {
    /**
     * @cfg {Number} containerId initial container id
     */
    containerId: -1,
    
    
    labelAlign: 'side',
    windowNamePrefix: 'EventEditWindow_',
    appName: 'Calendar',
    recordClass: Tine.Calendar.Model.Event,
    recordProxy: Tine.Calendar.backend,
    showContainerSelector: false,
    tbarItems: [{xtype: 'widget-activitiesaddbutton'}],
    
    mode: 'local',
    
    // note: we need up use new action updater here or generally in the widget!
    evalGrants: false,
    
    afterRender: function() {
        Tine.Calendar.EventEditDialog.superclass.afterRender.apply(this, arguments);
        this.CalendarSelectWidget.render(this.footer.first().insertFirst({tag: 'div', style: {'position': 'relative', 'top': '4px', 'float': 'left'}}));
    },
    
    onResize: function() {
        Tine.Calendar.EventEditDialog.superclass.onResize.apply(this, arguments);
        this.setTabHeight.defer(100, this);
    },
    
    
    /**
     * returns dialog
     * 
     * NOTE: when this method gets called, all initalisation is done.
     * @return {Object} components this.itmes definition
     */
    getFormItems: function() { 
        return {
            xtype: 'tabpanel',
            border: false,
            plain:true,
            activeTab: 0,
            border: false,
            items:[{
                title: this.app.i18n.n_('Event', 'Events', 1),
                border: false,
                frame: true,
                layout: 'border',
                items: [{
                    region: 'center',
                    layout: 'hfit',
                    border: false,
                    items: [{
                        xtype: 'fieldset',
                        layout: 'hfit',
                        autoHeight:true,
                        title: this.app.i18n.n_('Event', 'Events', 1),
                        items: [{
                            xtype: 'columnform',
                            labelAlign: 'side',
                            labelWidth: 100,
                            formDefaults: {
                                xtype:'textfield',
                                anchor: '100%',
                                labelSeparator: '',
                                columnWidth: .6
                            },
                            items: [[{
                                columnWidth: 1,
                                fieldLabel: this.app.i18n._('Summary'),
                                name: 'summary',
                                listeners: {render: function(field){field.focus(false, 250);}},
                                allowBlank: false,
                                requiredGrant: 'editGrant'
                            }], [{
                                columnWidth: 1,
                                fieldLabel: this.app.i18n._('Location'),
                                name: 'location',
                                requiredGrant: 'editGrant'
                            }], [{
                                xtype: 'datetimefield',
                                fieldLabel: this.app.i18n._('Start Time'),
                                listeners: {scope: this, change: this.onDtStartChange},
                                name: 'dtstart',
                                requiredGrant: 'editGrant'
                            }, {
                                columnWidth: .4,
                                xtype: 'combo',
                                hideLabel: true,
                                readOnly: true,
                                hideTrigger: true,
                                disabled: true,
                                name: 'originator_tz',
                                requiredGrant: 'editGrant'
                            }], [{
                                xtype: 'datetimefield',
                                fieldLabel: this.app.i18n._('End Time'),
                                listeners: {scope: this, change: this.onDtEndChange},
                                name: 'dtend',
                                requiredGrant: 'editGrant'
                            }, {
                                columnWidth: .4,
                                xtype: 'checkbox',
                                hideLabel: true,
                                boxLabel: this.app.i18n._('whole day'),
                                listeners: {scope: this, check: this.onAllDayChange},
                                name: 'is_all_day_event',
                                requiredGrant: 'editGrant'
                            }]]
                        }]
                    }, {
                        xtype: 'tabpanel',
                        deferredRender: false,
                        activeTab: 0,
                        border: false,
                        height: 235,
                        form: true,
                        items: [
                            this.attendeeGridPanel,
                            this.rrulePanel,
                            this.alarmPanel
                        ]
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
                                name: 'description',
                                hideLabel: true,
                                grow: false,
                                preventScrollbars:false,
                                anchor:'100% 100%',
                                emptyText: this.app.i18n._('Enter description'),
                                requiredGrant: 'editGrant'                           
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
    
    initComponent: function() {
        this.attendeeGridPanel = new Tine.Calendar.AttendeeGridPanel({});
        this.rrulePanel = new Tine.Calendar.RrulePanel({});
        this.alarmPanel = new Tine.widgets.dialog.AlarmPanel({});         
        this.attendeeStore = this.attendeeGridPanel.getStore();
        
        this.CalendarSelectWidget = new Tine.Calendar.CalendarSelectWidget(this);
        
        Tine.Calendar.EventEditDialog.superclass.initComponent.call(this);
    },
    
    /**
     * checks if form data is valid
     * 
     * @return {Boolean}
     */
    isValid: function() {
        var isValid = this.validateDtStart() && this.validateDtEnd();
        
        return isValid && Tine.Calendar.EventEditDialog.superclass.isValid.apply(this, arguments);
    },
    
    onAllDayChange: function(checkbox, isChecked) {
        var dtStartField = this.getForm().findField('dtstart');
        var dtEndField = this.getForm().findField('dtend');
        dtStartField.setDisabled(isChecked, 'time');
        dtEndField.setDisabled(isChecked, 'time');
        
        if (isChecked) {
            dtStartField.clearTime();
            var dtend = dtEndField.getValue()
            if (Ext.isDate(dtend) && dtend.format('H:i:s') != '23:59:59') {
                dtEndField.setValue(dtend.clearTime(true).add(Date.HOUR, 24).add(Date.SECOND, -1));
            }
            
        } else {
            dtStartField.undo();
            dtEndField.undo();
        }
    },
    
    /*
    onApplyChanges: function(button, event, closeWindow) {
        if(this.isValid()) {
            //this.loadMask.show();
            this.onRecordUpdate();
            
            this.fireEvent('update', Ext.util.JSON.encode(this.record.data));
        }
    },
    */
    
    onDtEndChange: function(dtEndField, newValue, oldValue) {
        this.validateDtEnd();
    },
    
    onDtStartChange: function(dtStartField, newValue, oldValue) {
        if (Ext.isDate(newValue) && Ext.isDate(oldValue)) {
            var diff = newValue.getTime() - oldValue.getTime();
            var dtEndField = this.getForm().findField('dtend');
            var dtEnd = dtEndField.getValue();
            if (Ext.isDate(dtEnd)) {
                dtEndField.setValue(dtEnd.add(Date.MILLI, diff));
            }
        }
    },
    
    onRecordLoad: function() {
        // NOTE: it comes again and again till 
        if (this.rendered) {
            this.attendeeGridPanel.onRecordLoad(this.record);
            this.rrulePanel.onRecordLoad(this.record);
            this.alarmPanel.onRecordLoad(this.record);
            this.CalendarSelectWidget.onRecordLoad(this.record);
            
            // apply grants
            if (! this.record.get('editGrant')) {
                this.getForm().items.each(function(f){
                    if(f.isFormField && f.requiredGrant !== undefined){
                        f.setDisabled(! this.record.get(f.requiredGrant));
                    }
                }, this);
            }
        }
        
        Tine.Calendar.EventEditDialog.superclass.onRecordLoad.apply(this, arguments);
    },
    
    onRecordUpdate: function() {
        Tine.Calendar.EventEditDialog.superclass.onRecordUpdate.apply(this, arguments);
        this.attendeeGridPanel.onRecordUpdate(this.record);
        this.rrulePanel.onRecordUpdate(this.record);
        this.alarmPanel.onRecordUpdate(this.record);
        this.CalendarSelectWidget.onRecordUpdate(this.record);
    },
    
    setTabHeight: function() {
        var eventTab = this.items.first().items.first();
        var centerPanel = eventTab.items.first();
        var tabPanel = centerPanel.items.last();
        tabPanel.setHeight(centerPanel.getEl().getBottom() - tabPanel.getEl().getTop());
    },
    
    validateDtEnd: function() {
        var dtStart = this.getForm().findField('dtstart').getValue();
        
        var dtEndField = this.getForm().findField('dtend');
        var dtEnd = dtEndField.getValue();
        
        if (! Ext.isDate(dtEnd)) {
            dtEndField.markInvalid(this.app.i18n._('End date is not valid'));
            return false;
        } else if (Ext.isDate(dtStart) && dtEnd.getTime() - dtStart.getTime() <= 0) {
            dtEndField.markInvalid(this.app.i18n._('End date must be after start date'));
            return false;
        } else {
            dtEndField.clearInvalid();
            return true;
        }
    },
    
    validateDtStart: function() {
        var dtStartField = this.getForm().findField('dtstart');
        var dtStart = dtStartField.getValue();
        
        if (! Ext.isDate(dtStart)) {
            dtStartField.markInvalid(this.app.i18n._('Start date is not valid'));
            return false;
        } else {
            dtStartField.clearInvalid();
            return true;
        }
        
    }
});

/**
 * Opens a new event edit dialog window
 * 
 * @return {Ext.ux.Window}
 */
Tine.Calendar.EventEditDialog.openWindow = function (config) {
    // record is JSON encoded here...
    var id = config.recordId ? config.recordId : 0;
    var window = Tine.WindowFactory.getWindow({
        width: 800,
        height: 470,
        name: Tine.Calendar.EventEditDialog.prototype.windowNamePrefix + id,
        contentPanelConstructor: 'Tine.Calendar.EventEditDialog',
        contentPanelConstructorConfig: config
    });
    return window;
};
