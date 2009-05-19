/* 
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */

Ext.ns('Tine.Calendar');

Tine.Calendar.PagingToolbar = Ext.extend(Ext.Toolbar, {
    
    dtStart: null,
    
    
    /**
     * @private
     * @property activeView
     */
    view: 'day',
    /**
     * @private
     * @property periodPicker
     */
    periodPicker: null,
    
    
    initComponent: function() {
        this.addEvents(
            /**
             * @event change
             * Fired whenever a viewstate changes
             * @param {Tine.Calendar.PagingToolbar} this
             * @param {String} activeView
             * @param {Array} period
             */
            'change'
        );
        if (! Ext.isDate(this.dtStart)) {
            this.dtStart = new Date();
        }
        
        this.periodPicker = new Tine.Calendar.PagingToolbar[Ext.util.Format.capitalize(this.view) + 'PeriodPicker']({
            tb: this,
            listeners: {
                scope: this,
                change: function(picker, view, period) {
                    this.dtStart = period.from.clone();
                    this.fireEvent('change', this, view, period);
                }
            }
        });
        
        /*
        var views = ['day', 'week', 'month'];
        for (var i=0, view; i<views.length; i++) {
            view = views[i];
            this.periodPickers[view] = new Tine.Calendar.PagingToolbar[Ext.util.Format.capitalize(view) + 'PeriodPicker']({
                tb: this,
                listeners: {
                    scope: this,
                    change: function(picker, view, period) {
                        this.dtStart = period.from.clone();
                        this.fireEvent('change', this, view, period);
                    }
                }
            });
            
        }
        /*
        this.periodPickers.day = new Tine.Calendar.PagingToolbar.DayPeriodPicker(this);
        this.relayEvents(this.periodPickers.day, ['change']);
        
        this.periodPickers.week = new Tine.Calendar.PagingToolbar.WeekPeriodPicker(this);
        this.relayEvents(this.periodPickers.week, ['change']);
        
        this.periodPickers.month = new Tine.Calendar.PagingToolbar.MonthPeriodPicker(this);
        this.relayEvents(this.periodPickers.month, ['change']);
        */
        
        Tine.Calendar.PagingToolbar.superclass.initComponent.call(this);
    },
    
    onRender: function(ct, position) {
        Tine.Calendar.PagingToolbar.superclass.onRender.call(this, ct, position);
        
        this.prevBtn = this.addButton({
            tooltip: Ext.PagingToolbar.prototype.prevText,
            iconCls: "x-tbar-page-prev",
            handler: this.onClick.createDelegate(this, ["prev"])
        });
        this.addSeparator();
        this.periodPicker.render();
        /*
        for (var view in this.periodPickers) {
            this.periodPickers[view].render();
        }
        */
        this.addSeparator();
        this.nextBtn = this.addButton({
            tooltip: Ext.PagingToolbar.prototype.nextText,
            iconCls: "x-tbar-page-next",
            handler: this.onClick.createDelegate(this, ["next"])
        });
        this.addSeparator();
        this.loading = this.addButton({
            tooltip: Ext.PagingToolbar.prototype.refreshText,
            iconCls: "x-tbar-loading",
            handler: this.onClick.createDelegate(this, ["refresh"])
        });
        
        this.addFill();
        
        /*
        if(this.dsLoaded){
            this.onLoad.apply(this, this.dsLoaded);
        }
        //this.loading.disable();
        */

    },
    
    onClick: function(which) {
        switch(which) {
            /*
            case 'day' :
            case 'week':
            case 'month':
                if (which != this.activeView) {
                    for (var view in this.periodPickers) {
                        this.periodPickers[view][view == which ? 'show' : 'hide']();
                    }
                    this.activeView = which;
                    this.periodPickers[this.activeView].update(this.dtStart);
                    
                    this.fireEvent('change', this, this.activeView, this.periodPickers[this.activeView].getPeriod());
                }
                break;
                
            */    
            case 'next':
            case 'prev':
                this.periodPicker[which]();
                this.fireEvent('change', this, this.activeView, this.periodPicker.getPeriod());
                break;
            case 'refresh':
                this.fireEvent('change', this, this.activeView, this.periodPicker.getPeriod());
                break;
        }
    }
    
});

Tine.Calendar.PagingToolbar.AbstractPeriodPicker = function(config) {
    Ext.apply(this, config);
    this.addEvents(
        /**
         * @event change
         * Fired whenever a period changes
         * @param {Tine.Calendar.PagingToolbar.AbstractPeriodPicker} this
         * @param {String} corresponding view
         * @param {Array} period
         */
        'change'
    );
    Tine.Calendar.PagingToolbar.AbstractPeriodPicker.superclass.constructor.call(this);
    
    this.dtStart = this.tb.dtStart.clone();
    this.init();
};
Ext.extend(Tine.Calendar.PagingToolbar.AbstractPeriodPicker, Ext.util.Observable, {
    init:       function() {},
    hide:       function() {this.button.hide();},
    show:       function() {this.button.show();},
    update:     function(dtStart) {},
    render:     function() {},
    prev:       function() {},
    next:       function() {},
    getPeriod:  function() {}
});

/**
 * @class Tine.Calendar.PagingToolbar.DayPeriodPicker
 * @extends Tine.Calendar.PagingToolbar.AbstractPeriodPicker
 * @constructor
 */
