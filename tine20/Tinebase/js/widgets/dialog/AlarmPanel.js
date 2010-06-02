/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 * TODO         make multiple alarms possible
 * TODO         add custom 'alarm time before' inputfield + combo (with min/day/week/...)
 * TODO         add combo with 'alarm for' single attender / all attendee (extend this panel in calendar?)
 * TODO         use Tine.Tinebase.Model.Alarm?
 */
 
Ext.ns('Tine.widgets', 'Tine.widgets.dialog');

/**
 * Alarm Panel
 * 
 * @namespace   Tine.widgets.dialog
 * @class       Tine.widgets.dialog.AlarmPanel
 * @extends     Ext.Panel
 */
Tine.widgets.dialog.AlarmPanel = Ext.extend(Ext.Panel, {
    
    //private
    layout      : 'form',
    border      : true,
    frame       : true,
    autoScroll  : true,
    
    initComponent: function() {
        this.title = _('Alarms');
        this.items = this.getFormItems();
        
        Tine.widgets.dialog.AlarmPanel.superclass.initComponent.call(this);
    },
    
    getFormItems: function() {
        
        this.customDateField = new Ext.ux.form.DateTimeField({
	        fieldLabel  : _('Custom Datetime'),
            lazyRender  : false,
            name        : 'alarm_date',
            width       : 300,
            style       : 'margin-left: 25px',
            hidden      : true
        });
        
        this.alarmCombo = new Ext.form.ComboBox({
            columnWidth     : .33,
	        fieldLabel      : _('Send Alarm'),
            name            : 'alarm_time_before',
            typeAhead       : false,
            triggerAction   : 'all',
            lazyRender      : false,
            editable        : false,
            mode            : 'local',
            forceSelection  : true,
            value           : 'none',
            store           : [
                ['none',    _('None')],
                ['0',       _('0 minutes before')],
                ['15',      _('15 minutes before')],
                ['30',      _('30 minutes before')],
                ['60',      _('1 hour before')],
                ['120',     _('2 hours before')],
                ['1440',    _('1 day before')],
                ['custom',  _('Custom datetime')]
            ],
            listeners       : {
                scope: this,
                select: function(combo, record, index) {
                    // enable datetime field if custom is selected
                    if (record.data.field1 === 'custom') {
                        this.customDateField.show();
                        // fix strange GC issue
                        this.customDateField.el.applyStyles('display: inline;')
                    } else {
                        this.customDateField.hide();
                    }
                },
                beforeselect: function(combo, record, index) {
                    // preset a usefull value
                    this.dtField = 'dtstart';
                    if (this.dtField && record.data.field1 === 'custom') {
                        var date = this.record.get(this.dtField);
                        if (! Ext.isDate(date)) {
                            date = new Date().add(Date.DAY, 1);
                        }
                        this.customDateField.setValue(date.add(Date.MINUTE, -1 * Ext.isNumber(combo.getValue()) ? combo.getValue() : 0));
                    }
                }
            }
        });
        
        return {
            layout: 'column',
            items: [
                this.alarmCombo,
                this.customDateField
            ]
        };
    },
    
    /**
     * on record load event
     * 
     * @param {Object} record
     */
    onRecordLoad: function(record) {
        this.record = record;
        
        // set combo
        if (record.get('alarms') && record.get('alarms').length > 0) {
            // only get first alarm at the moment
            //var alarm = new Tine.Tinebase.Model.Alarm(record.get('alarms')[0]);
            var alarm = record.get('alarms')[0];
            var options = Ext.util.JSON.decode(alarm.options);
            
            if (options && options.custom /*&& options.custom == true*/) {
                var date = Date.parseDate(alarm.alarm_time, Date.patterns.ISO8601Long);
                // get custom date if set (and enable field)
                this.customDateField.setValue(date);
                this.customDateField.show();
                this.alarmCombo.setValue('custom');
            } else {
                this.customDateField.hide();
                this.alarmCombo.setValue(alarm.minutes_before);
            }
        }
    },

    /**
     * on record update event
     * 
     * @param {Object} record
     */
    onRecordUpdate: function(record) {
        
        var comboValue = this.alarmCombo.getValue();
        var alarm = null;
        
        if (comboValue != 'none') {
            // update or create
            alarm = (record.get('alarms') && record.get('alarms').length > 0) ? record.get('alarms')[0] : {};
            if (comboValue == 'custom') {
                // set custom date if set
                alarm.alarm_time = this.customDateField.getValue();
            }
            alarm.minutes_before = comboValue;
        }
        
        // we need to initialze alarms because stringcompare would detect no change of the arrays
        record.set('alarms', '');
        if (alarm != null) {
            record.set('alarms', [alarm]);
        }
    }
});
