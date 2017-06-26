/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Michael Spahn <m.spahn@metaways.de>
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.Calendar');

/**
 * File picker dialog
 *
 * @namespace   Tine.Calendar
 * @class       Tine.Calendar.EventFinderOptionsDialog
 * @extends     Ext.FormPanel
 * @constructor
 * @param       {Object} config The configuration options.
 */
Tine.Calendar.EventFinderOptionsDialog = Ext.extend(Ext.Panel, {
    defaultOptions: [
        {id: 'monday', active: true, config: [8, 18.00]},
        {id: 'tuesday', active: true, config: [8, 18.00]},
        {id: 'wednesday', active: true, config: [8, 18.00]},
        {id: 'thursday', active: true, config: [8, 18.00]},
        {id: 'friday', active: true, config: [8, 18.00]},
        {id: 'monday', active: false, config: [8, 18.00]},
        {id: 'monday', active: false, config: [8, 18.00]}
    ],

    windowNamePrefix: 'eventfinderoptionsdialog_',

    app: null,
    cls: 'tw-editdialog',
    header: false,
    border: false,

    layout: 'fit',

    wkdays: ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'],

    mondaySlider: null,
    tuesdaySlider: null,
    wednesdaySlider: null,
    thursdaySlider: null,
    fridaySlider: null,
    saturdaySlider: null,
    sundaySlider: null,

    mondayCheckbox: null,
    tuesdayCheckbox: null,
    wednesdayCheckbox: null,
    thursdayCheckbox: null,
    fridayCheckbox: null,
    saturdayCheckbox: null,
    sundayCheckbox: null,

    stateId: 'eventFinderOptions',
    stateConfig: [],

    initComponent: function () {
        if (null === this.app) {
            this.app = Tine.Tinebase.appMgr.get('Calendar');
        }

        this.title = this.titleText ? this.titleText : this.app.i18n._('Event finder options');
        this.window.setTitle(this.title);

        this.initActions();
        this.initButtons();

        this.stateConfig = Ext.state.Manager.get(this.stateId, this.defaultOptions);

        this.items = this.getFormItems();

        this.supr().initComponent.apply(this, arguments);
    },

    initButtons: function () {
        this.fbar = [
            '->'
        ];

        this.fbar.push(this.action_cancel, this.action_saveAndClose);
    },

    initActions: function () {
        this.action_saveAndClose = new Ext.Action({
            text: this.app.i18n._('Ok'),
            minWidth: 70,
            ref: '../btnSaveAndClose',
            scope: this,
            handler: function () {
                this.onSaveAndClose();
            },
            iconCls: 'action_saveAndClose'
        });

        this.action_cancel = new Ext.Action({
            text: this.app.i18n._('Cancel'),
            minWidth: 70,
            scope: this,
            handler: this.onCancel,
            iconCls: 'action_cancel'
        });

        this.actionUpdater = new Tine.widgets.ActionUpdater({
            containerProperty: this.recordClass ? this.recordClass.getMeta('containerProperty') : null,
            evalGrants: this.evalGrants
        });

        this.actionUpdater.addActions([
            this.action_saveAndClose,
            this.action_cancel
        ]);
    },

    onCancel: function () {
        this.window.close();
    },

    /**
     * Get a time period of a given slider range
     *
     *    0:0:0 - 23:59:59
     *
     * @param range
     * @return {{from: *, until: *}}
     */
    getPeriodFromSliderRange: function (range) {
        var hoursStart = Tine.Tinebase.common.trunc(range[0]);
        var minStart = Tine.Tinebase.common.trunc(Math.round((range[0] % 1) * 100) * 0.60);

        var hoursEnd = Tine.Tinebase.common.trunc(range[1]);
        var minEnd = Tine.Tinebase.common.trunc((range[1] % 1) * 100 * 0.60);

        var startDate = new Date();
        startDate.setHours(hoursStart);
        startDate.setMinutes(minStart);
        startDate.setSeconds(0);

        var endDate = new Date();
        endDate.setHours(hoursEnd);
        endDate.setMinutes(minEnd);
        endDate.setSeconds((hoursEnd === 23 && minEnd === 59) ? 59 : 0);

        var pattern = 'H:i:s';

        return {
            from: startDate.format(pattern),
            until: endDate.format(pattern)
        };
    },

    onSaveAndClose: function () {
        var data = [{
            id: 'monday',
            active: this.mondayCheckbox.getValue(),
            config: this.mondaySlider.getRange(),
            period: this.getPeriodFromSliderRange(this.mondaySlider.getRange())
        }, {
            id: 'tuesday',
            active: this.tuesdayCheckbox.getValue(),
            config: this.tuesdaySlider.getRange(),
            period: this.getPeriodFromSliderRange(this.tuesdaySlider.getRange())
        }, {
            id: 'wednesday',
            active: this.wednesdayCheckbox.getValue(),
            config: this.wednesdaySlider.getRange(),
            period: this.getPeriodFromSliderRange(this.wednesdaySlider.getRange())
        }, {
            id: 'thursday',
            active: this.thursdayCheckbox.getValue(),
            config: this.thursdaySlider.getRange(),
            period: this.getPeriodFromSliderRange(this.thursdaySlider.getRange())
        }, {
            id: 'friday',
            active: this.fridayCheckbox.getValue(),
            config: this.fridaySlider.getRange(),
            period: this.getPeriodFromSliderRange(this.fridaySlider.getRange())
        }, {
            id: 'saturday',
            active: this.saturdayCheckbox.getValue(),
            config: this.saturdaySlider.getRange(),
            period: this.getPeriodFromSliderRange(this.saturdaySlider.getRange())
        }, {
            id: 'sunday',
            active: this.sundayCheckbox.getValue(),
            config: this.sundaySlider.getRange(),
            period: this.getPeriodFromSliderRange(this.sundaySlider.getRange())
        }];

        Ext.state.Manager.set(this.stateId, data);
        this.fireEvent('apply', this, data);
        this.window.close();
    },

    onCheckSlider: function (cb, checked) {
        this[cb.name + 'Slider'].setDisabled(!checked);
    },

    getCheckboxSliderRowFor: function (id) {
        var _ = window.lodash;

        var config = _.find(this.stateConfig, function (o) {
            return o.id === id;
        });

        return {
            layout: 'hbox',
            layoutConfig: {
                align: 'stretch',
                pack: 'start'
            },
            height: 30,
            items: [
                {
                    xtype: 'checkbox',
                    checked: !!config && config.active,
                    boxLabel: _.capitalize(id),
                    name: id,
                    anchor: '95%',
                    flex: 1,
                    ref: '../../../../../' + id + 'Checkbox',
                    listeners: {scope: this, check: this.onCheckSlider}
                }, this.getSliderFor(id, config)
            ]
        };
    },

    getSliderFor: function (id, config) {
        var _ = window.lodash;
        var sliderId = id + 'Slider';

        var sliderStart = 0;
        var sliderEnd = 23.9999;

        if (config) {
            sliderStart = config.config[0];
            sliderEnd = config.config[1];
        }

        return new Tine.Tinebase.RangeSliderComponent({
            width: 500,
            ref: '../../../../../' + sliderId,
            currentStart: sliderStart,
            currentEnd: sliderEnd,
            disabled: !config || !config.active
        });
    },

    /**
     * @todo: improve layout, rangeslidercomponent can't deal with resizing! Techically it can but it's extremely slow atm!
     */
    getFormItems: function () {
        var wkdayItems = [];
        for (var i = 0, d; i < 7; i++) {
            d = (i + Ext.DatePicker.prototype.startDay) % 7;
            wkdayItems.push(this.getCheckboxSliderRowFor(this.wkdays[d]));
        }

        var sliderElements = [{
            layout: 'vbox',
            border: false,

            layoutConfig: {
                align: 'stretch',
                pack: 'start'
            },

            items: wkdayItems
        }];

        return {
            xtype: 'tabpanel',
            border: false,
            plain: true,
            defaults: {
                hideMode: 'offsets'
            },
            plugins: [{
                ptype: 'ux.tabpanelkeyplugin'
            }],
            activeTab: 0,
            items: [{
                title: this.title,
                autoScroll: true,
                border: false,
                frame: true,
                layout: 'border',
                items: [{
                    region: 'center',
                    layout: 'fit',
                    border: false,
                    items: sliderElements
                }]
            }]
        };
    }
});

/**
 * Create new EventFinderOptionsDialog window
 */
Tine.Calendar.EventFinderOptionsDialog.openWindow = function (config) {
    var window = Tine.WindowFactory.getWindow({
        width: 800,
        height: 300,
        name: Tine.Calendar.EventFinderOptionsDialog.prototype.windowNamePrefix + config.recordId,
        contentPanelConstructor: 'Tine.Calendar.EventFinderOptionsDialog',
        contentPanelConstructorConfig: config
    });
    return window;
};
