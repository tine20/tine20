/* 
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine.Calendar');

Tine.Calendar.PagingToolbar = Ext.extend(Ext.Toolbar, {
    /**
     * @cfg {Date} dtstart
     */
    dtStart: null,
    /**
     * @cfg {String} view
     */
    view: 'day',
    /**
     * @private periodPicker
     */
    periodPicker: null,
    /**
     * @cfg {Boolean} showReloadBtn
     */    
    showReloadBtn: true,
    /**
     * @cfg {Boolean} showTodayBtn
     */ 
    showTodayBtn: true,
    /**
     * shows if the periodpicker is active
     * @type boolean
     */
    periodPickerActive: null,
    
    /**
     * @private
     */
    initComponent: function() {
        this.addEvents(
            /**
             * @event change
             * Fired whenever a viewstate changes
             * @param {Tine.Calendar.PagingToolbar} this
             * @param {String} activeView
             * @param {Array} period
             */
            'change',
            /**
             * @event refresh
             * Fired when user request view freshresh
             * @param {Tine.Calendar.PagingToolbar} this
             * @param {String} activeView
             * @param {Array} period
             */
            'refresh'
        );
        if (! Ext.isDate(this.dtStart)) {
            this.dtStart = new Date().clearTime();
        }
        
        this.periodPicker = new Tine.Calendar.PagingToolbar[Ext.util.Format.capitalize(this.view) + 'PeriodPicker']({
            tb: this,
            listeners: {
                scope: this,
                change: function(picker, view, period) {
                    this.dtStart = period.from.clearTime(true);
                    this.fireEvent('change', this, view, period);
                },
                menushow: function(){this.periodPickerActive = true; },
                menuhide: function(){this.periodPickerActive = false;}
            }
        });
        
        Tine.Calendar.PagingToolbar.superclass.initComponent.call(this);
        this.bind(this.store);
    },
    
    /**
     * @private
     */
    onRender: function(ct, position) {
        Tine.Calendar.PagingToolbar.superclass.onRender.call(this, ct, position);
        this.prevBtn = this.addButton({
            tooltip: Ext.PagingToolbar.prototype.prevText,
            iconCls: "x-tbar-page-prev",
            handler: this.onClick.createDelegate(this, ["prev"])
        });
        this.addSeparator();
        this.periodPicker.render();
        this.addSeparator();
        this.nextBtn = this.addButton({
            tooltip: Ext.PagingToolbar.prototype.nextText,
            iconCls: "x-tbar-page-next",
            handler: this.onClick.createDelegate(this, ["next"])
        });
        
        if(this.showTodayBtn || this.showReloadBtn) this.addSeparator();
        
        if(this.showTodayBtn) {
            this.todayBtn = this.addButton({
                text: Ext.DatePicker.prototype.todayText,
                iconCls: 'cal-today-action',
                handler: this.onClick.createDelegate(this, ["today"])
            });
        }

        if(this.showReloadBtn) {
            this.loading = this.addButton({
                tooltip: Ext.PagingToolbar.prototype.refreshText,
                iconCls: "x-tbar-loading",
                handler: this.onClick.createDelegate(this, ["refresh"])
            });
        }
        
        this.addFill();
        if (this.additionalItems) {
            this.addButton(this.additionalItems);
        }
        
        if(this.isLoading){
            this.loading.disable();
        }
    },
    
    /**
     * @private
     * @param {String} which
     */
    onClick: function(which) {
        switch(which) {
            case 'today':
            case 'next':
            case 'prev':
                this.periodPicker[which]();
                this.fireEvent('change', this, this.activeView, this.periodPicker.getPeriod());
                break;
            case 'refresh':
                this.fireEvent('refresh', this, this.activeView, this.periodPicker.getPeriod());
                break;
        }
    },
    
    /**
     * returns requested period
     * @return {Array}
     */
    getPeriod: function() {
        return this.periodPicker.getPeriod();
    },
    
    // private
    beforeLoad : function(){
        this.isLoading = true;
        
        if(this.rendered && this.loading) {
            this.loading.disable();
        }
    },
    
    // private
    onLoad : function(store, r, o){
        this.isLoading = false;
        
        if(this.rendered && this.loading) {
            this.loading.enable();
        }
    },

    /**
     * Unbinds the paging toolbar from the specified {@link Ext.data.Store}
     * @param {Ext.data.Store} store The data store to unbind
     */
    unbind : function(store){
        store = Ext.StoreMgr.lookup(store);
        store.un("beforeload", this.beforeLoad, this);
        store.un("load", this.onLoad, this);
        //store.un("loadexception", this.onLoadError, this);
        this.store = undefined;
    },

    /**
     * Binds the paging toolbar to the specified {@link Ext.data.Store}
     * @param {Ext.data.Store} store The data store to bind
     */
    bind : function(store){
        store = Ext.StoreMgr.lookup(store);
        store.on("beforeload", this.beforeLoad, this);
        store.on("load", this.onLoad, this);
        //store.on("loadexception", this.onLoadError, this);
        this.store = store;
    },

    /**
     * just needed when inserted in an eventpickercombobox
     */
    bindStore: function() {},
    
    // private
    onDestroy : function(){
        if(this.store){
            this.unbind(this.store);
        }
        Tine.Calendar.PagingToolbar.superclass.onDestroy.call(this);
    }
});

