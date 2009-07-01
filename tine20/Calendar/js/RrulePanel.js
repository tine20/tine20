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
        this.app= Tine.Tinebase.appMgr.get('Calendar');
        
        this.title = this.app.i18n._('Recurrances');
        
        /*
        this.freq = new Ext.form.ComboBox({
            triggerAction : 'all',
            hideLabel: true,
            fieldLabel    : this.app.i18n._('Recurrances'),
            value         : false,
            editable      : false,
            mode          : 'local',
            store         : [
                [false,      this.app.i18n._('Single Event')   ],
                ['DAILY',    this.app.i18n._('Daily')   ],
                ['WEEKLY',   this.app.i18n._('Weekly')  ],
                ['MONTHLY',  this.app.i18n._('Monthly') ],
                ['YEARLY',   this.app.i18n._('Yearly')  ]
            ]
        });
        
        */
        /*
        this.items = [{
            layout: 'column',
            items: [{
                columnWidth: 0.2,
                layout: 'form',
                labelAlign: 'top',
                items: [{
                    xtype: 'fieldset',
                    title: this.app.i18n._('Recurrances'),
                    autoHeight: true,
                    items: [{
                        xtype: 'radiogroup',
                        itemCls: 'x-check-group-alt',
                        hideLabel: true,
                        columns: 1,
                        items: [
                            {boxLabel: this.app.i18n._('None'),    name: 'rb-auto', inputValue: 1},
                            {boxLabel: this.app.i18n._('Daily'),   name: 'rb-auto', inputValue: 2, checked: true},
                            {boxLabel: this.app.i18n._('Weekly'),  name: 'rb-auto', inputValue: 3},
                            {boxLabel: this.app.i18n._('Monthly'), name: 'rb-auto', inputValue: 4},
                            {boxLabel: this.app.i18n._('Yearly'),  name: 'rb-auto', inputValue: 5}
                        ]
                    }]
                }]
            }, {
                //xtype: 'form',
                layout: 'form',
                //labelAlign: 'side',
                //labelWidth: 50,
                style: 'padding-left: 5px;',
                columnWidth: 0.8,
                items: [{
                    xtype: 'fieldset',
                    title: this.app.i18n._('Rule'),
                    autoHeight: true,
                    items: [
                        this.ruleCards, {
                        style: 'padding-bottom: 10px;',
                        layout: 'column',
                        items: [{
                            width: 70,
                            html: this.app.i18n._('Ends')
                        },
                            this.untilCombo,
                        {
                            width: 5,
                            html: '&nbsp;'
                        },
                            this.until
                        ]
                    }]
                }]
            }]
        }];
        */
        this.defaults = {
            border: false
        };
        
        this.NONEcard = new Ext.Panel({
            listeners: {
                scope: this,
                show: function(p) {Ext.getCmp(this.idPrefix + 'untilPicker').hide();},
                hide: function(p) {Ext.getCmp(this.idPrefix + 'untilPicker').show();}
                
            }
        });
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

        
        this.until = new Ext.ux.form.ClearableDateField({
            width: 100,
            emptyText: this.app.i18n._('never')
            //style: 'padding-left: 5px;'
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
        
        this.idPrefix = Ext.id();
        
        this.items = [{
            xtype: 'toolbar',
            //style: 'background: 0; border: 0; padding-bottom: 5px;',
            style: 'margin-bottom: 5px;',
            
            items: [{
                id: this.idPrefix + 'tglbtn' + 'NONE',
                xtype: 'tbbtnlockedtoggle',
                enableToggle: true,
                pressed: true,
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
                this.ruleCards, {
                id: this.idPrefix + 'untilPicker',
                hideMode: 'visibility',
                style: 'padding-top: 5px;',
                layout: 'column',
                items: [{
                    width: 70,
                    html: this.app.i18n._('Ends')
                },
                    /*this.untilCombo,
                {
                    width: 5,
                    html: '&nbsp;'
                },*/
                    this.until
                ]
            }]
        }];
        
        Tine.Calendar.RrulePanel.superclass.initComponent.call(this);
    },
    
    onFreqChange: function(freq) {
        this.ruleCards.layout.setActiveItem(this[freq + 'card']);
        this.ruleCards.layout.layout();
        this.activeRuleCard = this[freq + 'card'];
    },
    
    onRecordLoad: function(record) {
        this.record = record;
        this.rrule = this.record.get('rrule');
        
        var freqBtn = Ext.getCmp(this.idPrefix + 'tglbtn' + this.rrule.freq) || Ext.getCmp(this.idPrefix + 'tglbtnNONE');
        freqBtn.toggle(true);
        
        this.activeRuleCard = this[this.rrule.freq + 'card'];
        this.ruleCards.layout.setActiveItem(this.activeRuleCard);
        
        
        this.activeRuleCard.setRule(this.rrule);
        this.until.setValue(Date.parseDate(this.rrule.until, Date.patterns.ISO8601Long));
    },
    
    onRecordUpdate: function(record) {
        var rrule = this.activeRuleCard.getRule();
        var until = this.until.getValue();
        rrule.until = Ext.isDate(until) ? until.format(Date.patterns.ISO8601Long) : null;
        
        record.set('rrule', '');
        record.set('rrule', rrule);
    }
});

