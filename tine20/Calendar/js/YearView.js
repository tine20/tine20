/* 
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Nico Hessler <tine20@nico-hessler.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine.Calendar');

/**
 * @namespace Tine.Calendar
 * @class Tine.Calendar.YearView
 * @extends Ext.util.Observable
 * @constructor
 * @param {Object} config
 */
Tine.Calendar.YearView = function(config){
    Ext.apply(this, config);
    Tine.Calendar.YearView.superclass.constructor.call(this);
    
    this.printRenderer = Tine.Calendar.Printer.YearViewRenderer;
    
    this.addEvents(
        /**
         * @event click
         * fired if an event got clicked
         * @param {Tine.Calendar.Model.Event} event
         * @param {Ext.EventObject} e
         */
        'click',
        /**
         * @event contextmenu
         * fired if an event got contextmenu 
         * @param {Ext.EventObject} e
         */
        'contextmenu',
        /**
         * @event dblclick
         * fired if an event got dblclicked
         * @param {Tine.Calendar.Model.Event} event
         * @param {Ext.EventObject} e
         */
        'dblclick',
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
         * @param {Tine.Calendar.Model.Event} event
         */
        'addEvent',
        /**
         * @event updateEvent
         * fired when an event go resised/moved
         * 
         * @param {Tine.Calendar.Model.Event} event
         */
        'updateEvent'
    );
};