/**
 * @class Tine.Calendar.PagingToolbar.AbstractPeriodPicker
 * @extends Ext.util.Observable
 * @constructor
 * @param {Object} config
 */
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
    
    this.update(this.tb.dtStart);
    this.init();
};
Ext.extend(Tine.Calendar.PagingToolbar.AbstractPeriodPicker, Ext.util.Observable, {
    /**
     * period NOTE: might not fit to current range on view changes!
     */
    period: null,

    init:       function() {},
    hide:       function() {this.button.hide();},
    show:       function() {this.button.show();},
    update:     function(period) {},
    render:     function() {},
    prev:       function() {},
    next:       function() {},
    today:      function() {this.update(new Date().clearTime());},
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
    update: function(period) {
        this.dtStart = _.get(period, 'from', period).clearTime(true);
        if (this.button && this.button.rendered) {
            this.button.setText(this.dtStart.format(Ext.DatePicker.prototype.format));
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
            until: from.add(Date.DAY, 1)/*.add(Date.SECOND, -1)*/
        };
    }
});

/**
 * @class Tine.Calendar.PagingToolbar.WeekPeriodPicker
 * @extends Tine.Calendar.PagingToolbar.AbstractPeriodPicker
 * @constructor
 */
Tine.Calendar.PagingToolbar.WeekPeriodPicker = Ext.extend(Tine.Calendar.PagingToolbar.AbstractPeriodPicker, {
    datepickerMenu: null,
    datepickerButton: null,
    wkField: null,

    init: function() {
        this.label = new Ext.form.Label({
            text: Tine.Tinebase.appMgr.get('Calendar').i18n._('Week'),
            style: 'padding-right: 3px'
        });

        this.wkField = new Ext.form.TextField({
            value: this.tb.dtStart.getWeekOfYear(),
            width: 22,
            cls: "x-tbar-page-number",
            listeners: {
                scope: this,
                specialkey: this.onSelect,
                blur: this.onSelect
            }
        });

        this.yearField = new Ext.form.Label({
            text: this.tb.dtStart.format('o'),
            style: 'padding-left: 3px'
        });


        this.datepickerMenu = new Ext.menu.DateMenu({
            value: this.tb.dtStart,
            hideOnClick: true,
            focusOnSelect: true,
            plugins: [new Ext.ux.DatePickerWeekPlugin({
                weekHeaderString: Tine.Tinebase.appMgr.get('Calendar').i18n._('WK')
            })],
            listeners: {
                scope: this,
                'select': function (picker, date) {
                    var oldPeriod = this.getPeriod();
                    this.update(date);

                    if (this.getPeriod().from.getElapsed(oldPeriod.from)) {
                        this.fireEvent('change', this, 'week', this.getPeriod());
                    }
                }
            }
        });

        this.datepickerButton = new Ext.Button({
            iconCls: 'cal-sheet-view-type'
        });

        this.datepickerButton.on('click', function () {
            this.datepickerMenu.show(this.datepickerButton.el);
        }.createDelegate(this));
    },
    onSelect: function(field, e) {
        if (e && e.getKey() == e.ENTER) {
            return field.blur();
        }
        var diff = field.getValue() - this.dtStart.getWeekOfYear() - parseInt(this.dtStart.getDay() < 1 ? 1 : 0, 10);
        if (diff !== 0) {
            this.update(this.dtStart.add(Date.DAY, diff * 7));
            this.fireEvent('change', this, 'week', this.getPeriod());
        }
        
    },
    update: function(period) {
        let dtStart = _.get(period, 'from', period).clearTime(true);

        // const state = Ext.state.Manager.get('cal-pgtb-pp-wkbtn');
        const state = this.tb.periodBtn.getState();
        const wkStartDiff = state.startIdx - Tine.Calendar.PagingToolbar.WeekPeriodPicker.Button.prototype.beforeDays;
        const startDay = Ext.DatePicker.prototype.startDay;
        
        // recalculate dtstart according to WeekPeriodPicker.Button
        this.dtStart = dtStart.add(Date.DAY, -1 * (7+dtStart.getDay() - startDay - wkStartDiff)%7);
        if (this.getPeriod().until < dtStart) {
            this.dtStart = this.dtStart.add(Date.DAY, 7);
        }
        
        if (this.wkField && this.wkField.rendered) {
            // NOTE: '+1' is to ensure we display the ISO8601 based week where weeks always start on monday!
            var wkStart = this.dtStart.add(Date.DAY, dtStart.getDay() < 1 ? 1 : 0)
                .add(Date.DAY, -1*wkStartDiff);
            
            this.wkField.setValue(parseInt(wkStart.getWeekOfYear(), 10));
            this.yearField.setText(this.dtStart.format('o'));
        }
    },
    render: function() {
        this.tb.addField(this.label);
        this.tb.addField(this.wkField);
        this.tb.addField(this.yearField);
        this.tb.addField(this.datepickerButton);
    },
    hide: function() {
        this.label.hide();
        this.wkField.hide();
        this.yearField.hide();
        this.datepickerButton.hide();
    },
    show: function() {
        this.label.show();
        this.wkField.show();
        this.yearField.show();
        this.datepickerButton.show();
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
        const state = this.tb.periodBtn.getState();
        // const state = Ext.state.Manager.get('cal-pgtb-pp-wkbtn');
        return {
            from: this.dtStart.clone(),
            until: this.dtStart.add(Date.DAY, state.endIdx - state.startIdx+1)
        };
    }
});

