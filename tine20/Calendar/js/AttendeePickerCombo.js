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

    /**
     * @cfg {Bool} requireFreeBusyGrantOnly
     * freebusy grant is sufficient to find ressource (instead of inviteGrant)
     */
    requireFreeBusyGrantOnly: false,

    /**
     * @property {Tine.Calendar.Model.AttenderProxy} recordProxy
     */
    recordProxy: null,

    recordClass: Tine.Calendar.Model.Attender,

    itemSelector: '.cal-attendee-picker-combo-list-item',

    initComponent: function() {
        _ = window.lodash;

        this.typeTemplates = {};

        this.recordProxy = new Tine.Calendar.Model.AttenderProxy({
            freeBusyEventsProvider: _.bind(function() {
                return [this.eventRecord];
            }, this)
        });

        Tine.Calendar.AttendeePickerCombo.superclass.initComponent.call(this);
    },

    /**
     * prepare paging and sort
     *
     * @param {Ext.data.Store} store
     * @param {Object} options
     */
    onBeforeLoad: function (store, options) {
        Tine.Calendar.AttendeePickerCombo.superclass.onBeforeLoad.call(this, store, options);

        if (this.requireFreeBusyGrantOnly) {
            this.store.baseParams.filter.push({
                field: 'resourceFilter', value: [
                    {field: 'requireFreeBusyGrant', value: 1}
                ]
            });
        }
    },

    /**
     * create bogus attendee record for directly entered email-addresses
     */
    getValue: function() {
        if (this.el.dom) {
            var raw = _.get(this, 'el.dom') ? this.getRawValue() : Ext.value(this.value, '');
            if (Ext.form.VTypes.email(raw)) {
                this.value = raw;
                this.selectedRecord = new Tine.Calendar.Model.Attender(Ext.apply(Tine.Calendar.Model.Attender.getDefaultData(), {
                    'user_type': 'user',
                    'user_id': this.value
                }));
            }
        }
        return Tine.Calendar.AttendeePickerCombo.superclass.getValue.apply(this, arguments);
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