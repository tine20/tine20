/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 */
 
Ext.ns('Tine.widgets', 'Tine.widgets.dialog');

/**
 * Alarm Panel
 * 
 * @namespace   Tine.widgets.dialog
 * @class       Tine.widgets.dialog.AlarmPanel
 * @extends     Ext.Panel
 * 
 * @TODO add validation (alarm after alarmtime)
 */
Tine.widgets.dialog.AlarmPanel = Ext.extend(Ext.Panel, {
    
    /**
     * @cfg {string} name of alarm field
     */
    recordAlarmField: 'dtstart',
    
    //private
    layout      : 'fit',
    border      : true,
    frame       : true,
    
    
    initComponent: function() {
        this.title = _('Alarms');
        
        this.alarmOptions = [
            ['0',       _('0 minutes before')],
            ['5',       _('5 minutes before')],
            ['15',      _('15 minutes before')],
            ['30',      _('30 minutes before')],
            ['60',      _('1 hour before')],
            ['120',     _('2 hours before')],
            ['720',     _('12 hours before')],
            ['1440',    _('1 day before')],
            ['2880',    _('2 days before')],
            ['custom',  _('Custom Datetime')]
        ];
        
        this.items = this.alarmGrid = new Tine.widgets.grid.QuickaddGridPanel({
            layout: 'fit',
            autoExpandColumn: 'alarm_time',
            quickaddMandatory: 'minutes_before',
            frame: false,
            recordClass: Tine.Tinebase.Model.Alarm,
            onNewentry: this.onNewentry.createDelegate(this),
            cm: new Ext.grid.ColumnModel([{
                id: 'minutes_before', 
                header: _('Alarm Time'), 
                dataIndex: 'minutes_before', 
                width: 200, 
                hideable: false, 
                sortable: true,
                quickaddField: new Ext.form.ComboBox({
                    triggerAction   : 'all',
                    lazyRender      : false,
                    editable        : false,
                    mode            : 'local',
                    forceSelection  : true,
                    store: this.alarmOptions,
                    listeners       : {
                        scope: this,
                        beforeselect: function(combo, record, index) {
                            var alarmFieldDate = this.record ? this.record.get(this.recordAlarmField) : null;
                            
                            this.alarmTimeQuickAdd.setDisabled(record.data.field1 != 'custom');
                            
                            // preset a usefull value
                            if (Ext.isDate(alarmFieldDate) && record.data.field1 == 'custom') {
                                this.alarmTimeQuickAdd.setValue(alarmFieldDate.add(Date.MINUTE, -1 * Ext.isNumber(combo.getValue()) ? combo.getValue() : 0));
                            }
                            
                            if (record.data.field1 != 'custom') {
                                this.alarmTimeQuickAdd.setValue();
                            }
                        }
                    }
                }),
                editor: new Ext.form.ComboBox({
                    triggerAction   : 'all',
                    lazyRender      : false,
                    editable        : false,
                    mode            : 'local',
                    forceSelection  : true,
                    allowBlank      : false,
                    expandOnFocus   : true,
                    blurOnSelect    : true,
                    store: this.alarmOptions
                }),
                renderer: this.minutesBeforeRenderer.createDelegate(this)
            }, {
                id: 'alarm_time', 
                dataIndex: 'alarm_time', 
                hideable: false, 
                sortable: false,
                renderer: this.alarmTimeRenderer.createDelegate(this),
                quickaddField: this.alarmTimeQuickAdd = new Ext.ux.form.DateTimeField({
                    allowBlank      : true
                }),
                editor: new Ext.ux.form.DateTimeField({
                    allowBlank      : false
                })
            }])
        });
        
        this.alarmGrid.on('beforeedit', this.onBeforeEdit, this);
        this.alarmGrid.on('afteredit', this.onAfterEdit, this);
        Tine.widgets.dialog.AlarmPanel.superclass.initComponent.call(this);
        
    },
    
    onNewentry: function(recordData) {
        if (recordData.minutes_before == 'custom' && ! Ext.isDate(recordData.alarm_time)) {
            return false;
        }
        
        Tine.widgets.grid.QuickaddGridPanel.prototype.onNewentry.apply(this.alarmGrid, arguments);
        this.alarmGrid.store.sort('minutes_before', 'ASC');
    },
    
    onBeforeEdit: function(o) {
        if (o.field == 'alarm_time') {
            var minutesBefore = o.record.get('minutes_before'),
                alarmTime = o.record.get('alarm_time');
            
            // allow status setting if status authkey is present
            if (minutesBefore != 'custom') {
                o.cancel = true;
            } else if (alarmTime && ! Ext.isDate(alarmTime)) {
                var alarmDate = Date.parseDate(alarmTime, Date.patterns.ISO8601Long);
                o.record.set('alarm_time', '');
                o.record.set('alarm_time', alarmDate);
            }
        }
    },
    
    onAfterEdit: function(o) {
        if (o.record.get('minutes_before') == 'custom' && ! Ext.isDate(o.record.get('alarm_time'))) {
            var alarmFieldDate = this.record ? this.record.get(this.recordAlarmField) : null;
            
            o.record.set('alarm_time', Ext.isDate(alarmFieldDate) ? alarmFieldDate.clone() : new Date());
        }
        
        if (o.record.get('minutes_before') != 'custom') {
            o.record.set('alarm_time', '');
        }
        
        this.alarmGrid.store.sort('minutes_before', 'ASC');
    },
    
    minutesBeforeRenderer: function(value) {
        var string = null;
        Ext.each(this.alarmOptions, function(opt) {
            if (opt[0] == value) {
                string = opt[1];
                return false;
            }
        });
        
        if (! string ) {
            string = String.format(_('{0} minutes before'), value);
        }
        
        return string;
    },
    
    alarmTimeRenderer: function(value, metaData, record) {
        return record.get('minutes_before') == 'custom' ? Tine.Tinebase.common.dateTimeRenderer.apply(this, arguments) : '';
    },
    
    /**
     * on record load event
     * 
     * @param {Object} record
     */
    onRecordLoad: function(record) {
        this.record = record;
        this.alarmGrid.store.removeAll();
        this.alarmGrid.setStoreFromArray(record.get('alarms') || []);
        
        this.alarmGrid.store.sort('minutes_before', 'ASC');
    },

    /**
     * on record update event
     * 
     * @param {Object} record
     */
    onRecordUpdate: function(record) {
        // we need to initialze alarms because stringcompare would detect no change of the arrays
        record.set('alarms', '');
        record.set('alarms', this.alarmGrid.getFromStoreAsArray());
    },
    
    /**
     * disable contents not panel
     */
    setDisabled: function(v) {
        this.el[v ? 'mask' : 'unmask']();
    }
});