Tine.Calendar.PagingToolbar.WeekPeriodPicker.Button = Ext.extend(Ext.SplitButton, {
    // override via prototype or config
    beforeDays: 1,
    numDays: 10,
    
    stateful: true,
    stateId: 'cal-pgtb-pp-wkbtn',
    
    initComponent: function() {
        this.app = Tine.Tinebase.appMgr.get('Calendar');

        const state = Ext.state.Manager.get(this.stateId);
        const wkStartDay = _.get(state, 'wkStart', Ext.DatePicker.prototype.startDay);
        const selectionStartIdx = _.get(state, 'startIdx', this.beforeDays+wkStartDay-1);
        const selectionEndIdx = _.get(state, 'endIdx',selectionStartIdx + 6);
        
        this.startDay = (wkStartDay+7-this.beforeDays)%7;
        
        this.dayBtns = [];
        // @TODO: if week is not standard, have a marker in btn?
        for (let i=0,dayNum=this.startDay; i<this.numDays; i++,dayNum++) {
            this.dayBtns.push(new Ext.Button({
                pressed: i >= selectionStartIdx && i <= selectionEndIdx,
                dayIdx: i,
                dayNum: dayNum,
                text: Date.dayNames[dayNum%7].substr(0,2),
                enableToggle: true,
                scope: this,
                handler: this.onDayBtnPress
            }));
        }
        
        this.menu = new Ext.menu.Menu({ 
            items: [new Ext.Toolbar({
                cls: 'cal-wkperiod-config-menu',
                items: [].concat(this.dayBtns, {xtype: 'tbtext', width: 40}, 
                    {text: i18n._hidden('OK'), handler: () => {this.menu.hide()}})
        })]});
        
        this.menu.on('hide', this.onMenuHide, this);
        this.supr().initComponent.call(this);
    },

    onMenuHide: function() {
        const state = Ext.state.Manager.get(this.stateId);
        if (JSON.stringify(state) !== JSON.stringify(this.getState())) {
            this.saveState();
            this.fireEvent('change', this, state);
        }
    },
    onDayBtnPress: function(btn) {
        const selected = _.filter(this.dayBtns, {pressed: true});
        
        if (! btn.pressed) {
            // deselect/reduce
            const bellow = _.filter(selected, (cmp) => {return cmp.dayIdx < btn.dayIdx});
            const above = _.filter(selected, (cmp) => { return cmp.dayIdx > btn.dayIdx });
            const toReduce = bellow.length < above.length ? bellow : above;
            _.each(toReduce, (btn) => { btn.toggle(false, false) });
            if (bellow.length + above.length < 3) {
                btn.toggle(true, false);
            }
        } else {
            // select/expand
            let lastIdx = selected[0].dayIdx;
            _.each(selected, (btn) => {
                for(let idx=lastIdx+1; idx<btn.dayIdx; idx++) {
                    this.dayBtns[idx].toggle(true, false);
                }
                lastIdx = btn.dayIdx;
            })
        }
    },
    
    getState: function() {
        const state = {
            startIdx: _.find(this.dayBtns, {pressed: true}).dayIdx,
            endIdx: _.findLast(this.dayBtns, {pressed: true}).dayIdx
        }
        
        // normalise
        if (state.startIdx-7 >= 0) {
            state.startIdx = state.startIdx-7;
            state.endIdx = state.endIdx-7;
        }
        return state;
    },

    onRender : function(){
        Tine.Calendar.PagingToolbar.WeekPeriodPicker.Button.superclass.onRender.apply(this, arguments);
        // have less optical focus
        this.btnEl.setStyle({border: "none"});
    },
});
/**
 * @class Tine.Calendar.PagingToolbar.MonthPeriodPicker
 * @extends Tine.Calendar.PagingToolbar.AbstractPeriodPicker
 * @constructor
 */
