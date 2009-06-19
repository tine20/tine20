/**
 * Tine 2.0
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: AttendeeGridPanel.js 8754 2009-06-18 08:50:02Z c.weiss@metaways.de $
 *
 */
 
Ext.ns('Tine.Calendar');


Tine.Calendar.CalendarSelectWidget = function(EventEditDialog) {
    this.EventEditDialog = EventEditDialog;
    
    this.app = Tine.Tinebase.appMgr.get('Calendar');
    this.recordClass = Tine.Calendar.Model.Event;
    
    this.calMapStore = new Ext.data.SimpleStore({
        fields: this.calMapRecord
    });
    
    this.initTpl();
    
    this.fakeCombo = new Ext.form.ComboBox({
        typeAhead     : false,
        triggerAction : 'all',
        editable      : false,
        mode          : 'local',
        value         : null,
        forceSelection: true,
        width         : 450,
        store         : this.calMapStore,
        //itemSelector  : 'div.list-item',
        tpl           : this.attendeeListTpl,
        onSelect      : this.onCalMapSelect.createDelegate(this)
    });
    
    this.calCombo = new Tine.widgets.container.selectionComboBox({
        //id: this.app.appName + 'EditDialogPhysCalSelector',
        fieldLabel: Tine.Tinebase.tranlation._hidden('Saved in'),
        width: 450,
        containerName: this.app.i18n._hidden(this.recordClass.getMeta('containerName')),
        containersName: this.app.i18n._hidden(this.recordClass.getMeta('containersName')),
        appName: this.app.appName,
        hideTrigger2: false,
        trigger2Class: 'cal-invitation-trigger',
        onTrigger2Click: this.fakeCombo.onTriggerClick.createDelegate(this.fakeCombo),
        allowBlank: true
    });
    
    
    
};

Ext.extend(Tine.Calendar.CalendarSelectWidget, Ext.util.Observable, {
    
    calMapRecord: Ext.data.Record.create([
        {name: 'calendar'}, {name: 'user'}, {name: 'calendarName'}, {name: 'userName'}, {name: 'isOriginal'}
    ]),
    
    /**
     * @property {Tine.Calendar.EventEditDialog}
     */
    EventEditDialog: null,
    /**
     * @property {Tine.widgets.container.selectionComboBox} calCombo
     */
    calCombo: null,
    
    getPhysContainer: function() {
        return this.record.get('container_id')
    },
    
    /**
     *  builds attendee -> calendar map
     */
    buildAttendeeCalMapStore: function() {
        this.calMapStore.removeAll();
        var physCal = this.record.get('container_id');
        
        this.EventEditDialog.attendeeStore.each(function(attender){
            var calendar = attender.get('displaycontainer_id');
            var user = attender.get('user_id');
            var calendarName = this.EventEditDialog.attendeeGridPanel.renderAttenderDispContainer(calendar, {});
            var userName = this.EventEditDialog.attendeeGridPanel.renderAttenderName(user, {});
            
            // check if attender is a valid user
            if (userName && attender.get('user_type') == 'user') {
                
                // check if container is resoved
                if (calendar && calendar.name) {
                    // check that calendar is not the physCal
                    if (! (physCal && physCal.name) || physCal.id != calendar.id) {
                        this.calMapStore.add([new this.calMapRecord({
                            calendar: calendar, 
                            user: user,
                            calendarName: calendarName,
                            userName: String.format('for {0}', userName),
                            isOriginal: false
                        })]);
                    }
                }
            }
        }, this);
        
        // finally add physCal if acceptable
        if (physCal && physCal.name) {
            this.calMapStore.add([new this.calMapRecord({
                calendar: physCal, 
                user: 'Originator',
                calendarName: physCal.name,
                userName: this.app.i18n._('Originally'),
                isOriginal: true
            })]);
        }
    },
    
    initTpl: function() {
        this.attendeeListTpl = new Ext.XTemplate(
            '<tpl for=".">' +
                    '<div class="cal-calselectwidget-fakelist-item x-combo-list-item">' +
                            '<div class="cal-calselectwidget-fakelist-calendar">{calendarName}</div>' +
                            '<div class="cal-calselectwidget-fakelist-account">{userName}</div>' +
                    '</div>' +
            '</tpl>'
        );
    },
    
    onCalMapSelect: function(record, index) {
        this.calCombo.setValue(record.get('calendar'));
        this.calCombo.setTrigger2Text(String.format(record.get('userName')));
        
        this.fakeCombo.collapse();
        this.fakeCombo.fireEvent('select', this.fakeCombo, record, index);
    },
    
    onRecordLoad: function(record) {
        this.record = record;
        this.buildAttendeeCalMapStore();
        
        var physCal = record.get('container_id');
        if (physCal.name) {
            this.calCombo.setValue(record.get('container_id'));
            this.calCombo.setTrigger2Text('Originally');
        }
        
    },
    
    onRecordUpdate: function(record) {
        // nothing do do here!
        //console.log('todo: onRecordUpdate');
    },
    
    render: function(el) {
        this.el = el;
        
        new Ext.Panel({
            layout: 'form',
            border: false,
            renderTo: el,
            bodyStyle: {'background-color': '#F0F0F0'},
            items: this.calCombo
        });
        
        this.fakeCombo.render(this.el.insertFirst({tag: 'div', style: {'position': 'absolute', 'top': '0px', 'right': '0px'}}));
    }
});
