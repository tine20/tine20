/* 
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2015 Metaways Infosystems GmbH (http://www.metaways.de)
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
    
    /**
     * the event edit dialog (parent)
     * @type Tine.Calendar.EventEditDialog
     */
    eventEditDialog: null,
    
    layout: 'form',
    frame: true,
    canonicalName: 'RecurrenceConfig',
    
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
        
        this.DAILYcard = new Tine.Calendar.RrulePanel.DAILYcard({rrulePanel: this});
        this.WEEKLYcard = new Tine.Calendar.RrulePanel.WEEKLYcard({rrulePanel: this});
        this.MONTHLYcard = new Tine.Calendar.RrulePanel.MONTHLYcard({rrulePanel: this});
        this.YEARLYcard = new Tine.Calendar.RrulePanel.YEARLYcard({rrulePanel: this});
        
        this.ruleCards = new Ext.Panel({
            layout: 'card',
            baseCls: 'ux-arrowcollapse',
            cls: 'ux-arrowcollapse-plain',
            collapsible: true,
            collapsed: false,
            activeItem: 0,
            listeners: {
                scope: this,
                collapse: this.doLayout,
                expand: this.doLayout
            },
            //style: 'padding: 10px 0 0 20px;',
            title: this.app.i18n._('Details'),
            items: [
                this.NONEcard,
                this.DAILYcard,
                this.WEEKLYcard,
                this.MONTHLYcard,
                this.YEARLYcard
            ]
        });

        this.idPrefix = Ext.id();
        
        this.tbar = [{
            id: this.idPrefix + 'tglbtn' + 'NONE',
            xtype: 'tbbtnlockedtoggle',
            enableToggle: true,
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
        }];
        
        this.items = [
            this.ruleCards
        ];

        this.eventEditDialog.on('dtStartChange', function(jsonData) {
            var data = Ext.decode(jsonData),
                dtstart = Date.parseDate(data.newValue, Date.patterns.ISO8601Long);

            this.initRrule(dtstart);
        }, this);
        Tine.Calendar.RrulePanel.superclass.initComponent.call(this);
    },

    initRrule: function(dtstart) {
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
    },

    isValid: function() {
        return this.activeRuleCard.isValid(this.record);
    },
    
    onFreqChange: function(freq) {
        this.ruleCards.layout.setActiveItem(this[freq + 'card']);
        this.ruleCards.layout.layout();
        this.activeRuleCard = this[freq + 'card'];
    },
    
    /**
     * disable contents not panel
     */
    setDisabled: function(v) {
        this.getTopToolbar().items.each(function(item) {
            item.setDisabled(v);
        }, this);
    },
    
    onRecordLoad: function(record) {
        this.record = record;
        
        if (! this.record.get('editGrant') || this.record.isRecurException() || this.record.hasPoll()) {
            this.setDisabled(true);
        }
        
        this.rrule = this.record.get('rrule');

        this.initRrule(this.record.get('dtstart'));
        
        var freq = this.rrule && this.rrule.freq ? this.rrule.freq : 'NONE';
        
        var freqBtn = Ext.getCmp(this.idPrefix + 'tglbtn' + freq);
        freqBtn.toggle(true);
        
        this.activeRuleCard = this[freq + 'card'];
        this.ruleCards.activeItem = this.activeRuleCard;
        
        this.activeRuleCard.setRule(this.rrule);

        this.constrains = this.record.get('rrule_constraints');
        if (this.constrains) {
            var constrainsValue = this.constrains[0].value;
            if (constrainsValue && this.activeRuleCard.constrains) {
                this.activeRuleCard.constrains.setValue(constrainsValue);
            }
        }

        if (this.record.isRecurException()) {
            this.activeRuleCard = this.NONEcard;
            this.items.each(function(item) {
                item.setDisabled(true);
            }, this);
            this.ruleCards.collapsed = false;
            
            this.NONEcard.html = this.app.i18n._("Exceptions of reccuring events can't have recurrences themselves.");
        }
    },
    
    onRecordUpdate: function(record) {
        var _ = window.lodash,
            rendered = _.get(this, 'activeRuleCard.rendered', false),
            rrule = rendered ? this.activeRuleCard.getRule() : this.rrule;
        
        if (! this.rrule && rrule) {
            // mark as new rule to avoid series confirm dlg
            rrule.newrule = true;
        }
        
        record.set('rrule', '');
        record.set('rrule', rrule);

        record.set('rrule_constraints', '');

        if (! rendered) {
            record.set('rrule_constraints', this.constrains);
        } else if (this.activeRuleCard.constrains)  {
            var constrainsValue = this.activeRuleCard.constrains.getValue();
            if (constrainsValue && constrainsValue.length) {
                record.set('rrule_constraints', [{field: 'container_id', operator: 'in', value: constrainsValue}]);
            }
        }
    }
});