Tine.Calendar.PagingToolbar.MonthPeriodPicker = Ext.extend(Tine.Calendar.PagingToolbar.AbstractPeriodPicker, {
    init: function() {
        this.dateMenu = new Ext.menu.DateMenu({
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
                        this.fireEvent('change', this, 'month', this.getPeriod());
                    }
                }
            }
        });
        
        this.button = new Ext.Button({
            minWidth: 130,
            text: Ext.DatePicker.prototype.monthNames[this.tb.dtStart.getMonth()] + this.tb.dtStart.format(' Y'),
            //hidden: this.tb.activeView != 'month',
            menu: this.dateMenu,
            listeners: {
                scope: this,
                menushow: function(btn, menu) {
                    menu.picker.showMonthPicker();
                    menu.picker.monthPickerActive = true;
                    this.fireEvent('menushow');
                },
                menuhide: function(btn, menu) {
                    menu.picker.monthPickerActive = false;
                    this.fireEvent('menuhide');
                }
            }
        });
    },
    update: function(period) {
        this.dtStart = _.get(period, 'from', period).clearTime(true);
        if (this.button && this.button.rendered) {
            var monthName = Ext.DatePicker.prototype.monthNames[this.dtStart.getMonth()];
            this.button.setText(monthName + this.dtStart.format(' Y'));
            this.dateMenu.picker.setValue(this.dtStart.clone());
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
            until: from.add(Date.MONTH, 1)/*.add(Date.SECOND, -1)*/
        };
    }
});

/**
 * @class Tine.Calendar.PagingToolbar.YearPeriodPicker
 * @extends Tine.Calendar.PagingToolbar.AbstractPeriodPicker
 * @constructor
 */
