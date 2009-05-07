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
    numOfDays: 4,
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
        this.dd = new Ext.dd.DropZone(this.mainWrap.dom, {
            ddGroup: 'cal-event',
            
            notifyOver : function(dd, e, data) {
                var sourceEl = Ext.fly(data.sourceEl);
                sourceEl.setStyle({'border-style': 'dashed'});
                sourceEl.setOpacity(0.5);
                
                var target = Tine.Calendar.DaysView.prototype.getTargetDateTime.call(data.scope, e.getTarget());
                if (target) {
                    var v = data.scope;
                    var event = v.ds.getById(data.sourceEl.id);
                    return Math.abs(target.getTime() - event.get('dtstart').getTime()) < Date.msMINUTE ? 'cal-daysviewpanel-event-drop-nodrop' : 'cal-daysviewpanel-event-drop-ok';
                }
                
                return 'cal-daysviewpanel-event-drop-nodrop';
            },
            
            notifyOut : function() {
                //console.log('notifyOut');
                //delete this.grid;
            },
            
            notifyDrop : function(dd, e, data) {
                var v = data.scope;
                
                var targetDate = v.getTargetDateTime(e.getTarget());
                
                if (targetDate) {
                    var event = v.ds.getById(data.sourceEl.id);
                    
                    event.beginEdit();
                    event.set('dtstart', targetDate);
                    
                    if (! event.get('is_all_day_event') && targetDate.is_all_day_event && event.duration < Date.msDAY) {
                        // draged from scroller -> dropped to allDay and duration less than a day
                        event.set('dtend', targetDate.add(Date.DAY, 1));
                    } else if (event.get('is_all_day_event') && !targetDate.is_all_day_event) {
                        // draged from allDay -> droped to scroller will be resetted to hone hour
                        event.set('dtend', targetDate.add(Date.HOUR, 1));
                    } else {
                        event.set('dtend', targetDate.add(Date.MILLI, event.duration));
                    }
                    
                    event.set('is_all_day_event', targetDate.is_all_day_event);
                    event.endEdit();
                }
                
                return !!targetDate;
            }
        });
    },
    
    /**
     * @private
     */
    initDragZone: function() {
        /* not a usable result ;-(*/
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
                    var event = this.daysView.ds.getById(eventEl.id);
                    this.daysView.setActiveEvent(event);
                    
                    var d = eventEl.cloneNode(true);
                    d.id = Ext.id();
                    
                    if (event.get('is_all_day_event')) { 
                        Ext.fly(d).setLeft(0);
                    } else {
                        var width = (Ext.fly(this.daysView.dayCols[0]).getWidth() * 0.9);
                        Ext.fly(d).setTop(0);
                        Ext.fly(d).setWidth(width);
                    }
                    
                    return {
                        scope: this.daysView,
                        sourceEl: eventEl,
                        //repairXY: Ext.fly(eventEl).getXY(),
                        //repairWidth: Ext.fly(eventEl).getWidth(),
                        ddel: d
                    }
                }
            },
            
            getRepairXY: function(e, dd) {
                Ext.fly(this.dragData.sourceEl).setStyle({'border-style': 'solid'});
                Ext.fly(this.dragData.sourceEl).setOpacity(1, 1);
                
                return Ext.fly(this.dragData.sourceEl).getXY();
            }
        });
    },
    
    /**
     * renders the view
     */
    render: function() {
        this.templates.master.append(this.calPanel.body, {
            header: this.templates.header.applyTemplate({
                daysHeader: this.getDayHeaders(),
                wholeDayCols: this.getWholeDayCols()
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
            var registry = event.get('is_all_day_event') ? this.parallelWholeDayEventsRegistry : this.parallelScrollerEventsRegistry;
            registry.register(event);
        }, this);
        
        // put the events in
        this.ds.each(this.insertEvent, this);
        this.scrollToNow();
        
        this.layout();
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
        var dtEnd = event.get('dtend').add(Date.SECOND, -1);
        
        var registry = event.get('is_all_day_event') ? this.parallelWholeDayEventsRegistry : this.parallelScrollerEventsRegistry;
        var parallels = registry.getEvents(dtStart, dtEnd);
        var pos = parallels.indexOf(event);
        
        if (event.get('is_all_day_event')) { 
            
            var offsetWidth = Ext.fly(this.wholeDayArea).getWidth();
            
            var eventEl = this.templates.wholeDayEvent.append(this.getWholeDayEl(pos), {
                id: event.get('id'),
                summary: event.get('summary'),
                startTime: dtStart.format('H:i'),
                color: color,
                bgColor: bgColor,
                zIndex: 100,
                width: Math.round(offsetWidth * (dtEnd.getTime() - dtStart.getTime()) / (this.numOfDays * Date.msDAY)) -5  +'px',
                height: '15px',
                left: Math.round(offsetWidth * (dtStart.getTime() - this.startDate.getTime()) / (this.numOfDays * Date.msDAY)) + 'px',
                top: '1px'
            }, true);
            
            new Ext.Resizable(eventEl, {
                handles: 'e',
                disableTrackOver: true,
                //dynamic: true,
                widthIncrement: Math.round(offsetWidth / this.numOfDays),
                minWidth: Math.round(offsetWidth / this.numOfDays),
                listeners: {
                    scope: this,
                    resize: this.onEventResize,
                    beforeresize: this.onBeforeEventResize
                }
            });
            
        } else {
            var eventEl = this.templates.event.append(this.getDateColumnEl(dtStart), {
                id: event.get('id'),
                summary: event.get('summary'),
                startTime: dtStart.format('H:i'),
                color: color,
                bgColor: bgColor,
                zIndex: 100,
                width: Math.round(90 * 1/event.parallels) + '%',
                //height: (this.getTimeOffset(dtEnd) - this.getTimeOffset(dtStart)) + 'px',
                height: this.getTimeHeight(dtStart, dtEnd) + 'px',
                left: Math.round(pos * 90 * 1/event.parallels) + '%',
                top: this.getTimeOffset(dtStart) + 'px'
            }, true);
                    
            new Ext.Resizable(eventEl, {
                handles: 's',
                disableTrackOver: true,
                heightIncrement: this.granularityUnitHeights/2,
                listeners: {
                    scope: this,
                    resize: this.onEventResize,
                    beforeresize: this.onBeforeEventResize
                }
            });
        }
    },
    
    /**
     * returns events dom
     * @param {Tine.Calendar.Model.Event} event
     * @return {Ext.Element}
     */
    getEvent: function(event) {
        return Ext.get(event.get('id'));
    },
    
    /**
     * removes a evnet from the dom
     * @param {Tine.Calendar.Model.Event} event
     */
    removeEvent: function(event) {
        var eventEl = this.getEvent(event);
        if (eventEl) {
            eventEl.remove();
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
        el.setStyle({'z-index': 1000});
    },
    
    /**
     * gets currentlcy active event
     * 
     * @return {Tine.Calendar.Event} event
     */
    getActiveEvent: function() {
        return this.activeEvent;
    },
    
    onBeforeEventResize: function(rz, e) {
        var event = this.ds.getById(rz.el.id);
        
        rz.el.setStyle({'border-style': 'dashed'});
        rz.el.setOpacity(0.5);
        
        // rz supresses resize event if element is not resized
        rz.onMouseUp = rz.onMouseUp.createSequence(function() {
            rz.el.setStyle({'border-style': 'solid'});
            rz.el.setOpacity(1);
        });
        
        this.setActiveEvent(event);
    },
    
    onEventResize: function(rz, width, height) {
        var event = this.ds.getById(rz.el.id);
        var originalDuration = event.duration / Date.msMINUTE;
        
        if(event.get('is_all_day_event')) {
            var offsetWidth = Ext.fly(this.wholeDayArea).getWidth();
            var duration = Math.round(width * (this.numOfDays / offsetWidth));
            event.set('dtend', event.get('dtstart').add(Date.DAY, duration));
            
        } else {
            var duration = Math.round(height * this.timeGranularity / this.granularityUnitHeights);
            
            // NOTE: recalculate new duration to neglegt diffs due to borders etc.
            duration = duration - (duration - originalDuration) % 15;
            
            event.set('dtend', event.get('dtstart').add(Date.MINUTE, duration));
        }
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
    onUpdate : function(ds, event){
        // relayout original context
        var originalRegistry = (event.modified.hasOwnProperty('is_all_day_event') ? event.modified.is_all_day_event : event.get('is_all_day_event')) ? 
            this.parallelWholeDayEventsRegistry : 
            this.parallelScrollerEventsRegistry;
        var originalDtstart = event.modified.hasOwnProperty('dtstart') ? event.modified.dtstart : event.get('dtstart');
        var originalDtend = event.modified.hasOwnProperty('dtend') ? event.modified.dtend : event.get('dtend');
            
        originalRegistry.unregister(event);
        
        var originalParallels = originalRegistry.getEvents(originalDtstart, originalDtend);
        for (var j=0; j<originalParallels.length; j++) {
            this.removeEvent(originalParallels[j]);
            this.insertEvent(originalParallels[j]);
        }
        
        // relayout actual context
        var registry = event.get('is_all_day_event') ? this.parallelWholeDayEventsRegistry : this.parallelScrollerEventsRegistry;
        registry.register(event);
        
        var parallelEvents = registry.getEvents(event.get('dtstart'), event.get('dtend'));
        
        for (var j=0; j<parallelEvents.length; j++) {
            this.removeEvent(parallelEvents[j]);
            this.insertEvent(parallelEvents[j]);
        }
        
        event.commit(true);
        this.setActiveEvent(this.getActiveEvent());
    },

    /**
     * @private
     */
    onAdd : function(ds, records, index){
        for (var i=0; i<records.length; i++) {
            var event = records[i];
            
            var registry = event.get('is_all_day_event') ? this.parallelWholeDayEventsRegistry : this.parallelScrollerEventsRegistry;
            registry.register(event);
            
            var parallelEvents = registry.getEvents(event.get('dtstart'), event.get('dtend'));
            
            for (var j=0; j<parallelEvents.length; j++) {
                this.removeEvent(parallelEvents[j]);
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
        
        /*
        if (event.get('is_all_day_event')) {
            this.checkWholeDayEls();
            this.layout();
        }
        */
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
        if (target.id.match(/^ext-gen\d+:\d+/)) {
            var parts = target.id.split(':');
            
            var date = this.dateScale.getAt(parts[1]).get('date');
            date.is_all_day_event = true;
            
            if (parts[2] ) {
                var timePart = this.timeScale.getAt(parts[2]);
                date = date.add(Date.MINUTE, timePart.get('minutes'));
                date.is_all_day_event = false;
            }   
            return date;
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
    
    getTimeOffset: function(date) {
        var d = this.granularityUnitHeights / this.timeGranularity;
        
        return Math.round(d * ( 60 * date.getHours() + date.getMinutes()));
    },
    
    getTimeHeight: function(dtStart, dtEnd) {
        var d = this.granularityUnitHeights / this.timeGranularity;
        //((dtEnd.getTime() - dtStart.getTime()) / Date.msMinute);
        return Math.round(d * ((dtEnd.getTime() - dtStart.getTime()) / Date.msMINUTE));
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
        
        this.wholeDayArea = this.innerHd.firstChild.childNodes[1];
        
        this.scroller = new E(this.mainWrap.dom.childNodes[1]);
        this.scroller.setStyle('overflow-x', 'hidden');
        
        this.mainBody = new E(this.scroller.dom.firstChild);
        
        this.dayCols = this.mainBody.dom.firstChild.lastChild.childNodes;

        this.focusEl = new E(this.scroller.dom.childNodes[1]);
        this.focusEl.swallowEvent("click", true);
    },
    
    getDateColumnEl: function(date) {
        return this.dayCols[Math.floor((date.add(Date.SECOND, 1).getTime() - this.startDate.getTime()) / Date.msDAY)];
    },
    
    getWholeDayEl: function(pos) {
        for (var i=this.wholeDayArea.childNodes.length; i<pos+3; i++) {
            Ext.DomHelper.insertBefore(this.wholeDayArea.lastChild, '<div class="cal-daysviewpanel-wholedayheader-pos">&#160;</div>');
            this.layout();
            //console.log('inserted slice: ' + i);
        }

        return this.wholeDayArea.childNodes[pos];
    },
    
    checkWholeDayEls: function() {
        var freeIdxs = [];
        for (var i=0; i<this.wholeDayArea.childNodes.length-1; i++) {
            console.log(this.wholeDayArea.childNodes[i]);
            if(this.wholeDayArea.childNodes[i].childNodes.length === 1) {
                freeIdxs.push(i);
            }
        }
        
        console.log(freeIdxs)
        for (var i=1; i<freeIdxs.length; i++) {
            console.log('hier');
            Ext.fly(this.wholeDayArea.childNodes[freeIdxs[i]]).remove();
        }
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
     * returns HTML fragment of the whole day cols
     */
    getWholeDayCols: function() {
        var html = '';
        var width = 100/this.numOfDays;
        
        var baseId = Ext.id();
        for (var i=0; i<this.numOfDays; i++) {
            html += this.templates.wholeDayCol.applyTemplate({
                //day: date.get('dateString'),
                //height: this.granularityUnitHeights,
                id: baseId + ':' + i,
                width: width + '%',
                left: i * width + '%'
            });
        };
        
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
            '<div class="cal-daysviewpanel-wholedayheader">' +
                '<div class="cal-daysviewpanel-wholedayheader-pos">&#160;</div>' +
                '<div class="cal-daysviewpanel-wholedayheader-daycols">{wholeDayCols}</div>' +
            '</div>'
        );
        
        ts.dayHeader = new Ext.XTemplate(
            '<div class="cal-daysviewpanel-dayheader" style="height: {height}; width: {width}; left: {left};">' + 
                '<div class="cal-daysviewpanel-dayheader-day-wrap">' +
                    '<div class="cal-daysviewpanel-dayheader-day">{day}</div>' +
                '</div>',
            '</div>'
        );
        
        ts.wholeDayCol = new Ext.XTemplate(
            '<div class="cal-daysviewpanel-body-wholedaycolumn" style="left: {left}; width: {width};">' +
                '<div id="{id}" class="cal-daysviewpanel-body-wholedaycolumn-over">&#160;</div>' +
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
            '<div id="{id}", class="cal-daysviewpanel-event" style="width: {width}; height: {height}; left: {left}; top: {top}; z-index: {zIndex}; background-color: {bgColor}; border-color: {color};">' +
                '<div class="cal-daysviewpanel-event-header" style="background-color: {color};">' +
                    '<div class="cal-daysviewpanel-event-header-inner">{startTime}</div>' +
                    '<div class="cal-daysviewpanel-event-header-icons"></div>' +
                '</div>' +
                '<div class="cal-daysviewpanel-event-body">{[Ext.util.Format.nl2br(Ext.util.Format.htmlEncode(values.summary))]}</div>' +
            '</div>'
        );
        
        ts.wholeDayEvent = new Ext.XTemplate(
            '<div id="{id}", class="cal-daysviewpanel-event" style="width: {width}; height: {height}; left: {left}; top: {top}; z-index: {zIndex}; background-color: {bgColor}; border-color: {color};">' +
                '<div class="cal-daysviewpanel-wholedayevent-body">{[Ext.util.Format.nl2br(Ext.util.Format.htmlEncode(values.summary))]}</div>' +
                '<div class="cal-daysviewpanel-event-icons"></div>' +
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