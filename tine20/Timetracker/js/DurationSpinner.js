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
        if(typeof value != 'string'){
            var miliseconds = value * 60000;
            var time = new Date(miliseconds);
            
            value = Ext.util.Format.date(time,this.format);
        }

        Tine.Timetracker.DurationSpinner.superclass.setValue.call(this, value);
    },
    
    getValue: function() {
        var value = Tine.Timetracker.DurationSpinner.superclass.getValue.call(this);
        if(value && typeof value == 'string') {
            var time = Date.parseDate(value, this.format);
            value = time.getHours() * 60 + time.getMinutes();
        }
        
        return value;
    }
});

Ext.reg('tinedurationspinner', Tine.Timetracker.DurationSpinner);