Tine.Calendar.PagingToolbar.DayPeriodPicker = Ext.extend(Tine.Calendar.PagingToolbar.AbstractPeriodPicker, {
    init: function() {
        this.button = new Ext.Button({
            text: this.tb.dtStart.format(Ext.DatePicker.prototype.format),
            //hidden: this.tb.activeView != 'day',
            menu: new Ext.menu.DateMenu({
                listeners: {
                    scope: this,
                    select: function(field) {
                        if (typeof(field.getValue) == 'function') {
                            this.update(field.getValue());
                            this.fireEvent('change', this, 'day', this.getPeriod());
                        }
                    }
                }
            })
        });
    },
    update: function(dtStart) {
        this.dtStart = dtStart.clone();
        if (this.button.rendered) {
            this.button.setText(dtStart.format(Ext.DatePicker.prototype.format));
        }
    },
    render: function() {
        this.button = this.tb.addButton(this.button);
    },
    next: function() {
        this.dtStart = this.dtStart.add(Date.DAY, 1);
        this.update(this.dtStart);
    },
    prev: function() {
        this.dtStart = this.dtStart.add(Date.DAY, -1);
        this.update(this.dtStart);
    },
    getPeriod: function() {
        var from = Date.parseDate(this.dtStart.format('Y-m-d') + ' 00:00:00', Date.patterns.ISO8601Long);
        return {
            from: from,
            until: from.add(Date.DAY, 1).add(Date.SECOND, -1)
        };
    }
});

/**
 * @class Tine.Calendar.PagingToolbar.WeekPeriodPicker
 * @extends Tine.Calendar.PagingToolbar.AbstractPeriodPicker
 * @constructor
 */
Tine.Calendar.PagingToolbar.WeekPeriodPicker = Ext.extend(Tine.Calendar.PagingToolbar.AbstractPeriodPicker, {
    init: function() {
        this.label = new Ext.form.Label({
            text: 'week',
            style: 'padding-right: 3px'
            //hidden: this.tb.activeView != 'week'
        });
        this.field = new Ext.form.TextField({
            value: this.tb.dtStart.getWeekOfYear(),
            width: 30,
            cls: "x-tbar-page-number",
            //hidden: this.tb.activeView != 'week',
            listeners: {
                scope: this,
                specialkey: this.onSelect,
                blur: this.onSelect
            }
        });
    },
    onSelect: function(field, e) {
        if (e && e.getKey() == e.ENTER) {
            return field.blur();
        }
        var diff = field.getValue() - this.dtStart.getWeekOfYear()
        if (diff !== 0) {
            this.update(this.dtStart.add(Date.DAY, diff * 7))
            this.fireEvent('change', this, 'week', this.getPeriod());
        }
        
    },
    update: function(dtStart) {
        this.dtStart = dtStart.clone();
        if (this.field.rendered) {
            this.field.setValue(dtStart.getWeekOfYear());
        }
    },
    render: function() {
        this.tb.addField(this.label);
        this.tb.addField(this.field);
    },
    hide: function() {
        this.label.hide();
        this.field.hide();
    },
    show: function() {
        this.label.show();
        this.field.show();
    },
    next: function() {
        this.dtStart = this.dtStart.add(Date.DAY, 7);
        this.update(this.dtStart);
    },
    prev: function() {
        this.dtStart = this.dtStart.add(Date.DAY, -7);
        this.update(this.dtStart);
    },
    getPeriod: function() {
        // period is the week current startDate is in
        var startDay = Ext.DatePicker.prototype.startDay;
        var diff = startDay - this.dtStart.getDay();
        
        var from = Date.parseDate(this.dtStart.add(Date.DAY, diff).format('Y-m-d') + ' 00:00:00', Date.patterns.ISO8601Long);
        return {
            from: from,
            until: from.add(Date.DAY, 7).add(Date.SECOND, -1)
        };
    }
});

/**
 * @class Tine.Calendar.PagingToolbar.MonthPeriodPicker
 * @extends Tine.Calendar.PagingToolbar.AbstractPeriodPicker
 * @constructor
 */
Tine.Calendar.PagingToolbar.MonthPeriodPicker = Ext.extend(Tine.Calendar.PagingToolbar.AbstractPeriodPicker, {
    init: function() {
        this.button = new Ext.Button({
            text: Ext.DatePicker.prototype.monthNames[this.tb.dtStart.getMonth()] + this.tb.dtStart.format(' Y'),
            //hidden: this.tb.activeView != 'month',
            menu: new Ext.menu.DateMenu({
                hideMonthPicker: Ext.DatePicker.prototype.hideMonthPicker.createSequence(function() {
                    if (this.monthPickerActive) {
                        this.monthPickerActive = false;
                        
                        this.value = this.activeDate;
                        this.fireEvent('select', this, this.value);
                    }
                }),
                listeners: {
                    scope: this,
                    select: function(field) {
                        if (typeof(field.getValue) == 'function') {
                            this.update(field.getValue());
                        }
                    }
                }
            }),
            listeners: {
                scope: this,
                menushow: function(btn, menu) {
                    menu.picker.showMonthPicker();
                    menu.picker.monthPickerActive = true;
                },
                menuhide: function(btn, menu) {
                    menu.picker.monthPickerActive = false;
                }
            }
        });
    },
    update: function(dtStart) {
        this.dtStart = dtStart.clone();
        if (this.button.rendered) {
            var monthName = Ext.DatePicker.prototype.monthNames[dtStart.getMonth()];
            this.button.setText(monthName + dtStart.format(' Y'));
        }
    },
    render: function() {
        this.button = this.tb.addButton(this.button);
    },
    next: function() {
        this.dtStart = this.dtStart.add(Date.MONTH, 1);
        this.update(this.dtStart);
    },
    prev: function() {
        this.dtStart = this.dtStart.add(Date.MONTH, -1);
        this.update(this.dtStart);
    },
    getPeriod: function() {
        var from = Date.parseDate(this.dtStart.format('Y-m') + '-01 00:00:00', Date.patterns.ISO8601Long);
        return {
            from: from,
            until: from.add(Date.MONTH, 1).add(Date.SECOND, -1)
        };
    }
});