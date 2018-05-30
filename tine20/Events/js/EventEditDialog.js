/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Stefanie Stamer <s.stamer@metaways.de>
 * @copyright   Copyright (c) 2007-2015 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.Events');

/**
 * @namespace   Tine.Events
 * @class       Tine.Events.EventEditDialog
 * @extends     Tine.widgets.dialog.EditDialog
 * 
 * <p>Event Compose Dialog</p>
 * <p></p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Stefanie Stamer <s.stamer@metaways.de>
 * @copyright   Copyright (c) 2007-2015 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Events.EventEditDialog
 */
Tine.Events.EventEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {

    /**
     * @cfg {Boolean} checkBusyConflicts
     * do busy conflict check when saving record
     */
    checkBusyConflicts: true,

    /**
     * @private
     */
    windowHeight: 600,
    windowWidth: 800,
    windowNamePrefix: 'EventEventEditWindow_',
    displayNotes: true,

    initComponent: function() {
        this.addEvents(
            /**
             * @event dtStartChange
             * @desc  Fired when dtstart changes in UI
             * @param {Json String} oldValue, newValue
             */
            'dtStartChange'
        );

        this.calendarEventGridPanel = new Tine.Events.CalendarEventGridPanel({
            app: this.app
        });

        Tine.Events.EventEditDialog.superclass.initComponent.call(this);

        this.on('load', this.loadRecord, this);
    },

    onAfterRecordLoad: function () {
        Tine.Events.EventEditDialog.superclass.onAfterRecordLoad.call(this);

        this.showHideOptionalDate();
        this.onContactLoad();
        this.onDepartmentLoad();
    },

    onDtEndChange: function(dtEndField, newValue, oldValue) {
        this.validateDtEnd();
    },

    loadRecord: function() {
        this.calendarEventGridPanel.fillEventsStore(this.record);
        if (this.record.id === 0) {
            var department = Tine.Tinebase.registry.get('userDepartment');
            // set current user as contact
            this.record.set('contact', Tine.Tinebase.registry.get('userContact'));
            if (department) {
                this.record.set('department', department);
                this.record.set('location', (department.customfields && department.customfields.location) ? department.customfields.location : '');
            }
        }
    },

    onRecordUpdate: function() {
        Tine.Events.EventEditDialog.superclass.onRecordUpdate.call(this);

        var relations = this.calendarEventGridPanel.updateRelationsFromGrid(this.record.get('relations'));
        this.record.set('relations', relations);
        
        // TODO update dtstart/dtend from main event
    },

    /**
     * returns additional save params
     *
     * @returns {{checkBusyConflicts: boolean}}
     */
    getAdditionalSaveParams: function() {
        return {
            checkBusyConflicts: this.checkBusyConflicts
        };
    },

    /**
     * generic request exception handler
     *
     * @param {Object} exception
     */
    onRequestFailed: function(exception) {
        this.saving = false;

        if (exception.code == 901) {
            this.onBusyException.apply(this, arguments);
        } else {
            Tine.Events.EventEditDialog.superclass.onRequestFailed.call(this, exception);
        }
        this.loadMask.hide();
    },

    onBusyException: function() {
        Ext.Msg.confirm(this.app.i18n._('Ignore Scheduling Conflict'),
            this.app.i18n._('There is a resource/attendee conflict. You can ignore the conflict and save the record by pressing "yes".'),
            function(btn) {
                if (btn == 'yes') {
                    this.checkBusyConflicts = false;
                    this.onApplyChanges(true);
                }
            }, this);
    },

    /**
     * returns dialog
     * 
     * NOTE: when this method gets called, all initialisation is done.
     * 
     * @return {Object}
     * @private
     */
    getFormItems: function() {
        return {
            xtype: 'tabpanel',
            border: false,
            plain: true,
            activeTab: 0,
            defaults: {
                hideMode: 'offsets'
            },
            items: [{
                //Start first tab
                title: this.app.i18n._('General'),
                border: false,
                frame: true,
                layout: 'border',
                items: [{
                    region: 'center',
                    layout: 'hfit',
                    border: false,
                    items: [{
                        region: 'center',
                        xtype: 'columnform',
                        labelAlign: 'top',
                        formDefaults: {
                            xtype:'textfield',
                            anchor: '100%',
                            labelSeparator: '',
                            columnWidth: .333,
                            disabled: (this.useMultiple) ? true : false
                        },
                        // Start first line
                        items: [
                            [{
                                columnWidth: 1,
                                xtype: 'textfield',
                                fieldLabel: this.app.i18n._('Title'),
                                name: 'title',
                                maxLength: 100,
                                allowBlank: false,
                                listeners: {
                                    change: this.updateMainEvent,
                                    scope: this
                                }
                            }],
                            [new Tine.Tinebase.widgets.keyfield.ComboBox({
                                app: 'Events',
                                keyFieldName: 'actionType',
                                columnWidth: 1,
                                fieldLabel: this.app.i18n._('Action'),
                                name: 'action'
                            })],
                            [new Tine.Tinebase.widgets.keyfield.ComboBox({
                                app: 'Events',
                                keyFieldName: 'projectType',
                                columnWidth: 1,
                                fieldLabel: this.app.i18n._('Project type'),
                                name: 'project_type'
                            })]
                            ,
                            [Tine.widgets.form.RecordPickerManager.get('Addressbook', 'List', {
                                columnWidth: 0.5,
                                fieldLabel: this.app.i18n._('Department'),
                                name: 'department',
                                maxLength: 100,
                                departmentOnly: true,
                                listeners:{
                                    scope: this,
                                    'select': this.onDepartmentLoad
                                }
                            }),
                            {
                                columnWidth: 0.5,
                                xtype: 'textfield',
                                fieldLabel: this.app.i18n._('Location'),
                                name: 'location',
                                maxLength: 100,
                                disabled: true
                            }],
                            [
                            Tine.widgets.form.RecordPickerManager.get('Addressbook', 'Contact', {
                                columnWidth: 0.5,
                                userOnly: true,
                                fieldLabel: this.app.i18n._('Contact'),
                                emptyText: _('Add Contact ...'),
                                name: 'contact',
                                allowEmpty: true,
                                listeners:{
                                    scope: this,
                                    'select': this.onContactLoad
                                }
                            }),
                            {
                                columnWidth: 0.5,
                                xtype: 'textfield',
                                fieldLabel: this.app.i18n._('Phone'),
                                name: 'phone'
                            }],
                            [{
                                columnWidth: 1,
                                xtype: 'textfield',
                                fieldLabel: this.app.i18n._('Subregion'),
                                name: 'subregion',
                                maxLength: 100
                            }],
                            [{
                                columnWidth: 1,
                                xtype: 'textfield',
                                fieldLabel: this.app.i18n._('Organizer'),
                                name: 'organizer',
                                maxLength: 100
                            }],
                            [new Tine.Tinebase.widgets.keyfield.ComboBox({
                                app: 'Events',
                                keyFieldName: 'targetGroups',
                                columnWidth: 0.5,
                                fieldLabel: this.app.i18n._('Target group'),
                                name: 'target_group'
                            }), {
                                columnWidth: 0.5,
                                xtype: 'numberfield',
                                fieldLabel: this.app.i18n._('Guests'),
                                name: 'guests',
                                value: 0,
                                allowNegative: false,
                                allowDecimals: false
                            }],
                            [
                                new Tine.Tinebase.widgets.keyfield.ComboBox({
                                app: 'Events',
                                keyFieldName: 'plannedStatus',
                                columnWidth: 0.5,
                                fieldLabel: this.app.i18n._('Planned'),
                                name: 'planned',
                                listeners:{
                                    scope: this,
                                    'select': this.showHideOptionalDate
                                }
                            }), {
                                xtype: 'datefield',
                                columnWidth: 0.5,
                                fieldLabel: this.app.i18n._('Optional Date'),
                                name: 'optional_date'
                            }],
                            [{
                                xtype: 'datetimefield',
                                columnWidth: 0.5,
                                fieldLabel: this.app.i18n._('Start'),
                                listeners: {scope: this, change: this.onDtStartChange},
                                defaultTime: '12:00',
                                name: 'event_dtstart',
                                listeners: {
                                    change: this.updateMainEvent,
                                    scope: this
                                }
                            }],
                            [{
                                xtype: 'datetimefield',
                                columnWidth: 0.5,
                                fieldLabel: this.app.i18n._('End'),
                                listeners: {scope: this, change: this.onDtEndChange},
                                defaultTime: '13:00',
                                name: 'event_dtend',
                                listeners: {
                                    change: this.updateMainEvent,
                                    scope: this
                                }

                            }]
                        ]
                    }
                    ]
                    
                },
                    {
                        // activities and tags
                        region: 'east',
                        layout: 'ux.multiaccordion',
                        animate: true,
                        width: 210,
                        split: true,
                        collapsible: true,
                        collapseMode: 'mini',
                        header: false,
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
                                    xtype: 'textarea',
                                    name: 'description',
                                    hideLabel: true,
                                    grow: false,
                                    preventScrollbars: false,
                                    anchor: '100% 100%',
                                    emptyText: this.app.i18n._('Enter description'),
                                    requiredGrant: 'editGrant'
                                }]
                            }),
                            new Tine.widgets.tags.TagPanel({
                                app: 'Events',
                                border: false,
                                bodyStyle: 'border:1px solid #B5B8C8;'
                            })
                        ]
                    }]
                }, {
                    title: this.app.i18n._('Calendar Entries'),
                    autoScroll: true,
                    border: false,
                    frame: true,
                    layout: 'fit',
                    items: [
                        this.calendarEventGridPanel
                    ]
                },
                new Tine.widgets.activities.ActivitiesTabPanel({
                    app: this.appName,
                    record_id: this.record.id,
                    record_model: this.appName + '_Model_' + this.recordClass.getMeta('modelName')
                })
            ]
        };
    },

    /**
     * updates / creates main event from form values
     */
    updateMainEvent: function() {
        var dtstart = this.getForm().findField('event_dtstart').getValue(),
            dtend = this.getForm().findField('event_dtend').getValue(),
            summary = this.getForm().findField('title').getValue();
        this.calendarEventGridPanel.updateMainEvent(dtstart, dtend, summary);

        // refresh/re-sort grid to update event/relation types
        //this.calendarEventGridPanel.getStore().sort('dtstart', 'DESC');
        this.calendarEventGridPanel.getStore().fireEvent('datachanged', this.calendarEventGridPanel.getStore());
    },

    /**
     * on dt start change
     * 
     * @param {} dtStartField
     * @param {} newValue
     * @param {} oldValue
     */
    onDtStartChange: function(dtStartField, newValue, oldValue) {
        if (this.validateDtStart() == false) {
            return false;
        }
        
        if (Ext.isDate(newValue) && Ext.isDate(oldValue)) {
            var dtEndField = this.getForm().findField('event_dtend'),
                dtEnd = dtEndField.getValue();
                
            if (Ext.isDate(dtEnd)) {
                var duration = dtEnd.getTime() - oldValue.getTime(),
                    newDtEnd = newValue.add(Date.MILLI, duration);
                dtEndField.setValue(newDtEnd);
                this.validateDtEnd();
            }
        }

        this.fireEvent('dtStartChange', Ext.util.JSON.encode({newValue: newValue, oldValue: oldValue}));
    },
    
    validateDtStart: function() {
        var dtStartField = this.getForm().findField('event_dtstart'),
            dtStart = dtStartField.getValue();
        
        if (! Ext.isDate(dtStart)) {
            dtStartField.markInvalid(this.app.i18n._('Start date is not valid'));
            return false;
        } else {
            dtStartField.clearInvalid();
            return true;
        }
    },
    
    validateDtEnd: function() {
        var dtStart = this.getForm().findField('event_dtstart').getValue(),
            dtEndField = this.getForm().findField('event_dtend'),
            dtEnd = dtEndField.getValue();
        
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
    
    showHideOptionalDate: function() {
        var planned = this.getForm().findField('planned').getValue(),
            optionalDate = this.getForm().findField('optional_date');

        if (planned == 'OPTIONAL') {
            optionalDate.show();
        } else {
            optionalDate.hide();
        }
    },

    onContactLoad: function(combo, record) {
        var contact = !Ext.isEmpty(record) ? record.data : this.record.get('contact'),
            phoneField = this.getForm().findField('phone');
            
        if(!Ext.isEmpty(contact) && !Ext.isEmpty(contact.tel_work)) {
            phoneField.setValue(contact.tel_work);
        }
    },
    
    onDepartmentLoad: function(combo, record) {
        var department = !Ext.isEmpty(record) ? record.data : this.record.get('department'),
            locationField = this.getForm().findField('location');
            
        if(!Ext.isEmpty(department) && !Ext.isEmpty(department.customfields) && !Ext.isEmpty(department.customfields.location)) {
            locationField.setValue(department.customfields.location);
        } else {
            locationField.setValue('');
        }
    }
});
