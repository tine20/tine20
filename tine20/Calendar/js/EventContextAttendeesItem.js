/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2015 Metaways Infosystems GmbH (http://www.metaways.de)
 */


Tine.Calendar.EventContextAttendeesItem = Ext.extend(Ext.menu.Item, {
    text: 'Teilnehmer/Ressources',
    event: null,
    datetime: null,

    initComponent: function() {
        this.hidden = ! (
            this.event
            && this.event.get('editGrant')
            && Tine.Tinebase.appMgr.get('Calendar').featureEnabled('featureExtendedEventContextActions')
        );

        this.app = Tine.Tinebase.appMgr.get('Calendar');
        this.menu = [];
        this.view = this.app.getMainScreen().getCenterPanel().getCalendarPanel(this.app.getMainScreen().getCenterPanel().activeView).getView();

        var attendeeFilter = Ext.state.Manager.get('calendar-attendee-filter-grid'),
            explicitAttendee = attendeeFilter && Ext.isArray(attendeeFilter.explicitAttendee) ? attendeeFilter.explicitAttendee : [],
            currentAttendee = Tine.Calendar.Model.Attender.getAttendeeStore(this.event ? this.event.get('attendee') : []),
            attendeeRenderer = function(attender) {
                var name = Tine.Calendar.AttendeeGridPanel.prototype.renderAttenderName.call(Tine.Calendar.AttendeeGridPanel.prototype, attender.get('user_id'), false, attender),
                    type = Tine.Calendar.AttendeeGridPanel.prototype.renderAttenderType.call(Tine.Calendar.AttendeeGridPanel.prototype, attender.get('user_type'), false, attender);

                type = type.replace('<div', '<div style="float: left; padding-left: 20px;"');

                return type + name;
            };

        currentAttendee.each(function(r) {
            this.menu.push({
                record: r,
                text: attendeeRenderer(r),
                checked: true,
                handleClick: this.handleClick
            });
        }, this);

        Ext.each(explicitAttendee, function(attendeeData) {
            var r = new Tine.Calendar.Model.Attender(attendeeData, 'new-' + Ext.id()),
                isCurrent = Tine.Calendar.Model.Attender.getAttendeeStore.getAttenderRecord(currentAttendee, r);

            if (! isCurrent) {
                this.menu.push({
                    record: r,
                    text: attendeeRenderer(r),
                    checked: false,
                    handleClick: this.handleClick
                });
            }
        }, this);

        var other = new Ext.Action({
            text: this.app.i18n._('Additional Attendees ...'),
            handler: this.onOtherClick,
            scope: this
        });
        this.menu.push(other);

        Tine.Calendar.EventContextAttendeesItem.superclass.initComponent.call(this);

        this.menu.on('hide', this.onMenuHide, this);

    },

    /**
     * prevent menu hide
     */
    handleClick: function(e) {
        if(this.setChecked && !this.disabled && !(this.checked && this.group)){// disable unselect on radio item
            this.setChecked(!this.checked);
            this.parentMenu.dirty = true;
        }
        e.stopEvent();
    },

    /**
     * show extra window & prevent autosave
     */
    onOtherClick: function() {
        this.menu.un('hide', this.onMenuHide, this);

        var data = [],
            fakeRecord = this.event.copy();

        Ext.each(this.getSelectedRecords(), function(r) {
            data.push(r.data)
        }, this);
        fakeRecord.set('attendee', '');
        fakeRecord.set('attendee', data);

        this.attendeeGridPanel = new Tine.Calendar.AttendeeGridPanel({
            header: false,
            border: false
        });
        this.attendeeGridPanel.onRecordLoad(fakeRecord);

        var win = Tine.WindowFactory.getWindow({
            layout: 'fit',
            width: 500,
            height: 300,
            modal: true,
            title: this.app.i18n._('Attendee'),
            items: [{
                xtype: 'form',
                buttonAlign: 'right',
                border: false,
                layout: 'fit',
                items: this.attendeeGridPanel,
                buttons: [{
                    text: _('Cancel'),
                    minWidth: 70,
                    scope: this,
                    handler: function (btn) {win.close()},
                    iconCls: 'action_cancel'
                }, {
                    text: _('Ok'),
                    minWidth: 70,
                    scope: this,
                    handler: function(btn) {
                        this.attendeeGridPanel.onRecordUpdate(this.event);
                        this.view.editing = this.event;
                        this.app.getMainScreen().getCenterPanel().onUpdateEvent(this.event, false, 'update');
                        win.close();
                    },
                    iconCls: 'action_saveAndClose'
                }]
            }]
        });

        win.show();
    },

    /**
     * autosave
     */
    onMenuHide: function() {
        if (this.updateRecord(this.getSelectedRecords())) {
            this.menu.un('hide', this.onMenuHide, this);
        }
    },

    updateRecord: function(selectedRecords) {
        var data = [];

        Ext.each(selectedRecords, function(r) {
            data.push(r.data)
        }, this);

        if (this.menu.dirty) {
            this.event.set('attendee', '');
            this.event.set('attendee', data);
            this.view.editing = this.event;
            this.app.getMainScreen().getCenterPanel().onUpdateEvent(this.event, false, 'update');
            this.ownerCt.destroy.defer(100, this.ownerCt);
            return true;
        }
    },

    getSelectedRecords: function() {
        var records = [];

        this.menu.items.each(function(item) {
            if (item.checked) {
                records.push(item.record);
            }
        }, this);

        return records;
    }
});

Ext.ux.ItemRegistry.registerItem('Calendar-MainScreenPanel-ContextMenu', Tine.Calendar.EventContextAttendeesItem, 140);
