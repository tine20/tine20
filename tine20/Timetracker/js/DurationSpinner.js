/**
 * Tine 2.0
 * 
 * @package     Timetracker
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
 
Ext.ns('Tine.Timetracker');

/**
 * handles minutes to time conversions
 * @class Tine.Timetracker.DurationSpinner
 * @extends Ext.ux.form.Spinner
 */
Tine.Timetracker.DurationSpinner = Ext.extend(Ext.ux.form.Spinner,  {
    
    initComponent: function() {
        this.strategy = new Ext.ux.form.Spinner.TimeStrategy({
            incrementValue : 15
        });
        
        this.format = this.strategy.format;
    },
    
    setValue: function(value) {
        if(! value.match(/:/)){
            var time = new Date(0);
            var hours = Math.floor(value / 60);
            var minutes = value - hours * 60;
            
            time.setHours(hours);
            time.setMinutes(minutes);
            
            value = Ext.util.Format.date(time, this.format);
        }

        Tine.Timetracker.DurationSpinner.superclass.setValue.call(this, value);
    },
    
    getValue: function() {
        var value = Tine.Timetracker.DurationSpinner.superclass.getValue.call(this);
        if(value && typeof value == 'string') {
        	if (value.search(/:/) != -1) {
                var time = Date.parseDate(value, this.format);
                value = time.getHours() * 60 + time.getMinutes();
        	} else {
        		value = value * 60;
        	}
        }
        
        return value;
    }
});

Ext.reg('tinedurationspinner', Tine.Timetracker.DurationSpinner);