Ext.extend(Tine.Calendar.YearView, Ext.Container, {
    /**
     * @cfg {Date} startDate
     * start date
     */
    startDate: new Date().clearTime(),
    /**
     * @cfg {String} newEventSummary
     * _('New Event')
     */
    newEventSummary: 'New Event',
    /**
     * @cfg String moreString
     * _('{0} more...')
     */
    moreString: '{0} more...',
    /**
     * @cfg {Array} monthNames
     * An array of textual month names which can be overriden for localization support (defaults to Date.monthNames)
     */
    monthNames : Date.monthNames,
    /**
     * @cfg {Boolean} denyDragOnMissingEditGrant
     * deny drag action if edit grant for event is missing
     */
    denyDragOnMissingEditGrant: true,
    /**
     * @property {Tine.Calendar.Model.Event} activeEvent
     * @private
     */
    activeEvent: null,
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
    
    cls: "cal-yearview",
    
    /**
     * @private
     */
    afterRender: function() {
        Tine.Calendar.YearView.superclass.afterRender.apply(this, arguments);
        
        this.initElements();
        
        this.getSelectionModel().init(this);
        
        this.mon(this.el, 'mousedown', this.onMouseDown, this);
        this.mon(this.el, 'click', this.onClick, this);
        this.mon(this.el, 'contextmenu', this.onContextMenu, this);
        this.mon(this.el, 'keydown', this.onKeyDown, this);
        
        this.initDragZone();
        this.initDropZone();
        
        this.updatePeriod({from: this.period.from});
        
        if (this.store.getCount()) {
            this.onLoad.apply(this);
        }
        
        this.rendered = true;
    },
    
    /**
     * @private calculates mesh of dates for month this.startDate is in
     */
    calcDateMesh: function() {
        var mesh = [];
        var d = Date.parseDate(this.startDate.format('Y') + '-01-01 00:00:00', Date.patterns.ISO8601Long);

        for (var day=0; day<31; day++) {
            for (var month=0; month<12; month++) {
                var cell = d.add(Date.MONTH, month).add(Date.DAY, day);
                if(cell.getMonth() == month) {
                    mesh.push(cell.clone());
                }
                else {
                    mesh.push(null);
                }
            }
        }
        this.dateMesh = mesh;
    },
    
    /**
     * gets currentlcy active event
     * 
     * @return {Tine.Calendar.Model.Event} event
     */
    getActiveEvent: function() {
        return this.activeEvent;
    },
    
    /**
     * returns index of dateCell given date is in
     * @param {Date} date
     */
    getDayCellIndex: function(date) {
        return date.getMonth() + (date.getDate() - 1) * 12;
    },
    
    /**
     * @private returns a child div in requested position
     * 
     * @param {dom} dayCell
     * @param {Number} pos
     * @return {dom}
     */
    getEventSlice: function(dayCell, pos) {
        pos = Math.abs(pos);
        
        for (var i=dayCell.childNodes.length; i<=pos; i++) {
            Ext.DomHelper.insertAfter(dayCell.lastChild, '<div class="cal-yearview-eventslice"/>');
        }
        
        // make sure cell is empty
        while (dayCell.childNodes[pos].innerHTML) {
            pos++;
            
            if (pos > dayCell.childNodes.length -1) {
                Ext.DomHelper.insertAfter(dayCell.lastChild, '<div class="cal-yearview-eventslice"/>');
            }
        }
        
        return dayCell.childNodes[pos];
    },
    
    /**
     * returns period of currently displayed month
     * @return {Object}
     */
    getPeriod: function() {
        // happens if month view is rendered first
        if (! this.dateMesh) {
            this.calcDateMesh();
        }
        
        return {
            from: this.dateMesh[0],
            until: this.dateMesh[this.dateMesh.length -1].add(Date.DAY, 1)
        };
    },
    
    getSelectionModel: function() {
        return this.selModel;
    },
    
    getTargetDateTime: function(e) {
        var target = e.getTarget('td.cal-yearview-daycell', 3);
        
        if (target) {
            var dateIdx = this.dayCells.indexOf(target);
            var date = this.dateMesh[this.dayCells.indexOf(target)];
        
            // set some default time:
            date.add(Date.HOUR, 10);
            return date;
        }
    },
    
    getTargetEvent: function(e) {
        var target = e.getTarget('div.cal-yearview-event', 10);
        
        if (target) {
            var parts = target.id.split(':');
            var event = this.store.getById(parts[1]);
        }
        
        return event;
    },
    
    /**
     * init month view
     */
    initComponent: function() {
        this.app = Tine.Tinebase.appMgr.get('Calendar');
        this.newEventSummary =  this.app.i18n._hidden(this.newEventSummary);
        this.calWeekString   =  this.app.i18n._hidden(this.calWeekString);
        this.moreString      =  this.app.i18n._hidden(this.moreString);
        
        // redefine this props in case ext translations got included after this component
        this.monthNames = Date.monthNames;
        this.dayNames   = Date.dayNames;
        
        this.initData(this.store);
        this.initTemplates();
        
        if (! this.selModel) {
            this.selModel = this.selModel || new Tine.Calendar.EventSelectionModel();
        }
        Tine.Calendar.YearView.superclass.initComponent.apply(this, arguments);
    },
    
    /**
     * @private
     * @param {Ext.data.Store} ds
     */
    initData : function(ds){
        if(this.store){
            this.store.un("load", this.onLoad, this);
            this.store.un("beforeload", this.onBeforeLoad, this);
            this.store.un("add", this.onAdd, this);
            this.store.un("remove", this.onRemove, this);
            this.store.un("update", this.onUpdate, this);
        }
        if(ds){
            ds.on("load", this.onLoad, this);
            ds.on("beforeload", this.onBeforeLoad, this);
            ds.on("add", this.onAdd, this);
            ds.on("remove", this.onRemove, this);
            ds.on("update", this.onUpdate, this);
        }
        this.store = ds;
    },
    
    /**
     * @private
     */
    initDragZone: function() {
        this.dragZone = new Ext.dd.DragZone(this.el, {
            ddGroup: 'cal-event',
            view: this,
            scroll: false,
            
            getDragData: function(e) {
                var eventEl = e.getTarget('div.cal-yearview-event', 10);
                if (eventEl) {
                    var parts = eventEl.id.split(':');
                    var event = this.view.store.getById(parts[1]);
                    
                    // don't allow dragging with missing edit grant
                    if (this.view.denyDragOnMissingEditGrant && ! event.get('editGrant')) {
                        return false;
                    }
                    
                    // we need to clone an event with summary in
                    var d = Ext.get(event.ui.domIds[0]).dom.cloneNode(true);
                    
                    var width = Ext.fly(eventEl).getWidth() * event.ui.domIds.length;
                    
                    Ext.fly(d).setWidth(width);
                    Ext.fly(d).setOpacity(0.5);
                    d.id = Ext.id();
                    
                    return {
                        scope: this.view,
                        sourceEl: eventEl,
                        event: event,
                        ddel: d,
                        selections: this.view.getSelectionModel().getSelectedEvents()
                    }
                }
            },
            
            getRepairXY: function(e, dd) {
                Ext.fly(this.dragData.sourceEl).setOpacity(1, 1);
                return Ext.fly(this.dragData.sourceEl).getXY();
            }
        });
    },
    
    initDropZone: function() {
        this.dd = new Ext.dd.DropZone(this.el.dom, {
            ddGroup: 'cal-event',
            
            notifyOver : function(dd, e, data) {
                var target = e.getTarget('td.cal-yearview-daycell', 3);
                var event = data.event;
                
                // we dont support multiple dropping yet
                if (event) {
                    data.scope.getSelectionModel().select(event);
                }
                return target && event && event.get('editGrant') ? 'cal-daysviewpanel-event-drop-ok' : 'cal-daysviewpanel-event-drop-nodrop';
            },
            
            notifyDrop : function(dd, e, data) {
                var v = data.scope;
                
                var target = e.getTarget('td.cal-yearview-daycell', 3);
                var targetDate = v.dateMesh[v.dayCells.indexOf(target)];
                
                if (targetDate) {
                   var event = data.event;
                    
                    var diff = (targetDate.getTime() - event.get('dtstart').clearTime(true).getTime()) / Date.msDAY;
                    if (! diff  || ! event.get('editGrant')) {
                        return false;
                    }
                    
                    event.beginEdit();
                    event.set('dtstart', event.get('dtstart').add(Date.DAY, diff));
                    event.set('dtend', event.get('dtend').add(Date.DAY, diff));
                    event.endEdit();
                    
                    v.fireEvent('updateEvent', event);
                }
                
                return !!targetDate;
            }
        });
    },
    
    /**
     * @private
     */
    initElements: function() {
        var E = Ext.Element;

        this.focusEl = new E(this.el.dom.firstChild);
        
        this.mainHd = new E(this.el.dom.lastChild.firstChild);
        this.mainBody = new E(this.el.dom.lastChild.lastChild);
        
        this.dayCells = Ext.DomQuery.select('td[class=cal-yearview-daycell]', this.mainBody.dom);
    },
    
    /**
     * inits all tempaltes of this view
     */
    initTemplates: function() {
        var ts = this.templates || {};
        
        ts.event = new Ext.XTemplate(
            '<div id="{id}" class="cal-yearview-event {extraCls}" style="background-color: {bgColor};"">' +
                //'<div class="cal-yearview-event-summary"> {[Ext.util.Format.htmlEncode(values.summary)]}</div>' +
                '{[Ext.util.Format.htmlEncode(values.summary)]}' +
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
     * @param {Tine.Calendar.Model.Event} event
     */
    insertEvent: function(event) {
        event.ui = new Tine.Calendar.YearViewEventUI(event);
        //event.ui.render(this);
        
        var dtStart = event.get('dtstart');
        var startCellNumber = this.getDayCellIndex(dtStart);
        
        var dtEnd = event.get('dtend');
        // 00:00 in users timezone is a spechial case where the user expects
        // something like 24:00 and not 00:00
        if (dtEnd.format('H:i') == '00:00') {
            dtEnd = dtEnd.add(Date.MINUTE, -1);
        }
        var endCellNumber = this.getDayCellIndex(dtEnd);
        
        // skip out of range events
        if (endCellNumber < 0 || startCellNumber >= this.dateMesh.length) {
            return;
        }
        
        var pos = this.parallelEventsRegistry.getPosition(event);
        
        // save some layout info
        event.ui.colorSet = event.colorSet = Tine.Calendar.colorMgr.getColor(event);
        event.ui.color = event.ui.colorSet.color;
        event.ui.bgColor = event.ui.colorSet.light;
        
        var data = {
            startTime: dtStart.format('H:i'),
            summary: event.get('summary'),
            color: event.ui.color,
            bgColor: event.ui.bgColor,
            width: '100%'
        };
        
        for (var i=Math.max(startCellNumber, 0); i<=Math.min(endCellNumber, this.dayCells.length-1) ; i=i+12) {
            var col = i%12, row = Math.floor(i/12);
            //var row = i%12, col = Math.floor(i/12);
            
            data.id = Ext.id() + '-event:' + event.get('id');
            event.ui.domIds.push(data.id);
                
            var tmpl = this.templates.event;
            data.extraCls = event.get('editGrant') ? 'cal-yearview-event-editgrant' : '';
            data.extraCls += ' cal-status-' + event.get('status');
             
            if (data.showInfo && startCellNumber != endCellNumber) {
                var cols = (row == Math.floor(endCellNumber/12) ? endCellNumber%12 : 11) - col +1;
                data.width = 100 * cols + '%';
            }

            if (i > startCellNumber) {
                data.extraCls += ' cal-yearview-event-croptop';
            }
            if (i < endCellNumber) {
                data.extraCls += ' cal-yearview-event-cropbottom';
            }
            
            var posEl = this.getEventSlice(this.dayCells[i].lastChild, pos);
            var eventEl = tmpl.overwrite(posEl, data, true);
            
            if (event.dirty) {
                eventEl.setOpacity(0.5);
                
                // the event was selected before
                event.ui.onSelectedChange(true);
            }
        }
    },
    
    onLayout: function() {
        Tine.Calendar.YearView.superclass.onLayout.apply(this, arguments);
        
        if(!this.mainBody){
            return; // not rendered
        }
        
        var csize = this.container.getSize(true);
        var vw = csize.width;
        var vh = csize.height;
        
        var hsize = this.mainHd.getSize(true);
        
        this.dayCellsHeight = vh/31-2; 
        this.dayCellsWidth = vw/12-25; 

        var hdCels = this.mainHd.dom.firstChild.childNodes;
        for (var i=0; i<hdCels.length; i++) {
            Ext.get(hdCels[i]).setWidth(vw/12);
        }
        
        for (var i=0; i<this.dayCells.length; i++) {
            Ext.get(this.dayCells[i].lastChild).setSize(this.dayCellsWidth, Math.max(this.dayCellsHeight, 17));
        }
        
        this.layoutDayCells();
    },
    
    onDestroy: function() {
        this.removeAllEvents();
        this.initData(false);
        this.purgeListeners();
        
        Tine.Calendar.YearView.superclass.onDestroy.apply(this, arguments);
    },
    
    /**
     * layouts the contents (sets 'more items marker')
     */
    layoutDayCells: function() {
        for (var i=0; i<this.dayCells.length; i++) {
            if (this.dayCells[i].lastChild.childNodes.length > 1) {
                this.layoutDayCell(this.dayCells[i], true, true);
            }
        }
    },
    
    /**
     * layouts a single day cell
     * 
     * @param {dom} cell
     * @param {Bool} hideOverflow
     * @param {Bool} updateHeader
     */
    layoutDayCell: function(cell, hideOverflow, updateHeader) {
        // clean empty slices
        while (cell.lastChild.childNodes.length > 1 && cell.lastChild.lastChild.innerHTML == '') {
            Ext.fly(cell.lastChild.lastChild).remove();
        }
        
        Tine.log.debug('Tine.Calendar.YearView::layoutDayCell() - cell:');
        Tine.log.debug(cell);

        var items = cell.lastChild.childNodes.length;
        
        for (var j=0; j<items; j++) {
            var eventEl = Ext.get(cell.lastChild.childNodes[j]);
            
            //eventEl[height > this.dayCellsHeight && hideOverflow ? 'hide' : 'show']();
            eventEl.dom.style.width = this.dayCellsWidth / items ;
            cell.lastChild.childNodes[j].style.width = this.dayCellsWidth / items ;

        }
        
        
        //return height;
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
                this.removeEvent(parallelEvents[j]);
                this.insertEvent(parallelEvents[j]);
            }
            
            this.setActiveEvent(event);
        }
        
        this.layoutDayCells();
    },
    
    onClick: function(e, target) {
        
        // send click event anyway
        var event = this.getTargetEvent(e);
        if (event) {
            this.fireEvent('click', event, e);
            return;
        }
        
        /** distinct click from dblClick **/
        var now = new Date().getTime();
        
        if (now - parseInt(this.lastClickTime, 10) < 300) {
            this.lastClickTime = now;
            //e.stopEvent();
            return;
        }
        
        var dateTime = this.getTargetDateTime(e);
        if (Math.abs(dateTime - now) < 100) {
            this.lastClickTime = now;
            return this.onClick.defer(400, this, [e, target]);
        }
        this.lastClickTime = now;
        /** end distinct click from dblClick **/
        
        switch(target.className) {
            case 'cal-yearview-monthheader-date':
            case 'cal-yearview-monthheader-more':
                var moreText = target.parentNode.firstChild.innerHTML;
                if (! moreText) {
                    return;
                }
                
                //e.stopEvent();
                this.zoomDayCell(target.parentNode.parentNode);
                break;
        }
    },
    
    onContextMenu: function(e) {
	//TODO reenable
        //this.fireEvent('contextmenu', e);
    },
    
    onKeyDown : function(e){
        this.fireEvent("keydown", e);
    },
    
    onBeforeLoad: function(store, options) {
        if (! options.refresh) {
            this.store.each(this.removeEvent, this);
        }
    },
    
    /**
     * @private
     */
    onLoad : function(){
        if(! this.rendered){
            return;
        }
        
        this.removeAllEvents();
        
        // create parallels registry
        this.parallelEventsRegistry = new Tine.Calendar.ParallelEventsRegistry({
            dtStart: this.dateMesh[0], 
            dtEnd: this.dateMesh[this.dateMesh.length-1].add(Date.DAY, 1)/*.add(Date.SECOND, -1)*/,
            granularity: 60*24
        });
        
        // todo: sort generic?
        this.store.fields = Tine.Calendar.Model.Event.prototype.fields;
        this.store.sortInfo = {field: 'dtstart', direction: 'ASC'};
        this.store.applySort();
        
        // calculate duration and parallels
        this.store.each(function(event) {
            this.parallelEventsRegistry.register(event);
        }, this);
        
        this.store.each(this.insertEvent, this);
        this.layoutDayCells();
    },
    
    /**
     * @private
     */
    onMouseDown: function(e, target) {
        this.focusEl.focus();
        this.mainBody.focus();
        
        // only unzoom if click is not in the area of the daypreviewbox
        if (! e.getTarget('div.cal-yearview-daypreviewbox')) {
            this.unZoom();
        }
    },
    
    /**
     * @private
     */
    onRemove : function(ds, event, index, isUpdate){
        this.parallelEventsRegistry.unregister(event);
        this.removeEvent(event);
        this.getSelectionModel().unselect(event);
    },
    
    /**
     * @private
     */
    onUpdate : function(ds, event){
        // relayout original context
        var originalDtstart = event.modified.hasOwnProperty('dtstart') ? event.modified.dtstart : event.get('dtstart');
        var originalDtend = event.modified.hasOwnProperty('dtend') ? event.modified.dtend : event.get('dtend');
            
        var originalParallels = this.parallelEventsRegistry.getEvents(originalDtstart, originalDtend);
        for (var j=0; j<originalParallels.length; j++) {
            this.removeEvent(originalParallels[j]);
        }
        this.parallelEventsRegistry.unregister(event);
        
        var originalParallels = this.parallelEventsRegistry.getEvents(originalDtstart, originalDtend);
        for (var j=0; j<originalParallels.length; j++) {
            this.insertEvent(originalParallels[j]);
        }
        
        
        // relayout actual context
        var parallelEvents = this.parallelEventsRegistry.getEvents(event.get('dtstart'), event.get('dtend'));
        for (var j=0; j<parallelEvents.length; j++) {
            this.removeEvent(parallelEvents[j]);
        }
        this.parallelEventsRegistry.register(event);
        
        var parallelEvents = this.parallelEventsRegistry.getEvents(event.get('dtstart'), event.get('dtend'));
        for (var j=0; j<parallelEvents.length; j++) {
            this.insertEvent(parallelEvents[j]);
        }
        
        event.commit(true);
        this.setActiveEvent(this.getActiveEvent());
        this.layoutDayCells();
    },
    
    /**
     * print wrapper
     */
    print: function() {
        var renderer = new this.printRenderer();
        renderer.print(this);
    },
    
    /**
     * removes all events from dom
     */
    removeAllEvents: function() {
        var els = Ext.DomQuery.filter(Ext.DomQuery.select('div[class^=cal-yearview-event]', this.mainBody.dom), 'div[class=cal-yearview-eventslice]', true);
        for (var i=0; i<els.length; i++) {
            Ext.fly(els[i]).remove();
        }
        
        this.store.each(function(event) {
            if (event.ui) {
                event.ui.domIds = [];
            }
        });
        this.layoutDayCells();
    },
    
    /**
     * removes a event from the dom
     * @param {Tine.Calendar.Model.Event} event
     */
    removeEvent: function(event) {
        if (! event) {
            return;
        }
        
        if (event == this.activeEvent) {
            this.activeEvent = null;
        }
        
        if (event.ui) {
            event.ui.remove();
        }
    },
    
    /**
     * renders the view
     */
    onRender: function(container, position) {
        Tine.Calendar.YearView.superclass.onRender.apply(this, arguments);
        
        var m = [
             '<a href="#" class="cal-yearviewpanel-focus" tabIndex="-1"></a>',
             '<table class="cal-yearview-inner" cellspacing="0"><thead><tr class="cal-yearview-inner-header" height="23px">'
         ];
        for(var i = 0; i < 12; i++){
            m.push("<th class='cal-yearview-monthcell'><span>", this.monthNames[i], "</span></th>");
        }
        m[m.length] = "</tr></thead><tbody><tr>";
        for(var i = 0; i < 372; i++) {
            if(i % 12 == 0 && i != 0){
                m[m.length] = "</tr><tr>";
            }
            m[m.length] = 
                '<td class="cal-yearview-daycell">' +
//                    '<div class="cal-yearview-dayheader">' +
//                        '<div class="cal-yearview-dayheader-more"></div>' +
                        '<div class="cal-yearview-daycell-date"></div>' +
//                    '</div>' +
                    //'<div class="cal-yearview-daybody"><div class="cal-yearview-eventslice" /></div>' +
                    '<div class="cal-yearview-daybody"><div class="cal-yearview-eventslice" /></div>' +
                '</td>';
        }
        m.push('</tr></tbody></table>');
        
                
        this.el.update(m.join(""));
    },
       
    /**
     * sets currentlcy active event
     * 
     * NOTE: active != selected
     * @param {Tine.Calendar.Model.Event} event
     */
    setActiveEvent: function(event) {
        this.activeEvent = event || null;
    },
    
    updatePeriod: function(period) {
        this.toDay = new Date().clearTime();
        this.startDate = period.from;
        this.calcDateMesh();
        
        var tbar = this.findParentBy(function(c) {return c.getTopToolbar()}).getTopToolbar();
        if (tbar) {
            tbar.periodPicker.update(this.startDate);
            this.startDate = tbar.periodPicker.getPeriod().from;
        }
        
        if (! this.rendered) return;
        
        // update dates and bg colors
        var monthHeaders = Ext.DomQuery.select('div[class=cal-yearview-daycell-date]', this.mainBody.dom);
        for(var i = 0; i < this.dateMesh.length; i++) {
            if(this.dateMesh[i] != null && monthHeaders[i]) {
                //clsToAdd = ((this.dateMesh[i].getMonth() == this.startDate.getMonth()) ? ' cal-yearview-daycell-valid' : ' cal-yearview-daycell-invalid');
                clsToAdd = "";
                clsToAdd = clsToAdd + ((this.dateMesh[i].getDay() == 0 ) ? ' cal-yearview-daycell-sunday' : '' );
                clsToAdd = clsToAdd + ((this.dateMesh[i].getDay() == 6 ) ? ' cal-yearview-daycell-saturday' : '' );
                if (this.dateMesh[i].getTime() == this.toDay.getTime()) {
                    clsToAdd = clsToAdd + ' cal-yearview-daycell-today';
                }
                this.dayCells[i].setAttribute('class', 'cal-yearview-daycell ' + clsToAdd);
                monthHeaders[i].innerHTML = this.dateMesh[i].format('j');
            }
        }
       
        this.onLayout();
        this.fireEvent('changePeriod', period);
    },
    
    unZoom: function() {
        if (this.zoomCell) {
            // this prevents reopen of cell on header clicks
            this.lastClickTime = new Date().getTime();
            
            var cell = Ext.get(this.zoomCell);
            var dayBodyEl = cell.last();
            var height = cell.getHeight() - cell.first().getHeight();
            dayBodyEl.scrollTo('top');
            dayBodyEl.removeClass('cal-yearview-daypreviewbox');
            dayBodyEl.setStyle('background-color', cell.getStyle('background-color'));
            dayBodyEl.setStyle('border-top', 'none');
            dayBodyEl.setHeight(height);
            
            
            // NOTE: we need both setWidht statements, otherwise safari keeps scroller space
            for (var i=0; i<dayBodyEl.dom.childNodes.length; i++) {
                Ext.get(dayBodyEl.dom.childNodes[i]).setWidth(dayBodyEl.getWidth());
                Ext.get(dayBodyEl.dom.childNodes[i]).setWidth(dayBodyEl.first().getWidth());
            }
            
            this.layoutDayCell(this.zoomCell, true, true);
            
            this.zoomCell = false;
        }
        
    },
    
    zoomDayCell: function(cell) {
        this.zoomCell = cell;
        
        var dayBodyEl = Ext.get(cell.lastChild);
        var box = dayBodyEl.getBox();
        var bgColor = Ext.fly(cell).getStyle('background-color');
        bgColor == 'transparent' ? '#FFFFFF' : bgColor
        
        dayBodyEl.addClass('cal-yearview-daypreviewbox');
        dayBodyEl.setBox(box);
        dayBodyEl.setStyle('background-color', bgColor);
        dayBodyEl.setStyle('border-top', '1px solid ' + bgColor);
        
        var requiredHeight = this.layoutDayCell(cell, false, true) + 10;
        var availHeight = this.el.getBottom() - box.y;
        dayBodyEl.setHeight(Math.min(requiredHeight, availHeight));
    }
});
Ext.reg('Tine.Calendar.YearView', Tine.Calendar.YearView);
