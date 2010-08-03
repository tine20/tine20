/* 
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */

Ext.ns('Tine.Calendar');

Tine.Calendar.RrulePanel = Ext.extend(Ext.Panel, {
    
    /**
     * @static
     */
    wkdays: ['SU', 'MO', 'TU', 'WE', 'TH', 'FR', 'SA'],
    /**
     * @property
     */    
    activeRuleCard: null,
    
    layout: 'form',
    frame: true,
    
    initComponent: function() {
        this.app = Tine.Tinebase.appMgr.get('Calendar');
        
        this.title = this.app.i18n._('Recurrances');

        this.defaults = {
            border: false
        };
        
        this.NONEcard = new Ext.Panel({
            freq: 'NONE',
            html: this.app.i18n._('No recurring rule defined')
        });
        this.NONEcard.setRule = Ext.emptyFn;
        this.NONEcard.fillDefaults = Ext.emptyFn;
        this.NONEcard.getRule = function() {
            return null;
        };
        this.NONEcard.isValid = function() {
            return true;
        };
        
        this.DAILYcard = new Tine.Calendar.RrulePanel.DAILYcard({});
        this.WEEKLYcard = new Tine.Calendar.RrulePanel.WEEKLYcard({});
        this.MONTHLYcard = new Tine.Calendar.RrulePanel.MONTHLYcard({});
        this.YEARLYcard = new Tine.Calendar.RrulePanel.YEARLYcard({});
        
        this.ruleCards = new Ext.Panel({
            layout: 'card',
            activeItem: 0,
            items: [
                this.NONEcard,
                this.DAILYcard,
                this.WEEKLYcard,
                this.MONTHLYcard,
                this.YEARLYcard
            ]
        });

        this.idPrefix = Ext.id();
        
        this.items = [{
            xtype: 'toolbar',
            //style: 'background: 0; border: 0; padding-bottom: 5px;',
            style: 'margin-bottom: 5px;',
            
            items: [{
                id: this.idPrefix + 'tglbtn' + 'NONE',
                xtype: 'tbbtnlockedtoggle',
                enableToggle: true,
                //pressed: true,
                text: this.app.i18n._('None'),
                handler: this.onFreqChange.createDelegate(this, ['NONE']),
                toggleGroup: this.idPrefix + 'freqtglgroup'
            }, {
                id: this.idPrefix + 'tglbtn' + 'DAILY',
                xtype: 'tbbtnlockedtoggle',
                enableToggle: true,
                text: this.app.i18n._('Daily'),
                handler: this.onFreqChange.createDelegate(this, ['DAILY']),
                toggleGroup: this.idPrefix + 'freqtglgroup'
            }, {
                id: this.idPrefix + 'tglbtn' + 'WEEKLY',
                xtype: 'tbbtnlockedtoggle',
                enableToggle: true,
                text: this.app.i18n._('Weekly'),
                handler: this.onFreqChange.createDelegate(this, ['WEEKLY']),
                toggleGroup: this.idPrefix + 'freqtglgroup'
            }, {
                id: this.idPrefix + 'tglbtn' + 'MONTHLY',
                xtype: 'tbbtnlockedtoggle',
                enableToggle: true,
                text: this.app.i18n._('Monthly'),
                handler: this.onFreqChange.createDelegate(this, ['MONTHLY']),
                toggleGroup: this.idPrefix + 'freqtglgroup'
            }, {
                id: this.idPrefix + 'tglbtn' + 'YEARLY',
                xtype: 'tbbtnlockedtoggle',
                enableToggle: true,
                text: this.app.i18n._('Yearly'),
                handler: this.onFreqChange.createDelegate(this, ['YEARLY']),
                toggleGroup: this.idPrefix + 'freqtglgroup'
            }]
            
        }, {
            layout: 'form',
            style: 'padding-left: 10px;',
            items: [
                this.ruleCards
            ]
        }];
        
        Tine.Calendar.RrulePanel.superclass.initComponent.call(this);
    },
    
    isValid: function() {
        return this.activeRuleCard.isValid(this.record);
    },
    
    onFreqChange: function(freq) {
        this.ruleCards.layout.setActiveItem(this[freq + 'card']);
        this.ruleCards.layout.layout();
        this.activeRuleCard = this[freq + 'card'];
    },
    
    onRecordLoad: function(record) {
        this.record = record;
        
        if (! this.record.get('editGrant')) {
            this.items.each(function(item) {
                item.setDisabled(true);
            }, this);
        }
        
        this.rrule = this.record.get('rrule');
        
        var dtstart = this.record.get('dtstart');
        if (Ext.isDate(dtstart)) {
            var byday      = Tine.Calendar.RrulePanel.prototype.wkdays[dtstart.format('w')];
            var bymonthday = dtstart.format('j');
            var bymonth    = dtstart.format('n');
            
            this.WEEKLYcard.setRule({
                interval: 1,
                byday: byday
            });
            this.MONTHLYcard.setRule({
                interval: 1,
                byday: '1' + byday,
                bymonthday: bymonthday
            });
            this.YEARLYcard.setRule({
                byday: '1' + byday,
                bymonthday: bymonthday,
                bymonth: bymonth
            });
        }
        
        var freq = this.rrule && this.rrule.freq ? this.rrule.freq : 'NONE';
        
        var freqBtn = Ext.getCmp(this.idPrefix + 'tglbtn' + freq);
        freqBtn.toggle(true);
        
        this.activeRuleCard = this[freq + 'card'];
        this.ruleCards.activeItem = this.activeRuleCard;
        
        this.activeRuleCard.setRule(this.rrule);
    },
    
    onRecordUpdate: function(record) {
        var rrule = this.activeRuleCard.rendered ? this.activeRuleCard.getRule() : this.rrule;
        
        if (! this.rrule && rrule) {
            // mark as new rule to avoid series confirm dlg
            rrule.newrule = true;
        }
        
        record.set('rrule', '');
        record.set('rrule', rrule);
    }
});