Tine.Calendar.PagingToolbar.YearPeriodPicker = Ext.extend(Tine.Calendar.PagingToolbar.AbstractPeriodPicker, {
    init: function() {
        this.label = new Ext.form.Label({
            text: Tine.Tinebase.appMgr.get('Calendar').i18n._('Year'),
            style: 'padding-right: 3px'
        });
        this.field = new Ext.form.TextField({
            value: this.tb.dtStart.format('Y'),
            width: 40,
            cls: "x-tbar-page-number",
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
        var diff = field.getValue() - this.dtStart.format('Y'); 
        if (diff !== 0) {
            this.update(this.dtStart.add(Date.YEAR, diff ))
            this.fireEvent('change', this, 'year', this.getPeriod());
        }
        
    },
    update: function(period) {
        this.dtStart =  _.get(period, 'from', period).clearTime(true);
        if (this.field && this.field.rendered) {
            this.field.setValue(this.dtStart.format('Y'));
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
        this.dtStart = this.dtStart.add(Date.YEAR, 1);
        this.update(this.dtStart);
    },
    prev: function() {
        this.dtStart = this.dtStart.add(Date.YEAR, -1);
        this.update(this.dtStart);
    },
    getPeriod: function() {
        var from = Date.parseDate(this.dtStart.format('Y') + '-01-01 00:00:00', Date.patterns.ISO8601Long);
        return {
            from: from,
            until: from.add(Date.YEAR, 1)
        };
    }
});

/**
 * @class Tine.Calendar.PagingToolbar.CustomPeriodPicker
 * @extends Tine.Calendar.PagingToolbar.AbstractPeriodPicker
 * @constructor
 */
Tine.Calendar.PagingToolbar.CustomPeriodPicker = Ext.extend(Tine.Calendar.PagingToolbar.AbstractPeriodPicker, {

    init: function() {
        this.tb.showTodayBtn = false;

        this.fromLabel = new Ext.form.Label({
            text: Tine.Tinebase.appMgr.get('Calendar').i18n._('From'),
            style: 'padding-right: 3px'
        });

        this.fromPicker = new Ext.ux.form.DateTimeField ({
            style: 'margin-top: 2px',
            value: _.get(this, 'tb.period.from', this.tb.startDate) || new Date().clearTime(),
            listeners: {
                scope: this,
                specialkey: this.onSelect,
                blur: this.onSelect
            }
        });

        this.untilLabel = new Ext.form.Label({
            text: Tine.Tinebase.appMgr.get('Calendar').i18n._('until'),
            style: 'padding-left: 10px; padding-right: 3px'
        });

        this.untilPicker = new Ext.ux.form.DateTimeField ({
            style: 'margin-top: 2px',
            value: _.get(this, 'tb.period.until', this.tb.startDate) || new Date().clearTime().add(Date.DAY, 5),
            listeners: {
                scope: this,
                specialkey: this.onSelect,
                blur: this.onSelect
            }
        });

        this.period = this.getPeriod();
    },
    onSelect: function(field, e) {
        if (e && e.getKey() === e.ENTER) {
            return field.blur();
        }

        let currentPeriod = this.period;
        Tine.Tinebase.common.assertComparable(currentPeriod);

        let period = this.getPeriod();
        Tine.Tinebase.common.assertComparable(period);
        if (String(currentPeriod) !== String(period)) {
            this.fireEvent('change', this, 'custom', period);
        }
    },
    update: function(periodStart, period) {
        let boundaries = ['from', 'until'];

        _.each(boundaries, (boundary) => {
            if (_.get(this, `${boundary}Picker`) && _.isDate(_.get(period, boundary))) {
                let date = period[boundary].clone();

                // constrain to other boundary
                let boundaryIdx = boundaries.indexOf(boundary);
                let otherBoundary = boundaries[(boundaryIdx+1)%2];
                let otherDate =  _.isDate(_.get(period, otherBoundary)) ?
                    _.get(period, otherBoundary) : new Date().clearTime();

                this[`${boundary}Picker`].setValue(
                    new Date(Math[boundaryIdx ? 'max' : 'min'](date, otherDate))
                );

                // save state
                _.set(this, `period.${boundary}`, _.get(period, boundary).clone());
            }
        });
    },
    render: function() {
        this.tb.addField(this.fromLabel);
        this.tb.addField(this.fromPicker);
        this.tb.addField(this.untilLabel);
        this.tb.addField(this.untilPicker);
    },
    hide: function() {
        this.fromLabel.hide();
        this.fromPicker.hide();
        this.untilLabel.hide();
        this.untilPicker.hide();
    },
    show: function() {
        this.fromLabel.show();
        this.fromPicker.show();
        this.untilLabel.show();
        this.untilPicker.show();
    },
    next: function() {
        let period = this.getPeriod();

        this.update({
            from: period.until,
            until: period.until.add(Date.MILLI,  period.until.getTime() - period.from.getTime())
                .add(Date.SECOND, period.until.format('Z') - period.from.format('Z'))
        });
    },
    prev: function() {
        let period = this.getPeriod();

        this.update({
            from: period.until.add(Date.MILLI, period.from.getTime() - period.until.getTime())
                .add(Date.SECOND, period.from.format('Z') - period.until.format('Z')),
            until: period.from
        });
    },
    getPeriod: function() {
        // let from = this.fromPicker.getValue() || new Date
        return {
            from: this.fromPicker.getValue().clone(),
            until: this.untilPicker.getValue().clone()
        };
    }
});
