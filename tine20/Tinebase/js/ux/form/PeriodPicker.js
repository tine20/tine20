/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/*global Ext*/

Ext.ns('Ext.ux', 'Ext.ux.form');

/**
 * A combination range and paging control
 *
 * @namespace   Ext.ux.form
 * @class       Ext.ux.form.PeriodPicker
 * @extends     Ext.form.Field
 */
Ext.ux.form.PeriodPicker = Ext.extend(Ext.form.Field, {

    /**
     * @cfg {String} availableRanges
     * ranges available in range picker
     * _('Day'), _('Week'), _('Month'), _('Quarter'), _('Year')
     */
    availableRanges: 'day,week,month,quarter,year',

    /**
     * @cfg {String} range
     * initial range
     */
    range: 'month',

    /**
     * @cfg {Date} startDate
     * defaults to toDay, will be constraint to period, gets overwritten by value
     */
    startDate: null,

    defaultAutoCreate: {
        tag: 'table',
        cls: 'ux-pp-field',
        cn: [{
            tag: 'tr',
            cn: [{
                tag: 'td',
                cls: 'ux-pp-range'
            }, {
                tag: 'td',
                cls: 'ux-pp-prev'
            }, {
                tag: 'td',
                cls: 'ux-pp-period'
            }, {
                tag: 'td',
                cls: 'ux-pp-next'
            }]
        }]
    },

    initComponent: function() {
        Ext.ux.form.PeriodPicker.superclass.initComponent.call(this);

        this.value = Ext.ux.form.PeriodPicker.getPeriod(this.value ? this.value.from : this.startDate || new Date(), this.range);
        this.startDate = this.value.from;
        this.startValue = this.value;
    },

    /**
     * @return {Object} {from: Date, until: Date}
     */
    getValue: function() {
        return this.value;
    },

    /**
     * @param {Object} value {from: Date, until: Date}
     */
    setValue: function(value) {
        this.range = Ext.ux.form.PeriodPicker.getRange(value);
        this.value = Ext.ux.form.PeriodPicker.getPeriod(value.from, this.range);
        this.startDate = this.value.from;

        this.getRangeCombo().setValue(this.range);
        var dateString;

        switch(this.range) {
            case 'day':
                dateString = Tine.Tinebase.common.dateRenderer(this.startDate);
                break;
            case 'week':
                // NOTE: '+1' is to ensure we display the ISO8601 based week where weeks always start on monday!
                var wkStart = this.startDate.add(Date.DAY, this.startDate.getDay() < 1 ? 1 : 0);

                dateString = wkStart.getWeekOfYear() + ' ' + this.startDate.format('Y');
                break;
            case 'month':
                dateString = Date.getShortMonthName(this.startDate.getMonth()) + ' ' + this.startDate.format('Y');
                break;
            case 'quarter':
                dateString = Math.ceil(+this.startDate.format('n')/3) + ' ' + this.startDate.format('Y');
                break;
            case 'year':
                dateString = this.startDate.format('Y');
                break;
        }
        this.setPeriodText(dateString);

        if (JSON.stringify(this.value) != JSON.stringify(this.startValue)){
            this.fireEvent('change', this, this.value, this.startValue);
        }
    },

    reset : function() {
        this.originalValue = this.startValue;

        Ext.ux.form.PeriodPicker.superclass.reset.apply(this, arguments);
    },

    setStartDate: function(startDate) {
        var value = Ext.ux.form.PeriodPicker.getPeriod(startDate, this.range);
        this.setValue(value);
    },

    setPeriodText: function(text) {
        this.el.child('.ux-pp-period').update(Ext.util.Format.htmlEncode(text));
    },

    // private
    onRangeComboChange: function() {
        this.setValue(Ext.ux.form.PeriodPicker.getPeriod(this.startDate, this.getRangeCombo().getValue()));
    },

    // private
    onClick: function(e) {
        var prev = e.getTarget('.ux-pp-prev'),
            next = e.getTarget('.ux-pp-next'),
            period = e.getTarget('.ux-pp-period');

        if (next) {
            this.setStartDate(this.value.until.add(Date.DAY, 1));
        } else if (prev) {
            this.setStartDate(this.value.from.add(Date.DAY, -1));
        } else if (period) {
            this.getDatePickerMenu().show(period);
        }
    },

    // private
    onRender : function(ct, position){
        this.doc = Ext.isIE ? Ext.getBody() : Ext.getDoc();
        Ext.ux.form.PeriodPicker.superclass.onRender.call(this, ct, position);

        var rangeCombo = this.getRangeCombo();
        rangeCombo.render(this.el.child('.ux-pp-range'));
        rangeCombo.setWidth(this.getEl().getWidth() * 0.4);

        this.setValue(this.value);
        this.mon(this.getEl(), 'click', this.onClick, this);
    },

    // private
    onResize: function (w, h) {
        Ext.ux.form.PeriodPicker.superclass.onResize.apply(this, arguments);

        this.getRangeCombo().setWidth(this.getEl().getWidth() * 0.4);
    },

    getRangeCombo: function() {
        if (! this.rangeCombo) {
            var fieldDef = [];
            Ext.each(this.availableRanges.split(','), function(range) {
                fieldDef.push([range, i18n._hidden(Ext.util.Format.capitalize(range))]);
            });

            this.rangeCombo = new Ext.form.ComboBox({
                typeAhead: true,
                triggerAction: 'all',
                mode: 'local',
                forceSelection: true,
                allowEmpty: false,
                editable: false,
                store: fieldDef,
                // value: this.range,
                listeners: {
                    scope: this,
                    select: this.onRangeComboChange
                }
            });
        }

        return this.rangeCombo;
    },

    /**
     * returns a new datepickerMenu
     *
     * @returns {Ext.menu.DateMenu}
     */
    getDatePickerMenu: function() {
            var me = this;

            return new Ext.menu.DateMenu({
                value: this.startDate,
                hideOnClick: true,
                focusOnSelect: true,
                plugins: [new Ext.ux.DatePickerWeekPlugin({
                    weekHeaderString: Tine.Tinebase.appMgr.get('Calendar').i18n._hidden('WK'),
                    inspectMonthPickerClick: function(btn, e) {
                        if (e.getTarget('button')) {
                            me.getRangeCombo().setValue('month');
                            me.range = 'month';
                            me.setStartDate(this.activeDate);
                            this.destroy();
                            return false;
                        }
                    }
                })],
                listeners: {
                    scope: this,
                    select: function(picker, value, weekNumber) {
                        this.getRangeCombo().setValue(weekNumber ? 'week' : 'day');
                        this.range = weekNumber ? 'week' : 'day';
                        this.setStartDate(value);
                    }
                }
            });
    }
});

