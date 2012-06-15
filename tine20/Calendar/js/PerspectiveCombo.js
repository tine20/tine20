/*
 * Tine 2.0
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.ns('Tine.Calendar');

/**
 * @namespace   Tine.Calendar
 * @class       Tine.Calendar.PerspectiveCombo
 * @extends     Ext.form.ComboBox
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * 
 * @TODO make displaycontainer setting work
 */
Tine.Calendar.PerspectiveCombo = Ext.extend(Ext.form.ComboBox, {
    
    /**
     * @cfg {Tine.Calendar.EventEditDialog}
     */
    editDialog: null,
    
    defaultValue: 'origin',
    
    typeAhead: true,
    triggerAction: 'all',
    lazyRender:true,
    mode: 'local',
    valueField: 'id',
    displayField: 'displayText',
    
    initComponent: function() {
        this.app = Tine.Tinebase.appMgr.get('Calendar');
        
        this.initStore();
        
        this.on('beforeselect', function(field, record, index) {
            var oldValue = this.getValue(),
                newValue = this.store.getAt(index).id;
                
            this.onPerspectiveChange.defer(50, this, [field, newValue, oldValue]);
        } , this);
        
        
        this.editDialog.alarmPanel.alarmGrid.deleteAction.setHandler(this.onAlarmDelete, this);
        this.editDialog.alarmPanel.onRecordUpdate = this.onAlarmRecordUpdate.createDelegate(this);
        this.editDialog.alarmPanel.alarmGrid.store.on('add', this.onAlarmAdd, this);
        
        
        Tine.Calendar.PerspectiveCombo.superclass.initComponent.call(this);
    },
    
    getAttendeeStatusField: function() {
        if (! this.attendeeStatusField) {
            this.attendeeStatusField = Ext.ComponentMgr.create({
                xtype: 'widget-keyfieldcombo',
                width: 115,
                hideLabel: true,
                app:   'Calendar',
                name: 'attendeeStatus',
                keyFieldName: 'attendeeStatus'
            });
            
            this.attendeeStatusField.on('change', function(field) {
                var perspective = this.getValue(),
                    attendeeRecord = this.editDialog.attendeeStore.getById(perspective);
                    
                attendeeRecord.set('status', field.getValue());
            }, this);
        }
        
        return this.attendeeStatusField;
    },
    
    getAttendeeTranspField: function() {
        if (! this.attendeeTranspField) {
            this.attendeeTranspField = Ext.ComponentMgr.create({
                xtype: 'checkbox',
                boxLabel: this.app.i18n._('non-blocking'),
                name: 'attendeeTransp',
                getValue: function() {
                    var bool = Ext.form.Checkbox.prototype.getValue.call(this);
                    return bool ? 'TRANSPARENT' : 'OPAQUE';
                },
                setValue: function(value) {
                    var bool = (value == 'TRANSPARENT' || value === true);
                    return Ext.form.Checkbox.prototype.setValue.call(this, bool);
                }
            });
            
            this.attendeeTranspField.on('change', function(field) {
                var perspective = this.getValue(),
                    attendeeRecord = this.editDialog.attendeeStore.getById(perspective);
                    
                attendeeRecord.set('transp', field.getValue());
            }, this);
        }
        
        return this.attendeeTranspField;
    },
    
    initStore: function() {
        this.store = new Ext.data.Store({
            fields: Tine.Calendar.Model.Attender.getFieldDefinitions().concat([{name: 'displayText'}])
        });
        
        this.originRecord = new Tine.Calendar.Model.Attender({id: 'origin', displayText: this.app.i18n._('Organizer')}, 'origin');
        
        this.editDialog.attendeeStore.on('add', this.syncStores, this);
        this.editDialog.attendeeStore.on('update', this.syncStores, this);
        this.editDialog.attendeeStore.on('remove', this.syncStores, this);
    },
    
    syncStores: function() {
        this.store.removeAll();
        this.store.add(this.originRecord);
        
        this.editDialog.attendeeStore.each(function(attendee) {
            if (attendee.get('user_id')) {
                var attendee = attendee.copy(),
                    displayName = Tine.Calendar.AttendeeGridPanel.prototype.renderAttenderName.call(Tine.Calendar.AttendeeGridPanel.prototype, attendee.get('user_id'), false, attendee);
                    
                attendee.set('displayText', displayName + ' (' + Tine.Calendar.Model.Attender.getRecordName() + ')');
                attendee.set('id', attendee.id);
                this.store.add(attendee);
            }
        }, this);
        
        // reset if perspective attendee is gone
        if (! this.store.getById(this.getValue())) {
            this.setValue(this.originRecord.id);
        }
        
        // sync attendee status
        if (this.getValue() != 'origin') {
            var attendeeStatusField = this.editDialog.getForm().findField('attendeeStatus'),
                attendeeStatus = this.editDialog.attendeeStore.getById(this.getValue()).get('status');
                
            attendeeStatusField.setValue(attendeeStatus);
            
        }
    },
    
    applyAlarmFilter: function() {
        var alarmGrid = this.editDialog.alarmPanel.alarmGrid,
            perspective = this.getValue(),
            perspectiveRecord = this.store.getById(perspective),
            isOriginPerspective = perspective == 'origin';
            
        if (isOriginPerspective) {
            alarmGrid.store.filterBy(function(alarm) {
                // all but option attendee
                return !alarm.getOption('attendee');
            }, this);
        } else {
            alarmGrid.store.filterBy(function(alarm) {
                var skip = alarm.getOption('skip') || [],
                    isSkipped = Tine.Calendar.Model.Attender.getAttendeeStore.getAttenderRecord(Tine.Calendar.Model.Attender.getAttendeeStore(skip), perspectiveRecord),
                    attendee = alarm.getOption('attendee'),
                    isAttendee = Tine.Calendar.Model.Attender.getAttendeeStore.getAttenderRecord(Tine.Calendar.Model.Attender.getAttendeeStore([attendee]), perspectiveRecord);
                    
                // origin w.o. skip of this perspective + specials for this perspective
                return ! (isSkipped || (attendee && ! isAttendee));
            }, this);
            
        }
    },
    
    onAlarmAdd: function(store, records, index) {
        var alarm = records[0],
            perspective = this.getValue(),
            perspectiveRecord = this.store.getById(perspective),
            isOriginPerspective = perspective == 'origin';
            
        if(! alarm.get('id') && ! isOriginPerspective) {
            store.suspendEvents();
            alarm.setOption('attendee', {
                'user_type': perspectiveRecord.get('user_type'),
                'user_id'  : perspectiveRecord.getUserId()
            });
            store.resumeEvents();
        }
    },
    
    onAlarmDelete: function() {
        var alarmGrid = this.editDialog.alarmPanel.alarmGrid,
            perspective = this.getValue(),
            perspectiveRecord = this.store.getById(perspective),
            isOriginPerspective = perspective == 'origin';
        
        Ext.each(alarmGrid.getSelectionModel().getSelections(), function(alarm) {
            if (! isOriginPerspective && ! alarm.getOption('user_id')) {
                var skip = alarm.getOption('skip') || [];
                
                skip.push({
                    'user_type': perspectiveRecord.get('user_type'),
                    'user_id'  : perspectiveRecord.getUserId()
                });
                
                alarm.setOption('skip', skip);
                
                this.applyAlarmFilter();
            } else {
                alarmGrid.store.remove(alarm);
            }
        }, this);
    },
    
    onAlarmRecordUpdate: function(record) {
        var alarmGrid = this.editDialog.alarmPanel.alarmGrid,
            alarmStore = alarmGrid.store;
            
        alarmStore.suspendEvents();
        alarmStore.clearFilter();
        
        Tine.widgets.dialog.AlarmPanel.prototype.onRecordUpdate.call(this.editDialog.alarmPanel, record);
        
        this.applyAlarmFilter();
        alarmStore.suspendEvents();
    },
    
    onPerspectiveChange: function(field, newValue, oldValue) {
        if (newValue != oldValue) {
            this.updatePerspective(oldValue);
            this.loadPerspective(newValue);
        }
    },
    
    /**
     * load perspective into dialog
     */
    loadPerspective: function(perspective) {
        if (! this.perspectiveIsInitialized) {
            this.syncStores();
            
            var myAttendee = Tine.Calendar.Model.Attender.getAttendeeStore.getMyAttenderRecord(this.store),
                imOrganizer = this.editDialog.record.get('organizer').id == Tine.Tinebase.registry.get('currentAccount').contact_id;
                
            perspective = imOrganizer || ! myAttendee ? 'origin' : myAttendee.id;
            this.perspectiveIsInitialized = true;
            this.setValue(perspective);
        }
        
        var perspective = perspective || this.getValue(),
            perspectiveRecord = this.store.getById(perspective),
            isOriginPerspective = perspective == 'origin',
            attendeeStatusField = this.getAttendeeStatusField(),
            attendeeTranspField = this.getAttendeeTranspField(),
            transpField = this.editDialog.getForm().findField('transp'),
            containerField = this.editDialog.getForm().findField('container_id');
            
        attendeeTranspField.setValue(isOriginPerspective ? '' : perspectiveRecord.get('transp'));
        attendeeStatusField.setValue(isOriginPerspective ? '' : perspectiveRecord.get('status'));
//        containerField.setValue(isOriginPerspective ? this.editDialog.record.get('container_id') : perspectiveRecord.get('displaycontainer_id'));
        attendeeStatusField.setVisible(!isOriginPerspective);
        attendeeTranspField.setVisible(!isOriginPerspective);
        transpField.setVisible(isOriginPerspective);
        
        // (de)activate fields 
        var fs = this.editDialog.record.fields;
        fs.each(function(f){
            var field = this.editDialog.getForm().findField(f.name);
            if(field){
                    field.setDisabled(! isOriginPerspective || (field.requiredGrant && ! this.editDialog.record.get(field.requiredGrant)));
            }
        }, this);
        
        attendeeStatusField.setDisabled(isOriginPerspective || ! perspectiveRecord.get('status_authkey'));
        attendeeTranspField.setDisabled(isOriginPerspective || ! perspectiveRecord.get('status_authkey'));
        this.editDialog.alarmPanel.setDisabled((! isOriginPerspective || ! this.editDialog.record.get('editGrant')) && ! perspectiveRecord.get('status_authkey'));
        this.editDialog.attendeeGridPanel.setDisabled(! isOriginPerspective || ! this.editDialog.record.get('editGrant'));
        this.editDialog.rrulePanel.setDisabled(! isOriginPerspective || ! this.editDialog.record.get('editGrant'));
        
        this.applyAlarmFilter();
    },
    
    /**
     * update perspective from dialog
     */
    updatePerspective: function(perspective) {
        var perspective = perspective || this.getValue(),
            perspectiveRecord = this.editDialog.attendeeStore.getById(perspective),
            isOriginPerspective = perspective == 'origin',
            attendeeStatusField = this.getAttendeeStatusField(),
            attendeeTranspField = this.getAttendeeTranspField(),
            containerField = this.editDialog.getForm().findField('container_id');
            
        
        if (! isOriginPerspective) {
            perspectiveRecord.set('transp', attendeeTranspField.getValue());
            perspectiveRecord.set('status', attendeeStatusField.getValue());
//            perspectiveRecord.set('displaycontainer_id', containerField.getValue());
        }
    }
});