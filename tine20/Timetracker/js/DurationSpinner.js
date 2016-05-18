/**
 * Tine 2.0
 * 
 * @package     Timetracker
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2015 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.ns('Tine.Timetracker');

/**
 * handles minutes to time conversions
 * @class Tine.Timetracker.DurationSpinner
 * @extends Ext.ux.form.Spinner
 */
Tine.Timetracker.DurationSpinner = Ext.extend(Ext.ux.form.Spinner,  {
    
    /**
     * Set to empty value if value equals 0
     * @cfg emptyOnZero
     */
    emptyOnZero: null,
    
    initComponent: function() {
        this.preventMark = false;
        this.strategy = new Ext.ux.form.Spinner.TimeStrategy({
            incrementValue : 15
        });
        this.format = this.strategy.format;
    },

    setValue: function(value) {
        if(! value || value == '00:00') {
            value = this.emptyOnZero ? '' : '00:00';
        } else if(! value.toString().match(/:/)) {
            var hours = Math.floor(value / 60),
                minutes = value - hours * 60;

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
        }
        
        Tine.Timetracker.DurationSpinner.superclass.setValue.call(this, value);
    },
    
    validateValue: function(value) {
        if (value.search(/:/) != -1) {
            var parts = value.split(':'),
                hours = parseInt(parts[0]),
                minutes = parseInt(parts[1]);

            return 'NaN' != hours && 'NaN' != minutes;
        }
    },

    getValue: function() {
        var value = Tine.Timetracker.DurationSpinner.superclass.getValue.call(this);
        value = value.replace(',', '.');

        if(value && typeof value == 'string') {
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

        this.setValue(value);
        return value;
    }
});

Ext.reg('tinedurationspinner', Tine.Timetracker.DurationSpinner);
