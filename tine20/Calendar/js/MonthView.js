/* 
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */

Ext.ns('Tine.Calendar');

Tine.Calendar.MonthView = function(config){
    Ext.apply(this, config);
    Tine.Calendar.MonthView.superclass.constructor.call(this);
    
    this.addEvents(
        /**
         * @event changeView
         * fired if user wants to change view
         * @param {String} requested view name
         * @param {mixed} start param of requested view
         */
        'changeView',
        /**
         * @event changePeriod
         * fired when period changed
         * @param {Object} period
         */
        'changePeriod',
        /**
         * @event addEvent
         * fired when a new event got inserted
         * 
         * @param {Tine.Calendar.Event} event
         */
        'addEvent',
        /**
         * @event updateEvent
         * fired when an event go resised/moved
         * 
         * @param {Tine.Calendar.Event} event
         */
        'updateEvent'
    );
};

Ext.extend(Tine.Calendar.MonthView, Ext.util.Observable, {
    /**
     * @cfg {Date} startDate
     * start date
     */
    startDate: new Date().clearTime(),
    /**
     * @cfg {String} newEventSummary
     */
    newEventSummary: 'New Event',
    /**
     * @cfg {String} calWeekString
     */
    calWeekString: 'WK',
    /**
     * @cfg {Array} monthNames
     * An array of textual month names which can be overriden for localization support (defaults to Date.monthNames)
     */
    monthNames : Date.monthNames,
    /**
     * @cfg {Array} dayNames
     * An array of textual day names which can be overriden for localization support (defaults to Date.dayNames)
     */
    dayNames : Date.dayNames,
    /**
     * @cfg {Number} startDay
     * Day index at which the week should begin, 0-based
     */
    startDay: Ext.DatePicker.prototype.startDay,
    /**
     * @private {Date} toDay
     */
    toDay: null,
    /**
     * @private {Array} dateMesh
     */
    dateMesh: null,
    /**
     * @private {Tine.Calendar.ParallelEventsRegistry} parallelEventsRegistry
     */
    parallelEventsRegistry: null,
    
    /**
     * @private
     */
    afterRender: function() {
        this.initElements();
        this.el.on('dblclick', this.onDblClick, this);
        
        this.updatePeriod({from: this.startDate});
        
        // create parallels registry
        this.parallelEventsRegistry = new Tine.Calendar.ParallelEventsRegistry({
            dtStart: this.dateMesh[0], 
            dtEnd: this.dateMesh[this.dateMesh.length-1].add(Date.DAY, 1).add(Date.SECOND, -1),
            granularity: 60*24
        });
        
        // calculate duration and parallels
        this.ds.each(function(event) {
            this.parallelEventsRegistry.register(event);
        }, this);
        
        this.ds.each(this.insertEvent, this);
        
        //this.layoutDayCells();
    },
    
    /**
     * @private calculates mesh of dates for month this.startDate is in
     */
    calcDateMesh: function() {
        var mesh = [];
        var d = Date.parseDate(this.startDate.format('Y-m') + '-01 00:00:00', Date.patterns.ISO8601Long);
        while(d.getDay() != this.startDay) {
            d = d.add(Date.DAY, -1);
        }
        
        while(d.getMonth() != this.startDate.add(Date.MONTH, 1).getMonth()) {
            for (var i=0; i<7; i++) {
                mesh.push(d.add(Date.DAY, i).clone());
            }
            d = d.add(Date.DAY, 7);
        }

        this.dateMesh = mesh;
    },
    
    /**
     * returns index of dateCell given date is in
     * @param {Date} date
     */
    getDayCellIndex: function(date) {
        return Math.round((date.clearTime(true).getTime() - this.dateMesh[0].getTime())/Date.msDAY);
    },
    
    /**
     * @private returns a child div in requested position
     * 
     * @param {dom} dayCell
     * @param {Number} pos
     * @return {dom}
     */
    getEventPosEl: function(dayCell, pos) {
        pos = Math.abs(pos);
        
        for (var i=dayCell.childNodes.length; i<=pos; i++) {
            Ext.DomHelper.insertAfter(dayCell.lastChild, '<div />');
            //console.log('inserted slice: ' + i);
        }

        return dayCell.childNodes[pos];
    },
    
    /**
     * returns period of currently displayed month
     * @return {Object}
     */
    getPeriod: function() {
        return {
            from: this.dateMesh[0],
            until: this.dateMesh[this.dateMesh.length -1]
        };    
    },
    
    /**
     * @private
     * @param {Tine.Calendar.CalendarPanel} calPanel
     */
    init: function(calPanel) {
        this.calPanel = calPanel;
        
        this.initData(calPanel.store);
        this.initTemplates();
    },
    
    /**
     * @private
     * @param {Ext.data.Store} ds
     */
    initData : function(ds){
        if(this.ds){
            this.ds.un("beforeload", this.onBeforeLoad, this);
            this.ds.un("load", this.onLoad, this);
            //this.ds.un("datachanged", this.onDataChange, this);
            this.ds.un("add", this.onAdd, this);
            this.ds.un("remove", this.onRemove, this);
            this.ds.un("update", this.onUpdate, this);
            //this.ds.un("clear", this.onClear, this);
        }
        if(ds){
            ds.on("beforeload", this.onBeforeLoad, this);
            ds.on("load", this.onLoad, this);
           // ds.on("datachanged", this.onDataChange, this);
            ds.on("add", this.onAdd, this);
            ds.on("remove", this.onRemove, this);
            ds.on("update", this.onUpdate, this);
            //ds.on("clear", this.onClear, this);
        }
        this.ds = ds;
    },
    
    /**
     * @private
     */
    initElements: function() {
        var E = Ext.Element;

        var el = this.calPanel.body.dom.firstChild;
        var cs = el.childNodes;

        this.el = new E(el);
        
        this.mainHd = new E(this.el.dom.firstChild);
        this.mainBody = new E(this.el.dom.lastChild);
        
        this.dayCells = Ext.DomQuery.select('td[class=cal-monthview-daycell]', this.mainBody.dom);
    },
    
    /**
     * inits all tempaltes of this view
     */
    initTemplates: function() {
        var ts = this.templates || {};
    
        ts.allDayEvent = new Ext.XTemplate(
            '<div id="{id}" class="cal-monthview-alldayevent {extraCls}" style="background-color: {bgColor};">' +
                '<tpl if="values.showInfo">' +
                    '<div class="cal-event-icon {iconCls}">' +
                        '<div class="cal-monthview-alldayevent-summary">{[Ext.util.Format.htmlEncode(values.summary)]}</div>' +
                    '</div>' +
                '</tpl>' +
            '</div>'
        );
        
        ts.event = new Ext.XTemplate(
            '<div id="{id}" class="cal-monthview-event {extraCls}" style="color: {color};">' +
                '<div class="cal-event-icon {iconCls}">' +
                    '<div class="cal-monthview-event-summary">{startTime} {[Ext.util.Format.htmlEncode(values.summary)]}</div>' +
                '</div>' +
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
    },
    
    /**
     * @private
     * @param {Tine.Calendar.Event} event
     */
    insertEvent: function(event) {
        var dtStart = event.get('dtstart');
        var startCellNumber = this.getDayCellIndex(dtStart);
        var dtEnd = event.get('dtend');
        var endCellNumber = this.getDayCellIndex(dtEnd);
        
        // skip out of range events
        if (endCellNumber < 0 || startCellNumber >= this.dateMesh.length) {
            return;
        }
        
        var parallels = this.parallelEventsRegistry.getEvents(dtStart, dtEnd, true);
        var pos = parallels.indexOf(event);
        
        var is_all_day_event = event.get('is_all_day_event') || startCellNumber != endCellNumber;
        
        var data = {
            startTime: dtStart.format('H:i'),
            summary: event.get('summary'),
            color: '#FD0000',
            bgColor: '#FF9696'
        };
        
        for (var i=Math.max(startCellNumber, 0); i<=endCellNumber; i++) {
            var dayBody = this.dayCells[i].lastChild;
            var tmpl = this.templates.event;
            data.extraCls = '';
            
            if (is_all_day_event) {
                tmpl = this.templates.allDayEvent;
                data.color = 'black';
                
                if (i > startCellNumber) {
                    data.extraCls += ' cal-monthview-alldayevent-cropleft';
                }
                if (i < endCellNumber) {
                    data.extraCls += ' cal-monthview-alldayevent-cropright';
                }
                
                // show icon on startCell and leftCells
                data.showInfo = i == startCellNumber || i%7 == 0;
            } 
            
            var posEl = this.getEventPosEl(dayBody, pos);
            var eventEl = tmpl.overwrite(posEl, data, true);
        }
        
        
        //console.log(event);
        //console.log(dayCell)
    },
    
    layout: function() {
        if(!this.mainBody){
            return; // not rendered
        }
        
        var g = this.calPanel;
        var c = g.body;
        var csize = c.getSize(true);
        var vw = csize.width;
        
        //this.el.setSize(csize.width, csize.height);
        var hsize = this.mainHd.getSize(true);
        
        var hdCels = this.mainHd.dom.firstChild.childNodes;
        Ext.fly(hdCels[0]).setWidth(50);
        for (var i=1; i<hdCels.length; i++) {
            Ext.get(hdCels[i]).setWidth((vw-50)/7);
        }
        
        var rowHeight = ((csize.height - hsize.height - 2) / (this.dateMesh.length > 35 ? 6 : 5)) - 1;

        var calRows = this.mainBody.dom.childNodes;
        for (var i=0; i<calRows.length; i++) {
            Ext.get(calRows[i]).setHeight(rowHeight);
        }
        
        var dhsize = Ext.get(this.dayCells[0].firstChild).getSize();
        this.dayCellsHeight = rowHeight - dhsize.height;

        for (var i=0; i<this.dayCells.length; i++) {
            Ext.get(this.dayCells[i].lastChild).setSize((vw-50)/7 ,this.dayCellsHeight);
        }
        
        this.layoutDayCells();
    },
    
    /**
     * layouts the contents (sets 'more items marker')
     */
    layoutDayCells: function() {
        for (var i=0; i<this.dayCells.length; i++) {
            if (this.dayCells[i].lastChild.childNodes.length > 1) {
                for (var j=0, height=0, hideCount=0; j<this.dayCells[i].lastChild.childNodes.length; j++) {
                    var eventEl = Ext.get(this.dayCells[i].lastChild.childNodes[j]);
                    height += eventEl.getHeight();
                    
                    eventEl[height > this.dayCellsHeight ? 'hide' : 'show']();
                    
                    if (height > this.dayCellsHeight) {
                        hideCount++;
                    }
                }
                
                if (hideCount > 0) {
                    console.log(hideCount + 'events hidden in this cell');
                }
            }
        }
    },
    
    /**
     * @private
     */
    onAdd : function(ds, records, index){
        for (var i=0; i<records.length; i++) {
            var event = records[i];
            this.parallelEventsRegistry.register(event);
            
            var parallelEvents = this.parallelEventsRegistry.getEvents(event.get('dtstart'), event.get('dtend'));
            
            for (var j=0; j<parallelEvents.length; j++) {
                //this.removeEvent(parallelEvents[j]);
                //this.insertEvent(parallelEvents[j]);
            }
            
            //this.setActiveEvent(event);
        }
    },
    
    /**
     * @private
     */
    onBeforeLoad: function() {
        console.log('onBeforeLoad');
        //this.ds.each(this.removeEvent, this);
    },
    
    onDblClick: function(e, target) {
        e.stopEvent();
        switch(target.className) {
            case 'cal-monthview-wkcell':
                var wkIndex = Ext.DomQuery.select('td[class=cal-monthview-wkcell]', this.mainBody.dom).indexOf(target);
                var startDate = this.dateMesh[7*wkIndex];
                this.fireEvent('changeView', 'week', startDate);
                break;
                
            case 'cal-monthview-dayheader-inner':
                var dateIndex = this.dayCells.indexOf(target.parentNode.parentNode);
                var date = this.dateMesh[dateIndex];
                this.fireEvent('changeView', 'day', date);
                break;
                
            case 'cal-monthview-daycell':
                var dateIndex = this.dayCells.indexOf(target);
                var date = this.dateMesh[dateIndex];
                //console.log("Create event at: " + date.format('Y-m-d'));
                break;
        }
        
        //console.log(Ext.get(target));
    },
    
    /**
     * @private
     */
    onLoad : function(){
        console.log('onLoad');
        //this.ds.each(this.insertEvent, this);
    },
    
    /**
     * @private
     */
    onRemove : function(ds, event, index, isUpdate){
        this.parallelEventsRegistry.unregister(event);
        //this.removeEvent(event);
    },
    
    /**
     * @private
     */
    onUpdate : function(ds, event){
        /*
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
        */
    },
    
    render: function() {
        var m = [
             '<table class="cal-monthview-inner" cellspacing="0"><thead><tr class="cal-monthview-inner-header" height="23px">',
             "<th class='cal-monthview-wkcell-header'><span >", this.calWeekString, "</span></th>"
         ];
        for(var i = 0; i < 7; i++){
            var d = this.startDay+i;
            if(d > 6){
                d = d-7;
            }
            m.push("<th class='cal-monthview-daycell'><span>", this.dayNames[d], "</span></th>");
        }
        m[m.length] = "</tr></thead><tbody><tr><td class='cal-monthview-wkcell'></td>";
        for(var i = 0; i < 42; i++) {
            if(i % 7 == 0 && i != 0){
                m[m.length] = "</tr><tr><td class='cal-monthview-wkcell'></td>";
            }
            m[m.length] = 
                '<td class="cal-monthview-daycell">' +
                    '<div class="cal-monthview-dayheader">' +
                        '<div class="cal-monthview-dayheader-inner"></div>' +
                    '</div>' +
                    '<div class="cal-monthview-daybody"><div /></div>' +
                '</td>';
        }
        m.push('</tr></tbody></table></td></tr>');
                
        var el = this.calPanel.body.dom;
        el.className = "cal-monthview";
        el.innerHTML = m.join("");

        //container.dom.insertBefore(el, position);
        //this.calPanel.body
    },
    
    updatePeriod: function(period) {
        this.toDay = new Date().clearTime();
        this.startDate = period.from;
        this.calcDateMesh();

        // update dates and bg colors
        var dayHeaders = Ext.DomQuery.select('div[class=cal-monthview-dayheader-inner]', this.mainBody.dom);
        for(var i=0; i<this.dateMesh.length; i++) {
            this.dayCells[i].style.background = this.dateMesh[i].getMonth() == this.startDate.getMonth() ? '#FFFFFF' : '#F9F9F9';
            if (this.dateMesh[i].getTime() == this.toDay.getTime()) {
                this.dayCells[i].style.background = '#EBF3FD';
            }
                
            dayHeaders[i].innerHTML = this.dateMesh[i].format('j');
        }
        
        // update weeks
        var wkCells = Ext.DomQuery.select('td[class=cal-monthview-wkcell]', this.mainBody.dom);
        for(var i=0; i<wkCells.length; i++) {
            if (this.dateMesh.length > i*7 +1) {
                // NOTE: '+1' is to ensure we display the ISO8601 based week where weeks always start on monday!
                wkCells[i].innerHTML = this.dateMesh[i*7 +1].getWeekOfYear();
                //Ext.fly(wkCells[i]).unselectable(); // this supresses events ;-(
            }
        }
        
        this.layout();
        this.fireEvent('changePeriod', period);
    }
});