Tine.Calendar.RrulePanel.AbstractCard = Ext.extend(Ext.Panel, {
    border: false,
    layout: 'form',
    labelAlign: 'side',
    autoHeight: true,
    
    getRule: function() {
        
        var rrule = {
            freq    : this.freq,
            interval: this.interval.getValue()
        };
        
        if (this.untilRadio.checked) {
            rrule.until = this.until.getRawValue();
            rrule.until = rrule.until ? Date.parseDate(rrule.until, this.until.format) : null;
            
            
            if (Ext.isDate(rrule.until)) {
                // make sure, last reccurance is included
                rrule.until = rrule.until.clearTime(true).add(Date.HOUR, 24).add(Date.SECOND, -1).format(Date.patterns.ISO8601Long);
            }
        } else {
            rrule.count = this.count.getValue() || 1;
        }
            
        
        return rrule;
    },
    
    onAfterUnitTriggerClick: function() {
        if (! this.until.getValue()) {
            var dtstart = this.rrulePanel.record.get('dtstart');
            this.until.menu.picker.setValue(dtstart);
        }
    },
    
    initComponent: function() {
        this.app = Tine.Tinebase.appMgr.get('Calendar');
        
        this.limitId = Ext.id();
        
        this.untilRadio = new Ext.form.Radio({
            requiredGrant : 'editGrant',
            hideLabel     : true,
            boxLabel      : this.app.i18n._('at'), 
            name          : this.limitId + 'LimitRadioGroup', 
            inputValue    : 'UNTIL',
            checked       : true,
            listeners     : {
                check: this.onLimitRadioCheck.createDelegate(this)
            }
        });
        
        this.until = new Ext.form.DateField({
            requiredGrant : 'editGrant',
            width         : 100,
            emptyText     : this.app.i18n._('never'),
            onTriggerClick: Ext.form.DateField.prototype.onTriggerClick.createSequence(this.onAfterUnitTriggerClick, this),
            listeners: {
                scope: this,
                // so dumb!
                render: function(f) {f.wrap.setWidth.defer(100, f.wrap, [f.initialConfig.width]);}
            }
        });
        
        var countStringParts = this.app.i18n._('after {0} occurrences').split('{0}'),
            countBeforeString = countStringParts[0],
            countAfterString = countStringParts[1];
        
        this.countRadio = new Ext.form.Radio({
            requiredGrant : 'editGrant',
            hideLabel     : true,
            boxLabel      : countBeforeString, 
            name          : this.limitId + 'LimitRadioGroup', 
            inputValue    : 'COUNT',
            listeners     : {
                check: this.onLimitRadioCheck.createDelegate(this)
            }
        });
        
        this.count = new Ext.form.NumberField({
            requiredGrant : 'editGrant',
            style         : 'text-align:right;',
            width         : 40,
            minValue      : 1,
            disabled      : true,
            allowDecimals : false,
            allowBlank    : false
        });
        
        var intervalPars = this.intervalString.split('{0}');
        var intervalBeforeString = intervalPars[0];
        var intervalAfterString = intervalPars[1];
        
        this.interval = new Ext.form.NumberField({
            requiredGrant : 'editGrant',
            style         : 'text-align:right;',
            //fieldLabel    : this.intervalBeforeString,
            minValue      : 1,
            allowBlank    : false,
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

        this.constrains = new Tine.widgets.container.FilterModelMultipleValueField({
        //this.constrains = new Tine.widgets.container.SelectionComboBox({
            app: this.app,
            allowBlank: true,
            width: 260,
            listWidth: 200,
            allowNodeSelect: true,
            recordClass: Tine.Calendar.Model.Event
        });

        if (this.app.featureEnabled('featureRecurExcept')) {
            this.items = this.items.concat([{
                layout: 'hbox',
                //style: 'padding-top: 2px;',
                items: [
                    {
                        xtype: 'label',
                        style: 'padding-top: 2px;',
                        width: 70,
                        text: this.app.i18n._('Except')
                    },
                    {
                        // @IDEA: this could be a combo later
                        // - if one attendee is busy
                        // - if organizer is busy
                        // - resources are busy
                        // - ...
                        xtype: 'label',
                        style: 'padding-top: 2px;',
                        width: 200,
                        text: this.app.i18n._('during events in the calendars')
                    },
                    {
                        xtype: 'label',
                        width: 260,
                        id: this.limitId + 'constraints'
                    }
                ]
            }]);
        };

        this.items = this.items.concat({
            layout: 'form',
            html: '<div style="padding-top: 5px;">' + this.app.i18n._('End') + '</div>' +
                    '<div style="position: relative;">' +
                    '<div style="position: relative;">' +
                        '<table><tr>' +
                            '<td width="65" id="' + this.limitId + 'untilRadio"></td>' +
                            '<td width="100" id="' + this.limitId + 'until"></td>' +
                        '</tr></table>' +
                    '</div>' +
                    '<div style="position: relative;">' +
                        '<table><tr>' +
                            '<td width="65" id="' + this.limitId + 'countRadio"></td>' +
                            '<td width="40" id="' + this.limitId + 'count"></td>' +
                            '<td width="40" style="padding-left: 5px" >' + countAfterString + '</td>' +
                         '</tr></table>' +
                    '</div>' +
                '</div>',
                listeners: {
                   scope: this,
                   render: this.onLimitRender
                }
        });
        
        Tine.Calendar.RrulePanel.AbstractCard.superclass.initComponent.call(this);
    },
    
    onLimitRender: function() {
        var untilradioel = Ext.get(this.limitId + 'untilRadio');
        var untilel = Ext.get(this.limitId + 'until');
        
        var countradioel = Ext.get(this.limitId + 'countRadio');
        var countel = Ext.get(this.limitId + 'count');
        
        if (! (untilradioel && countradioel)) {
            return this.onLimitRender.defer(100, this, arguments);
        }
        
        this.untilRadio.render(untilradioel);
        this.until.render(untilel);
        this.until.wrap.setWidth(80);
        
        this.countRadio.render(countradioel);
        this.count.render(countel);

        if (this.app.featureEnabled('featureRecurExcept')) {
            this.constrains.render(Ext.get(this.limitId + 'constraints'));
            this.constrains.wrap.setWidth(260);
        }
    },
    
    onLimitRadioCheck: function(radio, checked) {
        switch(radio.inputValue) {
            case 'UNTIL':
                this.count.setDisabled(checked);
                break;
            case 'COUNT':
                this.until.setDisabled(checked);
                break;
        }
    },
    
    isValid: function(record) {
        var until = this.until.getValue(),
            freq = this.freq;
        
        if (Ext.isDate(until) && Ext.isDate(record.get('dtstart'))) {
            if (until.getTime() < record.get('dtstart').getTime()) {
                this.until.markInvalid(this.app.i18n._('Until has to be after event start'));
                return false;
            }
        } 
        
        if (Ext.isDate(record.get('dtend')) && Ext.isDate(record.get('dtstart'))) {
            var dayDifference = (record.get('dtend').getTime() - record.get('dtstart').getTime()) / 1000 / 60 / 60 / 24,
                dtendField = this.rrulePanel.eventEditDialog.getForm().findField('dtend');
            
            if(freq == 'DAILY' && dayDifference >= 1) {
                dtendField.markInvalid(this.app.i18n._('The event is longer than the recurring interval'));
                return false;
            } else if(freq == 'WEEKLY' && dayDifference >= 7) {
                dtendField.markInvalid(this.app.i18n._('The event is longer than the recurring interval'));
                return false;
            } else if(freq == 'MONTHLY' && dayDifference >= 28) {
                dtendField.markInvalid(this.app.i18n._('The event is longer than the recurring interval'));
                return false;
            } else if(freq == 'YEARLY' && dayDifference >= 365) {
                dtendField.markInvalid(this.app.i18n._('The event is longer than the recurring interval'));
                return false;
            }
        }
        
        return true;
    },
    
    setRule: function(rrule) {
        this.interval.setValue(rrule.interval || 1);
        var date = Date.parseDate(rrule.until, Date.patterns.ISO8601Long);
        this.until.value = date;
        
        if (rrule.count) {
            this.count.value = rrule.count;
                
            this.untilRadio.setValue(false);
            this.countRadio.setValue(true);
            this.onLimitRadioCheck(this.untilRadio, false);
            this.onLimitRadioCheck(this.countRadio, true);
        }
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
        
        rrule.wkst = this.wkst || Tine.Calendar.RrulePanel.prototype.wkdays[Ext.DatePicker.prototype.startDay];
        
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
        this.wkst = rrule.wkst;
        
        if (rrule.byday) {
            this.byDayValue = rrule.byday;
            
            var bydayArray = rrule.byday.split(',');
            
            if (Ext.isArray(this.byday.items)) {
                // on initialisation items are not renderd
                Ext.each(this.byday.items, function(cb) {
                    cb.checked = bydayArray.indexOf(cb.name) != -1
                }, this);
            } else {
                // after items are rendered
                this.byday.items.each(function(cb) {
                    cb.setValue(bydayArray.indexOf(cb.name) != -1);
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
            style         : 'text-align:right;',
            hideLabel     : true,
            width         : 40,
            value         : 1,
            disabled      : true
        });
        
        this.items = [{
            html: '<div style="padding-top: 5px;">' + 
                    '<div style="position: relative;">' +
                        '<table><tr>' +
                            '<td style="position: relative;" width="65" id="' + this.idPrefix + 'bydayradio"></td>' +
                            '<td width="100" id="' + this.idPrefix + 'bydaywknumber"></td>' +
                            '<td width="110" id="' + this.idPrefix + 'bydaywkday"></td>' +
                        '</tr></table>' +
                    '</div>' +
                    '<div style="position: relative;">' +
                        '<table><tr>' +
                            '<td width="65" id="' + this.idPrefix + 'bymonthdayradio"></td>' +
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
            style         : 'text-align:right;',
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
                            '<td width="15" style="padding-left: 37px">' + this.app.i18n._('of') + '</td>' +
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