Tine.Calendar.RrulePanel.AbstractCard = Ext.extend(Ext.Panel, {
    border: false,
    layout: 'form',
    labelAlign: 'side',
    
    getRule: function() {
        var rrule = {
            freq: this.freq,
            interval: this.interval.getValue()
        };
        
        return rrule;
    },
    
    initComponent: function() {
        this.app = Tine.Tinebase.appMgr.get('Calendar');
        
        if (! this.intervalBeforeString) {
            this.intervalBeforeString = this.app.i18n._('Every');
        }
        
        this.interval = new Ext.form.NumberField({
            style         : 'text-align:right;',
            fieldLabel    : this.intervalBeforeString,
            value         : 1,
            width         : 40
        });
        
        if (! this.items) {
            this.items = [];
        }
        
        this.items = [{
            layout: 'column',
            items: [{
                width: 70,
                html: this.intervalBeforeString
            },
                this.interval,
            {
                style: 'padding-top: 2px;',
                html: this.intervalAfterString
            }]
        }].concat(this.items);
        
        Tine.Calendar.RrulePanel.AbstractCard.superclass.initComponent.call(this);
    },
    
    setRule: function(rrule) {
        this.interval.setValue(rrule.interval);
    }
});

Tine.Calendar.RrulePanel.DAILYcard = Ext.extend(Tine.Calendar.RrulePanel.AbstractCard, {
    
    freq: 'DAILY',
    
    initComponent: function() {
        this.app = Tine.Tinebase.appMgr.get('Calendar');
        
        this.intervalAfterString = this.app.i18n._('. Day');
        
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
        return rrule;
    },
    
    initComponent: function() {
        this.app = Tine.Tinebase.appMgr.get('Calendar');
        
        this.intervalAfterString = this.app.i18n._('. Week at');
        
        var bydayItems = [];
        for (var i=0,d; i<7; i++) {
            d = (i+Ext.DatePicker.prototype.startDay)%7
            Tine.Calendar.RrulePanel.prototype.wkdays[d];
            bydayItems.push({
                boxLabel: Date.dayNames[d],
                name: Tine.Calendar.RrulePanel.prototype.wkdays[d]
            })
        }
        
        this.byday = new Ext.form.CheckboxGroup({
            style: 'padding-top: 5px; padding-left: 10px',
            hideLabel: true,
            items: bydayItems
        });
        
        this.items = [this.byday];
        
        Tine.Calendar.RrulePanel.WEEKLYcard.superclass.initComponent.call(this);
    },
    
    setRule: function(rrule) {
        Tine.Calendar.RrulePanel.WEEKLYcard.superclass.setRule.call(this, rrule);
        
        var bydayArray = rrule.byday.split(',');
        this.byday.items.each(function(cb) {
            if (bydayArray.indexOf(cb.name) != -1) {
                cb.setValue(true);
            }
        }, this);
    }
});

Tine.Calendar.RrulePanel.MONTHLYcard = Ext.extend(Tine.Calendar.RrulePanel.AbstractCard, {
    
    freq: 'MONTHLY',
    
    initComponent: function() {
        this.app = Tine.Tinebase.appMgr.get('Calendar');
        
        this.intervalAfterString = this.app.i18n._('. Month');
        
        Tine.Calendar.RrulePanel.MONTHLYcard.superclass.initComponent.call(this);
    }
});

Tine.Calendar.RrulePanel.YEARLYcard = Ext.extend(Tine.Calendar.RrulePanel.AbstractCard, {
    
    freq: 'YEARLY',
    
    initComponent: function() {
        this.app = Tine.Tinebase.appMgr.get('Calendar');
        
        this.intervalAfterString = this.app.i18n._('. Year');
        
        Tine.Calendar.RrulePanel.YEARLYcard.superclass.initComponent.call(this);
    }
});