Tine.Calendar.RrulePanel.AbstractCard = Ext.extend(Ext.Panel, {
    border: false,
    layout: 'form',
    labelAlign: 'side',
    autoHeight: true,
    
    getRule: function() {
        var until = this.until.getRawValue();
        until = until ? Date.parseDate(until, this.until.format) : null;
        
        
        if (Ext.isDate(until)) {
            // make sure, last recurance is included
            until = until.clearTime(true).add(Date.HOUR, 24).add(Date.SECOND, -1).format(Date.patterns.ISO8601Long);
        }
        
        var rrule = {
            freq    : this.freq,
            interval: this.interval.getValue(),
            //until   : Ext.isDate(until) ? until.format(Date.patterns.ISO8601Long) : null
            until   : until
        };
        
        return rrule;
    },
    
    initComponent: function() {
        this.app = Tine.Tinebase.appMgr.get('Calendar');
        
        this.untilId = Ext.id();
        
        this.until = new Ext.form.DateField({
            requiredGrant : 'editGrant',
            width         : 100,
            emptyText     : this.app.i18n._('forever')
        });
        
        /*
        this.untilCombo = new Ext.form.ComboBox({
            triggerAction : 'all',
            width: 70,
            hideLabel: true,
            value         : false,
            editable      : false,
            mode          : 'local',
            store         : [
                [false,   this.app.i18n._('Forever')  ],
                ['at',    this.app.i18n._('at')     ]
            ]
        });
        */
        var intervalPars = this.intervalString.split('{0}');
        var intervalBeforeString = intervalPars[0];
        var intervalAfterString = intervalPars[1];
        
        this.interval = new Ext.form.NumberField({
            requiredGrant : 'editGrant',
            style         : 'text-align:right;',
            //fieldLabel    : this.intervalBeforeString,
            value         : 1,
            width         : 40
        });
        
        if (! this.items) {
            this.items = [];
        }
        
        if (this.freq != 'YEARLY') {
            this.items = [{
                layout: 'column',
                items: [{
                    width: 70,
                    html: intervalBeforeString
                },
                    this.interval,
                {
                    style: 'padding-top: 2px;',
                    html: intervalAfterString
                }]
            }].concat(this.items);
        }
        
        this.items = this.items.concat({
            layout: 'column',
            style: 'padding-top: 5px;',
            items: [{
                width: 70,
                html: this.app.i18n._('Until')
            }, this.until]
                
        });
        
        Tine.Calendar.RrulePanel.AbstractCard.superclass.initComponent.call(this);
    },
    
    isValid: function(record) {
        var until = this.until.getValue();
        if (Ext.isDate(until) && Ext.isDate(record.get('dtstart'))) {
            if (until.getTime() < record.get('dtstart').getTime()) {
                this.until.markInvalid(this.app.i18n._('Until has to be after event start'));
                return false;
            }
        }
        
        return true;
    },
    
    setRule: function(rrule) {
        this.interval.setValue(rrule.interval);
        var date = Date.parseDate(rrule.until, Date.patterns.ISO8601Long);
        this.until.value = date;
    }
});

