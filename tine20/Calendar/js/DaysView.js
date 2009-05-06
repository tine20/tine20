/* 
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */

Ext.ns('Tine.Calendar');

Tine.Calendar.DaysView = function(config){
    Ext.apply(this, config);
    //this.addEvents();
    Tine.Calendar.DaysView.superclass.constructor.call(this);
};

Ext.extend(Tine.Calendar.DaysView, Ext.util.Observable, {
    /**
     * @cfg {Date} startDate
     * start date
     */
    startDate: new Date(),
    /**
     * @cfg {Number} numOfDays
     * number of days to display
     */
    numOfDays: 7,
    /**
     * @cfg {Number} timeGranularity
     * granularity of timegrid in minutes
     */
    timeGranularity: 30,
    /**
     * @cfg {Number} granularityUnitHeights
     * heights in px of a granularity unit
     */
    granularityUnitHeights: 18,
    /**
     * @property {Ext.data.Store} timeScale
     * store holding timescale 
     */
    timeScale: null,
    /**
     * @property {Ext.data.Store} dateScale
     * store holding datescale 
     */
    dateScale: null,
    /**
     * The amount of space to reserve for the scrollbar (defaults to 19 pixels)
     * @type Number
     */
    scrollOffset: 19,
    
    /**
     * @property {Tine.Calendar.Event} activeEvent
     * @private
     */
    activeEvent: null,
    /**
     * @property {Ext.data.Store}
     * @private
     */
    ds: null,
    
    
    /**
     * init this view
     * 
     * @param {Tine.Calendar.CalendarPanel} calPanel
     */
    init: function(calPanel) {
        this.calPanel = calPanel;
        
        this.startDate.setHours(0);
        this.startDate.setMinutes(0);
        this.startDate.setSeconds(0);
        
        this.endDate = this.startDate.add(Date.DAY, this.numOfDays+1);
        
        this.parallelScrollerEventsRegistry = new Tine.Calendar.ParallelEventsRegistry({dtStart: this.startDate, dtEnd: this.endDate});
        this.parallelWholeDayEventsRegistry = new Tine.Calendar.ParallelEventsRegistry({dtStart: this.startDate, dtEnd: this.endDate});
        
        this.initData(calPanel.store);
        
        this.initTimeScale();
        this.initDateScale();
        this.initTemplates();
    },
    
    /**
     * @private
     * @param {Ext.data.Store} ds
     */
    initData : function(ds){
        if(this.ds){
            this.ds.un("load", this.onLoad, this);
            this.ds.un("datachanged", this.onDataChange, this);
            this.ds.un("add", this.onAdd, this);
            this.ds.un("remove", this.onRemove, this);
            this.ds.un("update", this.onUpdate, this);
            this.ds.un("clear", this.onClear, this);
        }
        if(ds){
            ds.on("load", this.onLoad, this);
            ds.on("datachanged", this.onDataChange, this);
            ds.on("add", this.onAdd, this);
            ds.on("remove", this.onRemove, this);
            ds.on("update", this.onUpdate, this);
            ds.on("clear", this.onClear, this);
        }
        this.ds = ds;
    },
    
    /**
     * inits time scale
     * @private
     */
    initTimeScale: function() {
        var data = [];
        var scaleSize = Date.msDAY/(this.timeGranularity * Date.msMINUTE);
        var baseDate = this.startDate.clone();
        
        var minutes;
        for (var i=0; i<scaleSize; i++) {
            minutes = i * this.timeGranularity;
            data.push([i, minutes, minutes * Date.msMINUTE, baseDate.add(Date.MINUTE, minutes).format('H:i')]);
        }
        
        this.timeScale = new Ext.data.SimpleStore({
            fields: ['index', 'minutes', 'milliseconds', 'time'],
            data: data,
            id: 'index'
        });
    },
    
    /**
     * inits date scale
     * @private
     */
    initDateScale: function() {
        var data = [];
        var baseDate = this.startDate.clone(), date;
        
        for (var i=0; i<this.numOfDays; i++) {
            date = baseDate.add(Date.DAY, i)
            data.push([i, date, date.format('l, \\t\\he jS \\o\\f F')]);
        }
        
        this.dateScale = new Ext.data.SimpleStore({
            fields: ['index', 'date', 'dateString'],
            data: data,
            id: 'index'
        });
    },
    
    initDropZone: function() {
        this.dd = new Ext.dd.DropZone(this.scroller.dom, {
            ddGroup: 'cal-event',
            notifyOver : function(dd, e, data) {
                var target = Tine.Calendar.DaysView.prototype.getTargetDateTime.call(data.scope, e.getTarget());
                return target ? 'cal-daysviewpanel-event-drop-ok' : 'cal-daysviewpanel-event-drop-nodrop';
            },
            notifyOut : function() {
                console.log('notifyOut');
                //delete this.grid;
            },
            notifyDrop : function(dd, e, data) {
                var target = Tine.Calendar.DaysView.prototype.getTargetDateTime.call(data.scope, e.getTarget());
                console.log('droped event to ' + target);
                return !!target;
            }
        });
    },
    
    /**
     * @private
     */
    initDragZone: function() {
        this.scroller.ddScrollConfig = {
            vthresh: 50,
            hthresh: -1,
            frequency: 100,
            increment: 100
        };
        Ext.dd.ScrollManager.register(this.scroller);
        
        // init dragables
        this.dragZone = new Ext.dd.DragZone(this.el, {
            ddGroup: 'cal-event',
            daysView: this,
            scroll: false,
            containerScroll: true,
            
            getDragData: function(e) {
                var eventEl = e.getTarget('div.cal-daysviewpanel-event', 10);
                if (eventEl) {
                    var d = eventEl.cloneNode(true);
                    
                    var width = Ext.fly(eventEl).getWidth();
                    Ext.fly(d).setTop(0);
                    Ext.fly(d).setWidth(width);
                    d.id = Ext.id();
                    
                    return {
                        scope: this.daysView,
                        sourceEl: eventEl,
                        repairXY: Ext.fly(eventEl).getXY(),
                        repairWidth: Ext.fly(eventEl).getWidth(),
                        ddel: d
                    }
                }
            },
            
            getRepairXY: function() {
                return this.dragData.repairXY;
            }
        });
    },
    
    /**
     * renders the view
     */
    render: function() {
        this.templates.master.append(this.calPanel.body, {
            header: this.templates.header.applyTemplate({
                daysHeader: this.getDayHeaders()
            }),
            body: this.templates.body.applyTemplate({
                timeRows: this.getTimeRows(),
                dayColumns: this.getDayColumns()
            })
        });
        
        this.initElements();
    },
    
    /**
     * fill the events into the view
     */
    afterRender: function() {
        this.initDropZone();
        this.initDragZone();
        
        // calculate duration and parallels
        this.ds.each(function(event) {
            var registry = event.get('is_all_day_evnet') ? this.parallelWholeDayEventsRegistry : this.parallelScrollerEventsRegistry;
            registry.register(event);
        }, this);
        
        // put the events in
        this.ds.each(this.insertEvent, this);
        this.scrollToNow();
    },
    
    scrollToNow: function() {
        this.scroller.dom.scrollTop = this.getTimeOffset(new Date()) /2;
    },
    
    /**
     * renders a single event into this daysview
     * @param {Tine.Calendar.Model.Event} event
     * 
     * @todo Add support vor Events spanning over a day boundary
     */
    insertEvent: function(event) {
        
        // @todo fetch color from calendar
        var color = '#FD0000';
        
        // lighten up background
        var r = Math.min(this.hex2dec(color.substring(1,3)) + 150, 255);
        var g = Math.min(this.hex2dec(color.substring(3,5)) + 150, 255);
        var b = Math.min(this.hex2dec(color.substring(5,7)) + 150, 255);
        var bgColor = 'rgb(' + r + ',' + g + ',' + b + ')';
        
        var dtStart = event.get('dtstart');
        var dtEnd = event.get('dtend');
        
        var parallels = this.parallelScrollerEventsRegistry.getEvents(dtStart, dtEnd);
        var pos = parallels.indexOf(event);
        
        var eventEl = this.templates.event.append(this.dayCols[this.getDateColumn(dtStart)], {
            id: event.get('id'),
            summary: event.get('summary'),
            startTime: dtStart.format('H:i'),
            color: color,
            bgColor: bgColor,
            zIndex: 100,
            width: Math.round(90 * 1/event.parallels) + '%',
            height: (this.getTimeOffset(dtEnd) - this.getTimeOffset(dtStart)) + 'px',
            left: Math.round(pos * 90 * 1/event.parallels) + '%',
            top: this.getTimeOffset(dtStart) + 'px'
        }, true);
                
        new Ext.Resizable(eventEl, {
            handles: 's',
            disableTrackOver: true,
            dynamic: true,
            heightIncrement: this.granularityUnitHeights
        });
    },
    
    /**
     * returns events dom
     * @param {Tine.Calendar.Model.Event} event
     */
    getEvent: function(event) {
        //var colIdx = this.getDateColumn(event.get('dtstart'));
        //var eventDom = Ext.fly(this.dayCols[colIdx]).down(event.get('id'));
        //console.log(eventDom);
    },
    
    /**
     * removes a evnet from the dom
     * @param {Tine.Calendar.Model.Event} event
     */
    removeEvent: function(event) {
        var event = Ext.get(event.get('id'));
        if (event) {
            event.remove();
        }
    },
    
    /**
     * sets currentlcy active event
     * 
     * @param {Tine.Calendar.Event} event
     */
    setActiveEvent: function(event) {
        if (this.activeEvent) {
            var curEl = Ext.get(this.activeEvent.id);
            curEl.removeClass('cal-daysviewpanel-event-active');
            curEl.setStyle({'z-index': 100});
        }
        
        this.activeEvent = event;
        var el = Ext.get(event.id);
        el.addClass('cal-daysviewpanel-event-active');
        curEl.setStyle({'z-index': 1000});
    },
    
    /**
     * gets currentlcy active event
     * 
     * @return {Tine.Calendar.Event} event
     */
    getActiveEvent: function() {
        return this.activeEvent;
    },
    
    /**
     * @private
     */
    onDataChange : function(){
        this.refresh();
    },

    /**
     * @private
     */
    onClear : function(){
        this.refresh();
    },

    /**
     * @private
     */
    onUpdate : function(ds, record){
        this.refreshEvent(record);
    },

    /**
     * @private
     */
    onAdd : function(ds, records, index){
        for (var i=0; i<records.length; i++) {
            var event = records[i];
            
            var registry = event.get('is_all_day_evnet') ? this.parallelWholeDayEventsRegistry : this.parallelScrollerEventsRegistry;
            registry.register(event);
            
            var parallelEvents = registry.getEvents(event.get('dtstart'), event.get('dtend'));
            console.log(parallelEvents);
            
            for (var j=0; j<parallelEvents.length; j++) {
                this.removeEvent(parallelEvents[j]);
            }
            
            for (var j=0; j<parallelEvents.length; j++) {
                this.insertEvent(parallelEvents[j]);
            }
            
            this.setActiveEvent(event);
        }
    },

    /**
     * @private
     */
    onRemove : function(ds, record, index, isUpdate){
        if(isUpdate !== true){
            //this.fireEvent("beforeeventremoved", this, index, record);
        }
        this.removeEvent(record);
        if(isUpdate !== true){
            //this.fireEvent("eventremoved", this, index, record);
        }
    },

    /**
     * @private
     */
    onLoad : function(){
        this.scrollToNow();
    },
    
    
    hex2dec: function(hex) {
        var dec = 0;
        hex = hex.toString();
        var length = hex.length, multiplier, digit;
        for (var i=0; i<length; i++) {
            
            multiplier = Math.pow(16, (Math.abs(i - hex.length)-1));
            digit = parseInt(hex[i], 10);
            if (isNaN(digit)) {
                switch (hex[i].toString().toUpperCase()) {
                    case 'A': digit = 10;  break;
                    case 'B': digit = 11;  break;
                    case 'C': digit = 12;  break;
                    case 'D': digit = 13;  break;
                    case 'E': digit = 14;  break;
                    case 'F': digit = 15;  break;
                    default: return NaN;
                }
            }
            dec = dec + (multiplier * digit);
        }
        
        return dec;
    },
    
    /**
     * get date of a (event) target
     * 
     * @param {dom} target
     * @return {Date}
     */
    getTargetDateTime: function(target) {
        if (target.id.match(/^ext-gen\d+:\d+:\d+/)) {
            var parts = target.id.split(':');
            
            var timePart = this.timeScale.getAt(parts[2]);
            var datePart = this.dateScale.getAt(parts[1]);
            
            return datePart.get('date').add(Date.MINUTE, timePart.get('minutes'));
        }
    },
    
    /**
     * gets event el of target
     * 
     * @param {dom} target
     * @return {Tine.Calendar.Model.Event}
     */
    getTargetEvent: function(target) {
        var el = Ext.fly(target);
        if (el.hasClass('cal-daysviewpanel-event') || (el = el.up('[class=cal-daysviewpanel-event]', 5))) {
            return this.ds.getById(el.dom.id);
        }
    },
    
    getDateColumn: function(date) {
        return Math.floor((date.getTime() - this.startDate.getTime()) / Date.msDAY);
    },
    
    getTimeOffset: function(date) {
        var d = this.granularityUnitHeights / this.timeGranularity;
        
        return Math.round(d * ( 60 * date.getHours() + date.getMinutes()));
    },
    
    /**
     * fetches elements from our generated dom
     */
    initElements : function(){
        var E = Ext.Element;

        var el = this.calPanel.body.dom.firstChild;
        var cs = el.childNodes;

        this.el = new E(el);

        this.mainWrap = new E(cs[0]);
        this.mainHd = new E(this.mainWrap.dom.firstChild);

        this.innerHd = this.mainHd.dom.firstChild;
        
        this.scroller = new E(this.mainWrap.dom.childNodes[1]);
        this.scroller.setStyle('overflow-x', 'hidden');
        
        this.mainBody = new E(this.scroller.dom.firstChild);
        
        this.dayCols = this.mainBody.dom.firstChild.lastChild.childNodes;

        this.focusEl = new E(this.scroller.dom.childNodes[1]);
        this.focusEl.swallowEvent("click", true);
    },
    
    /**
     * layouts the view
     */
    layout: function() {
        if(!this.mainBody){
            return; // not rendered
        }
        var g = this.calPanel;
        var c = g.body;
        var csize = c.getSize(true);
        var vw = csize.width;
        
        this.el.setSize(csize.width, csize.height);

        var hdHeight = this.mainHd.getHeight();
        
        var vh = csize.height - (hdHeight);

        this.scroller.setSize(vw, vh);
        this.innerHd.style.width = (vw)+'px';
    },
    
    /**
     * returns HTML frament of the day headers
     */
    getDayHeaders: function() {
        var html = '';
        var width = 100/this.numOfDays;
        
        this.dateScale.each(function(date) {
            var index = date.get('index');
            html += this.templates.dayHeader.applyTemplate({
                day: date.get('dateString'),
                height: this.granularityUnitHeights,
                width: width + '%',
                left: index * width + '%'
            });
        }, this);
        
        return html;
    },
    
    /**
     * gets HTML fragment of the horizontal time rows
     */
    getTimeRows: function() {
        var html = '';
        this.timeScale.each(function(time){
            var index = time.get('index');
            html += this.templates.timeRow.applyTemplate({
                cls: index%2 ? 'cal-daysviewpanel-timeRow-off' : 'cal-daysviewpanel-timeRow-on',
                height: this.granularityUnitHeights + 'px',
                top: index * this.granularityUnitHeights + 'px',
                time: index%2 ? '' : time.get('time')
            });
        }, this);
        
        return html;
    },
    
    /**
     * gets HTML fragment of the day columns
     */
    getDayColumns: function() {
        var html = '';
        var width = 100/this.numOfDays;
        
        for (var i=0; i<this.numOfDays; i++) {
            html += this.templates.dayColumn.applyTemplate({
                width: width + '%',
                left: i * width + '%',
                overRows: this.getOverRows(i)
            });
        }
        
        return html;
    },
    
    /**
     * gets HTML fragment of the time over rows
     */
    getOverRows: function(dayIndex) {
        var html = '';
        var baseId = Ext.id();
        
        this.timeScale.each(function(time){
            var index = time.get('index');
            html += this.templates.overRow.applyTemplate({
                id: baseId + ':' + dayIndex + ':' + index,
                cls: 'cal-daysviewpanel-daycolumn-row-' + (index%2 ? 'off' : 'on'),
                height: this.granularityUnitHeights + 'px'
            });
        }, this);
        
        return html;
    },
    
    /**
     * inits all tempaltes of this view
     */
    initTemplates: function() {
        var ts = this.templates || {};
    
        ts.master = new Ext.XTemplate(
            '<div class="cal-daysviewpanel" hidefocus="true">',
                '<div class="cal-daysviewpanel-viewport">',
                    '<div class="cal-daysviewpanel-header"><div class="cal-daysviewpanel-header-inner"><div class="cal-daysviewpanel-header-offset">{header}</div></div><div class="x-clear"></div></div>',
                    '<div class="cal-daysviewpanel-scroller"><div class="cal-daysviewpanel-body">{body}</div><a href="#" class="cal-daysviewpanel-focus" tabIndex="-1"></a></div>',
                '</div>',
                //'<div class="cal-daysviewpanel-resize-marker">&#160;</div>',
                //'<div class="cal-daysviewpanel-resize-proxy">&#160;</div>',
            '</div>'
        );
        
        ts.header = new Ext.XTemplate(
            '<div class="cal-daysviewpanel-daysheader">{daysHeader}</div>' +
            '<div class="cal-daysviewpanel-wholedayheader">&#160;</div>'
        );
        
        ts.dayHeader = new Ext.XTemplate(
            '<div class="cal-daysviewpanel-dayheader" style="height: {height}; width: {width}; left: {left};">' + 
                '<div class="cal-daysviewpanel-dayheader-day-wrap">' +
                    '<div class="cal-daysviewpanel-dayheader-day">{day}</div>' +
                '</div>',
            '</div>'
        );
        
        ts.body = new Ext.XTemplate(
            '<div class="cal-daysviewpanel-body-inner">' +
                '{timeRows}' +
                '<div class="cal-daysviewpanel-body-daycolumns">{dayColumns}</div>' +
            '</div>'
        );
        
        ts.timeRow = new Ext.XTemplate(
            '<div class="{cls}" style="height: {height}; top: {top};">',
                '<div class="cal-daysviewpanel-timeRow-time">{time}</div>',
            '</div>'
        );
        
        ts.dayColumn = new Ext.XTemplate(
            '<div class="cal-daysviewpanel-body-daycolumn" style="left: {left}; width: {width};">' +
                '<div class="cal-daysviewpanel-body-daycolumn-inner">&#160;</div>'+
                '{overRows}' +
            '</div>'
        );
        
        ts.overRow = new Ext.XTemplate(
            '<div class="cal-daysviewpanel-daycolumn-row" style="height: {height};">' +
                '<div id="{id}" class="{cls}" >&#160;</div>'+
            '</div>'
        );
        
        ts.event = new Ext.XTemplate(
            '<div id="{id}", class="cal-daysviewpanel-event" style="width: {width}; height: {height}; left: {left}; top: {top}; background-color: {bgColor}; border-color: {color}; z-index: {zIndex};">' +
                '<div class="cal-daysviewpanel-event-header" style="background-color: {color};">' +
                    '<div class="cal-daysviewpanel-event-header-inner">{startTime}</div>' +
                    '<div class="cal-daysviewpanel-event-header-icons"></div>' +
                '</div>' +
                '<div class="cal-daysviewpanel-event-body">{[Ext.util.Format.nl2br(Ext.util.Format.htmlEncode(values.summary))]}</div>' +
            '</div>'
        );
        
        
        for(var k in ts){
            var t = ts[k];
            if(t && typeof t.compile == 'function' && !t.compiled){
                t.disableFormats = true;
                t.compile();
            }
        }

        this.templates = ts;
    }
});