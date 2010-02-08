/*
 * Tine 2.0
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: AttendeeGridPanel.js 8754 2009-06-18 08:50:02Z c.weiss@metaways.de $
 *
 */
 
Ext.ns('Tine.Calendar');

/**
 * @namespace   Tine.Calendar
 * @class       Tine.Calendar.CalendarSelectWidget
 * @extends     Ext.util.Observable
 * Calendar Selector Widget
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id: DaysView.js 9771 2009-08-05 17:50:15Z c.weiss@metaways.de $
 * @constructor
 * @param {Tine.Calendar.EventEditDialog} EventEditDialog
 */
Tine.Calendar.CalendarSelectWidget = function(EventEditDialog) {
    this.EventEditDialog = EventEditDialog;
    
    Tine.Calendar.CalendarSelectWidget.superclass.constructor.call(this);
};

Ext.extend(Tine.Calendar.CalendarSelectWidget, Ext.Panel, {
    layout: 'fit',

    style: 'padding-right: 5px;',
    /**
     * Calmap record definition
     * 
     * @type Function 
     * @property calMapRecord
     */
    calMapRecord: Ext.data.Record.create([
        {name: 'attender'}, {name: 'calendar'}, {name: 'user'}, {name: 'userAccountId'}, {name: 'calendarName'}, {name: 'userName'}, {name: 'editGrant'}, {name: 'isOriginal'}
    ]),
    
    /**
     * Current calendar map
     * 
     * @type Ext.data.Record 
     * @property currentCalMap
     */
    currentCalMap: null,
    
    /**
     * edit dialog
     * 
     * @type Tine.Calendar.EventEditDialog
     * @property EventEditDialog
     */
    EventEditDialog: null,
    
    /**
     * Calendar select combo box
     * 
     * @type Tine.widgets.container.selectionComboBox
     * @property calCombo
     */
    calCombo: null,
    
    /**
     * returns physical/originator container
     * 
     * @return {Tine.Tinebase.Model.Container}
     */
    getPhysContainer: function() {
        return this.record.get('container_id')
    },
    
    /**
     *  builds attendee -> calendar map
     */
    buildCalMapStore: function() {
        var needEditGrant = true;
        
        this.calMapStore.removeAll();
        var physCal = this.record.get('container_id');
        
        this.EventEditDialog.attendeeStore.each(function(attender){
            var calendar = attender.get('displaycontainer_id');
            var user = attender.get('user_id');
            var userAccountId = attender.getUserAccountId();
            var calendarName = this.EventEditDialog.attendeeGridPanel.renderAttenderDispContainer(calendar, {});
            var userName = this.EventEditDialog.attendeeGridPanel.renderAttenderName(user, {});
            
            // check if attender is a user which is/has an useraccount
            if (userAccountId && userName) {
                // check if container is resoved
                if (calendar && calendar.name) {
                    // check that calendar is not the physCal
                    if (! (physCal && physCal.name) || physCal.id != calendar.id) {
                        if (! needEditGrant || calendar.account_grants.editGrant) {
                            this.calMapStore.add([new this.calMapRecord({
                                attender: attender,
                                calendar: calendar, 
                                user: user,
                                userAccountId: userAccountId,
                                calendarName: calendarName,
                                userName: String.format('for {0}', userName),
                                editGrant: calendar.account_grants.editGrant,
                                isOriginal: false
                            })]);
                        }
                    }
                }
            }
        }, this);
        
        // finally add physCal if acceptable
        if (physCal && physCal.name && (! needEditGrant || physCal.account_grants.editGrant)) {
            this.calMapStore.add([new this.calMapRecord({
                calendar: physCal, 
                calendarName: physCal.name,
                userName: this.app.i18n._('Originally'),
                editGrant: physCal.account_grants.editGrant,
                isOriginal: true
            })]);
        }
    },
    
    initComponent: function() {
        this.app = Tine.Tinebase.appMgr.get('Calendar');
        this.recordClass = Tine.Calendar.Model.Event;
        
        this.currentAccountId = Tine.Tinebase.registry.get('currentAccount').accountId;
        
        this.calMapStore = new Ext.data.SimpleStore({
            fields: this.calMapRecord
        });
        
        this.initTpl();
        
        this.fakeCombo = new Ext.form.ComboBox({
            mode          : 'local',
            hideMode      : 'visibility',
            style         : {'position': 'absolute', 'top': '0px', 'right': '0px'},
            store         : this.calMapStore,
            tpl           : this.attendeeListTpl,
            onSelect      : this.onCalMapSelect.createDelegate(this)
        });
        
        this.calCombo = new Tine.widgets.container.selectionComboBox({
            hideLabel: true,
            containerName: this.app.i18n.n_hidden(this.recordClass.getMeta('containerName'), this.recordClass.getMeta('containersName'), 1),
            containersName: this.app.i18n._hidden(this.recordClass.getMeta('containersName')),
            appName: this.app.appName,
            requiredGrant: 'addGrant',
            hideTrigger2: false,
            trigger2Class: 'cal-invitation-trigger',
            onTrigger2Click: this.fakeCombo.onTriggerClick.createDelegate(this.fakeCombo),
            allowBlank: true,
            listeners: {
                scope: this,
                beforequery: this.onBeforeCalComboQuery,
                select: this.onCalComboSelect
            }
        });
        
        this.items = [
            this.calCombo,
            this.fakeCombo
        ];
        
        this.supr().initComponent.call(this);
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
    
    onAttendeUpdate: function(store, updatedAttender) {
        var userAccountId = attender.getUserAccountId();
        
        // we are only interested in attenders wich are/have a user account
        if (userAccountId) {
            // mhh weired, somtimes a container comes instead of an attender...
            if (typeof updatedAttender.get('displaycontainer_id').get == 'function') {
                // check if currently displayed container changed
                if (updatedAttender.get('displaycontainer_id').get('account_grants').account_id == this.currentCalMap.get('userAccountId')) {
                    //console.log('currently displayed non original changed');
                    this.currentCalMap.set('calendar', '');
                    this.currentCalMap.set('calendar', updatedAttender.get('displaycontainer_id'));
                    //this.calCombo.setValue(updatedAttender.get('displaycontainer_id'));
                    this.calCombo.setRawValue(updatedAttender.get('displaycontainer_id').get('name'));
                } else if (userAccountId == this.currentAccountId && updatedAttender.get('user_type') == 'user' && this.currentCalMap.get('isOriginal')) {
                    //console.log('currently displayed original changed');
                    this.currentCalMap.set('calendar', '');
                    this.currentCalMap.set('calendar', updatedAttender.get('displaycontainer_id'));
                    //this.calCombo.setValue(updatedAttender.get('displaycontainer_id'));
                    this.calCombo.setRawValue(updatedAttender.get('displaycontainer_id').get('name'));
                }
            }
        }
    },
    
    onBeforeCalComboQuery: function() {
        if(! this.currentCalMap || this.currentCalMap.get('isOriginal')) {
            this.calCombo.startNode = 'all';
        } else {
            this.calCombo.startNode = 'personalOf';
            this.calCombo.owner = this.currentCalMap.get('userAccountId');
        }
    },
    
    onCalComboSelect: function() {
        var container = this.calCombo.selectedContainer;
        container.toString = function() {return container.id};
        
        if (! this.currentCalMap || this.currentCalMap.get('isOriginal')) {
            this.record.set('container_id', container);
        } else {
            this.currentCalMap.get('attender').set('displaycontainer_id', container);
        }
    },
    
    onCalMapSelect: function(record, index) {
        if (record && typeof record.get == 'function') {
            this.calCombo.setValue(record.get('calendar'));
            this.calCombo.setTrigger2Text(String.format(record.get('userName')));
            
            this.currentCalMap = record;
            
            this.fakeCombo.collapse();
            this.fakeCombo.hide();
            
            this.fakeCombo.fireEvent('select', this.fakeCombo, record, index);
        }
    },
    
    /**
     * loads this widget with data from given record
     * called by edit dialog when record is loaded
     * 
     * @param {Tine.Calendar.Model.Event} record
     */
    onRecordLoad: function(record) {
        this.record = record;
        this.buildCalMapStore();
        
        if (this.calMapStore.getCount() == 0) {
            // call setValue to add 'choose other'...
            this.calCombo.setValue('');
        } else if (this.calMapStore.getCount() == 1) {
            this.onCalMapSelect(this.calMapStore.getAt(0));
            this.calCombo.setTrigger2Disabled(true);
        } else {
            var mine = this.calMapStore.find('userAccountId', this.currentAccountId);
            var phys = this.calMapStore.find('isOriginal', true);
            
            var take = mine > 0 ? mine : phys;
            this.onCalMapSelect(this.calMapStore.getAt(take));
        }
    },
    
    /**
     * Updates given record with data from this widget
     * called by edit dialog to get data
     * 
     * @param {Tine.Calendar.Model.Event} record
     */
    onRecordUpdate: function(record) {
        // nothing do do here!
        //console.log('todo: onRecordUpdate');
    },
    
    onResize: function(width, height) {
        this.supr().onResize.apply(this, arguments);
        this.fakeCombo.setWidth(width);
    }
});