Tine.Calendar.RrulePanel.DAILYcard = Ext.extend(Tine.Calendar.RrulePanel.AbstractCard, {
    
    freq: 'DAILY',
    
    initComponent: function() {
        this.app = Tine.Tinebase.appMgr.get('Calendar');
        
        this.intervalString = this.app.i18n._('Every {0}. Day');
        
        Tine.Calendar.RrulePanel.DAILYcard.superclass.initComponent.call(this);
    }
});

Tine.Calendar.RrulePanel.WEEKLYcard = Ext.extend(Tine.Calendar.RrulePanel.AbstractCard, {
    
    freq: 'WEEKLY',
    
    getRule: function() {
        var rrule = Tine.Calendar.RrulePanel.WEEKLYcard.superclass.getRule.call(this);
        
        var bydayArray = [];
        this.byday.items.each(function(cb) {
            if (cb.checked) {
                bydayArray.push(cb.name);
            }
        }, this);
        
        rrule.byday = bydayArray.join();
        if (! rrule.byday) {
            rrule.byday = this.byDayValue;
        }
        return rrule;
    },
    
    initComponent: function() {
        this.app = Tine.Tinebase.appMgr.get('Calendar');
        
        this.intervalString = this.app.i18n._('Every {0}. Week at');
        
        var bydayItems = [];
        for (var i=0,d; i<7; i++) {
            d = (i+Ext.DatePicker.prototype.startDay)%7
            bydayItems.push({
                boxLabel: Date.dayNames[d],
                name: Tine.Calendar.RrulePanel.prototype.wkdays[d]
            })
        }
        
        this.byday = new Ext.form.CheckboxGroup({
            requiredGrant : 'editGrant',
            style: 'padding-top: 5px;',
            hideLabel: true,
            items: bydayItems
        });
        
        this.items = [this.byday];
        
        Tine.Calendar.RrulePanel.WEEKLYcard.superclass.initComponent.call(this);
    },
    
    setRule: function(rrule) {
        Tine.Calendar.RrulePanel.WEEKLYcard.superclass.setRule.call(this, rrule);
        
        if (rrule.byday) {
            this.byDayValue = rrule.byday;
            
            var bydayArray = rrule.byday.split(',');
            
            if (Ext.isArray(this.byday.items)) {
                // on initialisation items are not renderd
                Ext.each(this.byday.items, function(cb) {
                    if (bydayArray.indexOf(cb.name) != -1) {
                        cb.checked = true;
                    }
                }, this);
            } else {
                // after items are rendered
                this.byday.items.each(function(cb) {
                    if (bydayArray.indexOf(cb.name) != -1) {
                        cb.setValue(true);
                    }
                }, this);
            }
        }
    }
});