/**
 * gets period
 *
 * @static
 * @param {Date} startDate date within period
 * @param {String} range day|week|month|year
 * @return {Object} {from: Date, until: Date}
 */
Ext.ux.form.PeriodPicker.getPeriod = function(startDate, range) {
    var from, until;
    switch(range) {
        case 'day':
            from = startDate.clearTime(true);
            until = from.add(Date.DAY, 1);
            break;
        case 'week':
            from = startDate.clearTime(true).add(Date.DAY, -1 * startDate.getDay())
                .add(Date.DAY, Ext.DatePicker.prototype.startDay - (startDate.getDay() == 0 ? 7 : 0));
            until = from.add(Date.DAY, 7);
            break;
        case 'month':
            from = startDate.clearTime(true).getFirstDateOfMonth();
            until = from.getLastDateOfMonth().add(Date.DAY, 1);
            break;
        case 'quarter':
            from = startDate.clearTime(true).getFirstDateOfMonth().add(Date.MONTH, -1 * ((+startDate.format('n')-1)%3));
            until = from.add(Date.MONTH, 2).getLastDateOfMonth().add(Date.DAY, 1);
            break;
        case 'year':
            var year = startDate.format('Y');
            from = Date.parseDate(year + '-01-01 00:00:00', 'Y-m-d H:i:s');
            until = Date.parseDate(++year + '-01-01 00:00:00', 'Y-m-d H:i:s');
            break;
    }

    return {from: from, until: until};
};

/**
 * gets period
 *
 * @static
 * @param {Object} {from: Date, until: Date}
 * @return {String} range day|week|month|year
 */
Ext.ux.form.PeriodPicker.getRange = function(period) {
    var ms = period.from.getElapsed(period.until),
        msDay = 86400000;

    if (ms > msDay * 300) {
        return 'year';
    } else if (ms > msDay * 80) {
        return 'quarter';
    } else if (ms > msDay * 20) {
        return 'month';
    } else if (ms > msDay * 5) {
        return 'week';
    } else {
        return 'day'
    }

}
Ext.reg('ux-period-picker', Ext.ux.form.PeriodPicker)