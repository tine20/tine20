/**
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

Ext.ns('Ext.ux.form');

/**
 * handles minutes to time conversions
 * @class Ext.ux.form.DurationSpinner
 * @extends Ext.ux.form.Spinner
 */
Ext.ux.form.DurationSpinner = Ext.extend(Ext.ux.form.Spinner,  {

    /**
     * Set to empty value if value equals 0
     * @cfg emptyOnZero
     */
    emptyOnZero: null,

    /**
     * allow negative values
     * @cfg allowNegative bool
     */
    allowNegative: true,

    /**
     * $cfg {String} baseUnit minutes|seconds
     */
    baseUnit: 'minutes',

    initComponent: function() {
        this.preventMark = false;
        this.strategy = new Ext.ux.form.Spinner.TimeStrategy({
            incrementValue : 15,
            allowNegative: this.allowNegative
        });
        this.format = this.strategy.format;

        this.on('focus', function(field) {field.focus(true, 10)});
    },

    setValue: function(value) {
        value = Ext.ux.form.DurationSpinner.durationRenderer(value, this);

        Ext.ux.form.DurationSpinner.superclass.setValue.call(this, value);
    },

    validateValue: function(value) {
        if (value.search(/:/) != -1) {
            value = value.replace(Ext.ux.form.Spinner.DateStrategy.isNegRe, '');

            var parts = value.split(':'),
                hours = parseInt(parts[0]),
                minutes = parseInt(parts[1]);

            return 'NaN' != hours && 'NaN' != minutes;
        }
    },

    getValue: function() {
        var value = Ext.ux.form.DurationSpinner.superclass.getValue.call(this),
            isNegValue = false;

        value = value.replace(',', '.');

        if(value && typeof value == 'string') {
            if(value.match(Ext.ux.form.Spinner.DateStrategy.isNegRe)) {
                value = value.replace(Ext.ux.form.Spinner.DateStrategy.isNegRe, '');
                isNegValue = true;
            }

            if (value.search(/:/) != -1) {
                var parts = value.split(':'),
                    hours = parseInt(parts[0]),
                    minutes = parseInt(parts[1]);

                if (0 > hours) {
                    hours = 0;
                }

                if (0 > minutes) {
                    minutes = 0;
                }

                if (minutes > 0) {
                    value = hours * 60 + minutes;
                } else {
                    value = hours * 60;
                }
            } else if (value > 0) {
                value = value * 60;
            } else {
                this.markInvalid(i18n._('Not a valid time'));
                return;
            }
        }

        if (this.baseUnit == 'seconds') {
            value = value* 60;
        }

        if (isNegValue) {
            value = -1 * value;
        }

        this.setValue(value);
        return value;
    }
});

Ext.ux.form.DurationSpinner.durationRenderer = function(value, config) {
    if(! value || value == '00:00') {
        value = config.emptyOnZero ? '' : '00:00';
    } else if(! value.toString().match(/:/)) {
        if (config.baseUnit == 'seconds') {
            value = value/60;
        }
        var isNegValue = value < 0,
            hours = Math.floor(Math.abs(value) / 60),
            minutes = Math.abs(value) - hours * 60;

        if (hours < 10) {
            hours = '0' + hours;
        }

        if (minutes < 10) {
            minutes = '0' + minutes;
        }

        if (minutes !== 0) {
            value = hours + ':' + minutes;
        } else {
            value = hours + ':00';
        }

        if (isNegValue) {
            value = '- ' + value;
        }
    }
    return value;
};
Ext.reg('durationspinner', Ext.ux.form.DurationSpinner);
