/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
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
    // TODO do we need all of those?
    layout: 'form',
    border: true,
    frame: true,
    labelAlign: 'top',
    autoScroll: true,
    defaults: {
        anchor: '100%',
        labelSeparator: ''
    },
    
    initComponent: function() {
        this.title = _('Alarms');
        
        this.items = this.getFormItems();
        
        Tine.widgets.dialog.AlarmPanel.superclass.initComponent.call(this);
    },
    
    getFormItems: function() {
        
        this.alarmCombo = new Ext.form.ComboBox({
            columnWidth: 0.33,
            fieldLabel: _('Send Alarm'),
            name: 'alarm_time_before',
            typeAhead     : false,
            triggerAction : 'all',
            lazyRender    : true,
            editable      : false,
            mode          : 'local',
            forceSelection: true,
            value: 'none',
            store: [
                ['none', _('None')],
                ['15',  _('15 minutes before')],
                ['30',  _('30 minutes before')],
                ['60',  _('1 hour before')],
                ['120',  _('2 hours before')],
                ['1440',  _('1 day before')]
            ]
        });
        
        return {
            layout: 'column',
            style: 'padding-top: 5px;',
            items: this.alarmCombo
        };
    },
    
    /**
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
        }
    },

    /**
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
        
        // we need to initialze alarms because stringcompare would detect no change of the arrays
        record.set('alarms', '');
        if (alarm != null) {
            record.set('alarms', [alarm]);
        }
    }
});
