/*
 * Tine 2.0
 *
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

Ext.ns('Tine.Calendar');

require('./ResourcePickerCombo');
require('./FreeBusyInfo');

/**
 * @namespace   Tine.Calendar
 * @class       Tine.Calendar.AttendeePickerCombo
 * @extends     Tine.Tinebase.widgets.form.RecordPickerComboBox
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */
Tine.Calendar.AttendeePickerCombo = Ext.extend(Tine.Tinebase.widgets.form.RecordPickerComboBox, {
    /**
     * @cfg {} eventRecord
     * reference to event record. If set scheduling info for freebusy info will be taken from it
     */
    eventRecord: null,

    recordClass: Tine.Calendar.Model.Attender,
    itemSelector: '.cal-attendee-picker-combo-list-item',

    initComponent: function() {
        this.typeTemplates = {};

        Tine.Calendar.AttendeePickerCombo.superclass.initComponent.call(this);
        this.store.proxy.jsonReader.readRecords = this.readRecords.createDelegate(this);
    },

    onBeforeLoad: function(store, options) {
        Tine.Calendar.AttendeePickerCombo.superclass.onBeforeLoad.call(this, store, options);

        var _ = window.lodash;
        options.params.ignoreUIDs = [];
        if (_.get(this, 'eventRecord.data.dtstart')) {
            options.params.event = this.eventRecord.data;
            if (this.eventRecord.get('uid')) {
                options.params.ignoreUIDs.push(this.eventRecord.get('uid'));
            }
        }
    },

    readRecords : function(resultData){
        // Tine.Calendar.AttendeePickerCombo.superclass.onStoreBeforeLoadRecords.apply(this, arguments);

        var _ = window.lodash,
            totalcount = 0,
            eventRecord = this.eventRecord,
            records = [],
            fbInfo = new Tine.Calendar.FreeBusyInfo(resultData.freeBusyInfo)

        _.each(['user', 'group', 'resource'], function(type) {
            var typeResult = _.get(resultData, type, {}),
                typeCount = _.get(typeResult, 'totalcount', 0),
                typeData = _.get(typeResult, 'results', []);

            totalcount += +typeCount;
            _.each(typeData, function(userData) {
                var id = type + '-' + userData.id,
                    attendeeData = _.assign(Tine.Calendar.Model.Attender.getDefaultData(), {
                        id: id,
                        user_type: type,
                        user_id: userData
                    }),
                    attendee = new Tine.Calendar.Model.Attender(attendeeData, id);

                if (_.get(eventRecord, 'data.dtstart')) {
                    attendee.set('fbInfo', fbInfo.getStateOfAttendee(attendee, eventRecord));
                }
                records.push(attendee);
            });
        });

        return {
            success : true,
            records: records,
            totalRecords: totalcount
        };
    },

    /**
     * respect record.getTitle method
     */
    initTemplate: function() {
        if (! this.tpl) {
            this.tpl = new Ext.XTemplate(
                '<tpl for=".">',
                    '<div class="cal-attendee-picker-combo-list-item">',
                        '<table>',
                            '<tr>',
                                '<td width="100%">{[this.getAttendeeItem(values.' + this.recordClass.getMeta('idProperty') + ')]}</td>',
                                '<td style="min-width: 20px;" class="cal-attendee-picker-combo-list-item-fbinfo">{values.fbInfo}</td>',
                            '</tr>',
                        '</table>',
                    '</div>',
                '</tpl>', this);
        }
    },

    getAttendeeItem: function(id) {
        var record = this.getStore().getById(id),
            type = record.get('user_type'),
            template = this.getTemplate(type);

        return template.apply([record.get('user_id')])
    },

    getTemplate: function(type) {
        if (! this.typeTemplates[type]) {
            var p,
                o = {
                    getLastQuery: this.getLastQuery.createDelegate(this)
                };

            switch (type) {
                case 'user':
                    p = Tine.Addressbook.ContactSearchCombo.prototype;
                    break;
                case 'group':
                    p = Tine.Addressbook.ListSearchCombo.prototype;
                    break;
                case 'resource':
                    p = Tine.Calendar.ResourcePickerCombo.prototype;
                    break;
            }

            p.initTemplate.call(o);
            this.typeTemplates[type] = o.tpl;
        }

        return this.typeTemplates[type];
    }
});