Tine.Calendar.RrulePanel.MONTHLYcard = Ext.extend(Tine.Calendar.RrulePanel.AbstractCard, {
    
    freq: 'MONTHLY',
    
    getRule: function() {
        var rrule = Tine.Calendar.RrulePanel.MONTHLYcard.superclass.getRule.call(this);
        
        if (this.bydayRadio.checked) {
            rrule.byday = this.wkNumber.getValue() + this.wkDay.getValue();
        } else {
            rrule.bymonthday = this.bymonthdayday.getValue();
        }
        
        return rrule;
    },
    
    initComponent: function() {
        this.app = Tine.Tinebase.appMgr.get('Calendar');
        
        this.intervalString = this.app.i18n._('Every {0}. Month');
        
        this.idPrefix = Ext.id();
        
        this.bydayRadio = new Ext.form.Radio({
            hideLabel: true,
            boxLabel: this.app.i18n._('at the'), 
            name: this.idPrefix + 'byRadioGroup', 
            inputValue: 'BYDAY',
            checked: true,
            listeners: {
                check: this.onByRadioCheck.createDelegate(this)
            }
        });

        this.wkNumber = new Ext.form.ComboBox({
            requiredGrant : 'editGrant',
            width: 80,
            listWidth: 80,
            triggerAction : 'all',
            hideLabel     : true,
            value         : 1,
            editable      : false,
            mode          : 'local',
            store         : [
                [1,  this.app.i18n._('first')  ],
                [2,  this.app.i18n._('second') ],
                [3,  this.app.i18n._('third')  ],
                [4,  this.app.i18n._('fourth') ],
                [-1, this.app.i18n._('last')   ]
            ]
        });
        
        var wkdayItems = [];
        for (var i=0,d; i<7; i++) {
            d = (i+Ext.DatePicker.prototype.startDay)%7
            Tine.Calendar.RrulePanel.prototype.wkdays[d];
            wkdayItems.push([Tine.Calendar.RrulePanel.prototype.wkdays[d], Date.dayNames[d]]);
        }
        
        this.wkDay = new Ext.form.ComboBox({
            requiredGrant : 'editGrant',
            width         : 100,
            listWidth     : 100,
            triggerAction : 'all',
            hideLabel     : true,
            value         : Tine.Calendar.RrulePanel.prototype.wkdays[Ext.DatePicker.prototype.startDay],
            editable      : false,
            mode          : 'local',
            store         : wkdayItems
        });
        
        this.bymonthdayRadio = new Ext.form.Radio({
            requiredGrant : 'editGrant',
            hideLabel     : true,
            boxLabel      : this.app.i18n._('at the'), 
            name          : this.idPrefix + 'byRadioGroup', 
            inputValue    : 'BYMONTHDAY',
            listeners     : {
                check: this.onByRadioCheck.createDelegate(this)
            }
        });
        
        this.bymonthdayday = new Ext.form.NumberField({
            requiredGrant : 'editGrant',
            hideLabel     : true,
            width         : 40,
            value         : 1,
            disabled      : true
        });
        
        this.items = [{
            html: '<div style="padding-top: 5px; padding-left: 5px">' +
                    '<div style="position: relative;">' +
                        '<table><tr>' +
                            '<td style="position: relative;" width="60" id="' + this.idPrefix + 'bydayradio"></td>' +
                            '<td width="100" id="' + this.idPrefix + 'bydaywknumber"></td>' +
                            '<td width="110" id="' + this.idPrefix + 'bydaywkday"></td>' +
                        '</tr></table>' +
                    '</div>' +
                    '<div style="position: relative;">' +
                        '<table><tr>' +
                            '<td width="60" id="' + this.idPrefix + 'bymonthdayradio"></td>' +
                            '<td width="40" id="' + this.idPrefix + 'bymonthdayday"></td>' +
                            '<td>.</td>' +
                         '</tr></table>' +
                    '</div>' +
                '</div>',
            listeners: {
                scope: this,
                render: this.onByRender
            }
        }];
        
        Tine.Calendar.RrulePanel.MONTHLYcard.superclass.initComponent.call(this);
    },
    
    onByRadioCheck: function(radio, checked) {
        switch(radio.inputValue) {
            case 'BYDAY':
                this.bymonthdayday.setDisabled(checked);
                break;
            case 'BYMONTHDAY':
                this.wkNumber.setDisabled(checked);
                this.wkDay.setDisabled(checked);
                break;
        }
    },
    
    onByRender: function() {
        var bybayradioel = Ext.get(this.idPrefix + 'bydayradio');
        var bybaywknumberel = Ext.get(this.idPrefix + 'bydaywknumber');
        var bybaywkdayel = Ext.get(this.idPrefix + 'bydaywkday');
        
        var bymonthdayradioel = Ext.get(this.idPrefix + 'bymonthdayradio');
        var bymonthdaydayel = Ext.get(this.idPrefix + 'bymonthdayday');
        
        if (! (bybayradioel && bymonthdayradioel)) {
            return this.onByRender.defer(100, this, arguments);
        }
        
        this.bydayRadio.render(bybayradioel);
        this.wkNumber.render(bybaywknumberel);
        this.wkNumber.wrap.setWidth(80);
        this.wkDay.render(bybaywkdayel);
        this.wkDay.wrap.setWidth(100);
        
        this.bymonthdayRadio.render(bymonthdayradioel);
        this.bymonthdayday.render(bymonthdaydayel);
    },
    
    setRule: function(rrule) {
        Tine.Calendar.RrulePanel.MONTHLYcard.superclass.setRule.call(this, rrule);
        
        if (rrule.byday) {
            this.bydayRadio.setValue(true);
            this.bymonthdayRadio.setValue(false);
            this.onByRadioCheck(this.bydayRadio, true);
            this.onByRadioCheck(this.bymonthdayRadio, false);
            
            var parts = rrule.byday.match(/([\-\d]{1,2})([A-Z]{2})/);
            this.wkNumber.setValue(parts[1]);
            this.wkDay.setValue(parts[2]);
            
        }
        
        if (rrule.bymonthday) {
            this.bydayRadio.setValue(false);
            this.bymonthdayRadio.setValue(true);
            this.onByRadioCheck(this.bydayRadio, false);
            this.onByRadioCheck(this.bymonthdayRadio, true);
            
            this.bymonthdayday.setValue(rrule.bymonthday);
        }

    }
    
});

