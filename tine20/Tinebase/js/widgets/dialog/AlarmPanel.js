/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 * TODO         add custom alarm datetime
 * TODO         make multiple alarms possible
 * TODO         add custom 'alarm time before' inputfield + combo (with min/day/week/...)
 * TODO         add combo with 'alarm for' single attender / all attendee (extend this panel in calendar?)
 * TODO         use Tine.Tinebase.Model.Alarm?
 */
 
Ext.ns('Tine.widgets', 'Tine.widgets.dialog');

/**
 * Alarm panel
 */
Tine.widgets.dialog.AlarmPanel = Ext.extend(Ext.Panel, {
    
    //private
    layout      : 'form',
    border      : true,
    frame       : true,
    autoScroll  : true,
    autoHeight  : true,
    
    initComponent: function() {
        this.title = _('Alarms');
        this.items = this.getFormItems();
        
        Tine.widgets.dialog.AlarmPanel.superclass.initComponent.call(this);
    },
    
    getFormItems: function() {
        
        this.customDateField = new Ext.ux.form.DateTimeField({
            // TODO fix the height of the date + time fields
            name        : 'alarm_date',
            disabled    : true,
            columnWidth : 0.66
        });
        
        this.alarmCombo = new Ext.form.ComboBox({
            columnWidth     : 0.33,
            name            : 'alarm_time_before',
            typeAhead       : false,
            triggerAction   : 'all',
            lazyRender      : true,
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
                ['1440',    _('1 day before')]
                //['custom',  _('Custom datetime')]
            ],
            listeners       : {
                scope: this,
                select: function(combo, record, index) {
                    // enable datetime field if custom is selected
                    this.customDateField.setDisabled(record.data.field1 != 'custom');
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
            var alarm = record.get('alarms')[0];
            this.alarmCombo.setValue(alarm.minutes_before);
            
            // TODO get custom date if set (and enable field)
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
            alarm.minutes_before = comboValue;
        }
        
        // TODO set custom date if set
        
        // we need to initialze alarms because stringcompare would detect no change of the arrays
        record.set('alarms', '');
        if (alarm != null) {
            record.set('alarms', [alarm]);
        }
    }
});
