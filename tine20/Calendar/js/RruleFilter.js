/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Michael Spahn <m.spahn@metaways.de>
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.Calendar');

/**
 * @namespace   Tine.Calendar
 * @class       Tine.Calendar.RruleFilter
 * @extends     Tine.widgets.grid.FilterModel
 *
 * @author      Michael Spahn <m.spahn@metaways.de>
 */
Tine.Calendar.RruleFilter = Ext.extend(Tine.widgets.grid.FilterModel, {
    app: null,

    field: 'rrule',

    defaultOperator: 'in',

    operators: ['in', 'notin'],

    initComponent: function () {
        Tine.Calendar.AttendeeFilterModel.superclass.initComponent.call(this);

        this.app = this.app || Tine.Tinebase.appMgr.get('Calendar');

        this.label = this.app.i18n._('Rrule');

        this.defaultValue = [{
            'daily': true,
            'weekly': true,
            'monthly': true,
            'yearly': true
        }];
    },

    valueRenderer: function (filter, el) {
        var value  = new Tine.Calendar.RruleFilterValueField({
            app: this.app,
            filter: filter,
            value: filter.data.value ? filter.data.value : this.defaultValue,
            renderTo: el
        });

        value.on('select', this.onFiltertrigger, this);
        value.onCheckboxSelect(null, null);

        return value;
    }
});

Tine.widgets.grid.FilterToolbar.FILTERS['calendar.rrule'] = Tine.Calendar.RruleFilter;

/**
 * @namespace   Tine.Calendar
 * @class       Tine.Calendar.RruleFilterValueField
 * @extends     Ext.ux.form.LayerCombo
 *
 * @author      Michael Spahn <m.spahn@metaways.de>
 */
Tine.Calendar.RruleFilterValueField = Ext.extend(Ext.ux.form.LayerCombo, {
    hideButtons: false,

    lazyInit: false,

    formConfig: {
        labelAlign: 'left',
        labelWidth: 30
    },

    dailyCheckbox: null,
    weeklyCheckbox: null,
    monthlyCheckbox: null,
    yearlyCheckbox: null,

    initComponent: function () {
        this.supr().initComponent.apply(this, arguments);
    },

    getFormValue: function () {
        return [{
            'daily': this.dailyCheckbox.getValue(),
            'weekly': this.weeklyCheckbox.getValue(),
            'monthly': this.monthlyCheckbox.getValue(),
            'yearly': this.yearlyCheckbox.getValue()
        }];
    },

    getItems: function () {
        this.dailyCheckbox = new Ext.form.Checkbox({
            boxLabel: this.app.i18n._('Daily'),
            name: 'daily',
            columnWidth: 1,
            readOnly: false,
            disabled: false,
            checked: true,
            listeners: {scope: this, check: this.onCheckboxSelect}
        });

        this.weeklyCheckbox = new Ext.form.Checkbox({
            xtype: 'checkbox',
            boxLabel: this.app.i18n._('Weekly'),
            name: 'weekly',
            columnWidth: 1,
            readOnly: false,
            disabled: false,
            checked: true,
            listeners: {scope: this, check: this.onCheckboxSelect}
        });

        this.monthlyCheckbox = new Ext.form.Checkbox({
            boxLabel: this.app.i18n._('Monthly'),
            name: 'monthly',
            columnWidth: 1,
            readOnly: false,
            disabled: false,
            checked: true,
            listeners: {scope: this, check: this.onCheckboxSelect}
        });

        this.yearlyCheckbox = new Ext.form.Checkbox({
            boxLabel: this.app.i18n._('Yearly'),
            name: 'yearly',
            columnWidth: 1,
            readOnly: false,
            disabled: false,
            checked: true,
            listeners: {scope: this, check: this.onCheckboxSelect}
        });

        return [
            this.dailyCheckbox,
            this.weeklyCheckbox,
            this.monthlyCheckbox,
            this.yearlyCheckbox
        ];
    },

    onCheckboxSelect: function (cb, checked) {
        var selection = [];

        if (this.dailyCheckbox.getValue()) {
            selection.push(this.dailyCheckbox.boxLabel);
        }

        if (this.weeklyCheckbox.getValue()) {
            selection.push(this.weeklyCheckbox.boxLabel);
        }

        if (this.monthlyCheckbox.getValue()) {
            selection.push(this.monthlyCheckbox.boxLabel);
        }

        if (this.yearlyCheckbox.getValue()) {
            selection.push(this.yearlyCheckbox.boxLabel);
        }

        this.setRawValue(selection.join(', '));
        this.setValue(this.getFormValue());
    },

    setValue: function (value) {
        value = Ext.isArray(value) ? value : [value];

        this.setFormValue(value);

        return this.supr().setValue.apply(this, [value]);
    },

    setFormValue: function(value) {
        this.dailyCheckbox.setValue(value[0].daily || false);
        this.weeklyCheckbox.setValue(value[0].weekly || false);
        this.monthlyCheckbox.setValue(value[0].monthly || false);
        this.yearlyCheckbox.setValue(value[0].yearly || false);
    }
});