Tine.Calendar.RrulePanel.YEARLYcard = Ext.extend(Tine.Calendar.RrulePanel.AbstractCard, {
    
    freq: 'YEARLY',
    
    getRule: function() {
        var rrule = Tine.Calendar.RrulePanel.MONTHLYcard.superclass.getRule.call(this);
        
        if (this.bydayRadio.checked) {
            rrule.byday = this.wkNumber.getValue() + this.wkDay.getValue();
        } else {
            rrule.bymonthday = this.bymonthdayday.getValue();
        }
        
        rrule.bymonth = this.bymonth.getValue();
        return rrule;
    },
    
    initComponent: function() {
        this.app = Tine.Tinebase.appMgr.get('Calendar');
        
        this.intervalString = this.app.i18n._('Every {0}. Year');
        
        this.idPrefix = Ext.id();
        
        this.bydayRadio = new Ext.form.Radio({
            requiredGrant : 'editGrant',
            hideLabel     : true,
            boxLabel      : this.app.i18n._('at the'), 
            name          : this.idPrefix + 'byRadioGroup', 
            inputValue    : 'BYDAY',
            listeners     : {
                check: this.onByRadioCheck.createDelegate(this)
            }
        });

        this.wkNumber = new Ext.form.ComboBox({
            requiredGrant : 'editGrant',
            width         : 80,
            listWidth     : 80,
            triggerAction : 'all',
            hideLabel     : true,
            value         : 1,
            editable      : false,
            mode          : 'local',
            disabled      : true,
            store         : [
                [1,  this.app.i18n._('first')  ],
                [2,  this.app.i18n._('second') ],
                [3,  this.app.i18n._('third')  ],
                [4,  this.app.i18n._('fourth') ],
                [-1, this.app.i18n._('last')   ]
            ]
        });
        
        var wkdayItems = [];
        for (var i=0,d; i<7; i++) {
            d = (i+Ext.DatePicker.prototype.startDay)%7
            Tine.Calendar.RrulePanel.prototype.wkdays[d];
            wkdayItems.push([Tine.Calendar.RrulePanel.prototype.wkdays[d], Date.dayNames[d]]);
        }
        
        this.wkDay = new Ext.form.ComboBox({
            requiredGrant : 'editGrant',
            width         : 100,
            listWidth     : 100,
            triggerAction : 'all',
            hideLabel     : true,
            value         : Tine.Calendar.RrulePanel.prototype.wkdays[Ext.DatePicker.prototype.startDay],
            editable      : false,
            mode          : 'local',
            store         : wkdayItems,
            disabled      : true
        });
        
        this.bymonthdayRadio = new Ext.form.Radio({
            requiredGrant : 'editGrant',
            hideLabel     : true,
            boxLabel      : this.app.i18n._('at the'), 
            name          : this.idPrefix + 'byRadioGroup', 
            inputValue    : 'BYMONTHDAY',
            checked       : true,
            listeners     : {
                check: this.onByRadioCheck.createDelegate(this)
            }
        });
        
        this.bymonthdayday = new Ext.form.NumberField({
            requiredGrant : 'editGrant',
            hideLabel     : true,
            width         : 40,
            value         : 1
        });
        
        var monthItems = [];
        for (var i=0; i<Date.monthNames.length; i++) {
            monthItems.push([i+1, Date.monthNames[i]]);
        }
        
        this.bymonth = new Ext.form.ComboBox({
            requiredGrant : 'editGrant',
            width         : 100,
            listWidth     : 100,
            triggerAction : 'all',
            hideLabel     : true,
            value         : 1,
            editable      : false,
            mode          : 'local',
            store         : monthItems
        });
        
        this.items = [{
            html: '<div style="padding-top: 5px;">' +
                    '<div style="position: relative;">' +
                        '<table><tr>' +
                            '<td style="position: relative;" width="65" id="' + this.idPrefix + 'bydayradio"></td>' +
                            '<td width="100" id="' + this.idPrefix + 'bydaywknumber"></td>' +
                            '<td width="110" id="' + this.idPrefix + 'bydaywkday"></td>' +
                            //'<td style="padding-left: 10px">' + this.app.i18n._('of') + '</td>' +
                        '</tr></table>' +
                    '</div>' +
                    '<div style="position: relative;">' +
                        '<table><tr>' +
                            '<td width="65" id="' + this.idPrefix + 'bymonthdayradio"></td>' +
                            '<td width="40" id="' + this.idPrefix + 'bymonthdayday"></td>' +
                            '<td>.</td>' +
                         '</tr></table>' +
                    '</div>' +
                    '<div style="position: relative;">' +
                        '<table><tr>' +
                            '<td width="48" style="padding-left: 17px">' + this.app.i18n._('of') + '</td>' +
                            '<td width="100" id="' + this.idPrefix + 'bymonth"></td>' +
                         '</tr></table>' +
                    '</div>' +
                '</div>',
            listeners: {
                scope: this,
                render: this.onByRender
            }
        }];
        Tine.Calendar.RrulePanel.YEARLYcard.superclass.initComponent.call(this);
    },
    
    onByRadioCheck: function(radio, checked) {
        switch(radio.inputValue) {
            case 'BYDAY':
                this.bymonthdayday.setDisabled(checked);
                break;
            case 'BYMONTHDAY':
                this.wkNumber.setDisabled(checked);
                this.wkDay.setDisabled(checked);
                break;
        }
    },
    
    onByRender: function() {
        var bybayradioel = Ext.get(this.idPrefix + 'bydayradio');
        var bybaywknumberel = Ext.get(this.idPrefix + 'bydaywknumber');
        var bybaywkdayel = Ext.get(this.idPrefix + 'bydaywkday');
        
        var bymonthdayradioel = Ext.get(this.idPrefix + 'bymonthdayradio');
        var bymonthdaydayel = Ext.get(this.idPrefix + 'bymonthdayday');

        var bymonthel = Ext.get(this.idPrefix + 'bymonth');
        
        if (! (bybayradioel && bymonthdayradioel)) {
            return this.onByRender.defer(100, this, arguments);
        }
        
        this.bydayRadio.render(bybayradioel);
        this.wkNumber.render(bybaywknumberel);
        this.wkNumber.wrap.setWidth(80);
        this.wkDay.render(bybaywkdayel);
        this.wkDay.wrap.setWidth(100);
        
        this.bymonthdayRadio.render(bymonthdayradioel);
        this.bymonthdayday.render(bymonthdaydayel);
        
        this.bymonth.render(bymonthel);
        this.bymonth.wrap.setWidth(100);
    },
    
    setRule: function(rrule) {
        Tine.Calendar.RrulePanel.MONTHLYcard.superclass.setRule.call(this, rrule);
        
        if (rrule.byday) {
            this.bydayRadio.setValue(true);
            this.bymonthdayRadio.setValue(false);
            this.onByRadioCheck(this.bydayRadio, true);
            this.onByRadioCheck(this.bymonthdayRadio, false);
            
            var parts = rrule.byday.match(/([\-\d]{1,2})([A-Z]{2})/);
            this.wkNumber.setValue(parts[1]);
            this.wkDay.setValue(parts[2]);
            
        }
        
        if (rrule.bymonthday) {
            this.bydayRadio.setValue(false);
            this.bymonthdayRadio.setValue(true);
            this.onByRadioCheck(this.bydayRadio, false);
            this.onByRadioCheck(this.bymonthdayRadio, true);
            
            this.bymonthdayday.setValue(rrule.bymonthday);
        }
        
        this.bymonth.setValue(rrule.bymonth);

    }
});