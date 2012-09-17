/**
 * Tine 2.0
 * 
 * @package     Timetracker
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
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
            var time = new Date(0);
            var hours = Math.floor(value / 60);
            var minutes = value - hours * 60;
            
            time.setHours(hours);
            time.setMinutes(minutes);
            
            value = Ext.util.Format.date(time, this.format);
        }
        
        Tine.Timetracker.DurationSpinner.superclass.setValue.call(this, value);
    },
    
    validateValue: function(value) {
        var time = Date.parseDate(value, this.format);
        return Ext.isDate(time);
    },
    
    getValue: function() {
        var value = Tine.Timetracker.DurationSpinner.superclass.getValue.call(this);
        value = value.replace(',', '.');
        
        if(value && typeof value == 'string') {
            if (value.search(/:/) != -1) {
                var parts = value.split(':');
                parts[0] = parts[0].length == 1 ? '0' + parts[0] : parts[0];
                parts[1] = parts[1].length == 1 ? '0' + parts[1] : parts[1];
                value = parts.join(':');
                
                var time = Date.parseDate(value, this.format);
                if (! time) {
                    this.markInvalid(_('Not a valid time'));
                    return;
                } else {
                    value = time.getHours() * 60 + time.getMinutes();
                }
            } else if (value > 0) {
                if (value < 24) {
                    value = value * 60;
                }
            } else {
                this.markInvalid(_('Not a valid time'));
                return;
            }
        }
        this.setValue(value);
        return value;
    }
});

Ext.reg('tinedurationspinner', Tine.Timetracker.DurationSpinner);
