/* 
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 * @note: lot to do here, i just started to move stuff from views here
 */
 
Ext.ns('Tine.Calendar');

Tine.Calendar.EventUI = function(event) {
    this.event = event;
    this.domIds = [];
    this.init();
};

Tine.Calendar.EventUI.prototype = {
    addClass: function(cls) {
        Ext.each(this.getEls(), function(el){
            el.addClass(cls);
        });
    },
    
    blur: function() {
        Ext.each(this.getEls(), function(el){
            el.blur();
        });
    },
    
    
    
    focus: function() {
        Ext.each(this.getEls(), function(el){
            el.focus();
        });
    },
    
    /**
     * returns events dom
     * @return {Array} of Ext.Element
     */
    getEls: function() {
        var domEls = [];
        for (var i=0; i<this.domIds.length; i++) {
            domEls[i] = Ext.get(this.domIds[i]);
        }
        return domEls;
    },
    
    init: function() {
        
    },
    
    onSelectedChange: function(state){
        if(state){
            this.focus();
            this.addClass('cal-event-active');
            this.setStyle({'z-index': 1000});
            
        }else{
            //this.blur();
            this.removeClass('cal-event-active');
            this.setStyle({'z-index': 100});
        }
    },
    
    /**
     * removes a event from the dom
     */
    remove: function() {
        var eventEls = this.getEls();
        for (var i=0; i<eventEls.length; i++) {
            if (eventEls[i] && typeof eventEls[i].remove == 'function') {
                eventEls[i].remove();
            }
        }
        this.domIds = [];
    },
    
    removeClass: function(cls) {
        Ext.each(this.getEls(), function(el){
            el.removeClass(cls);
        });
    },
    
    render: function() {
        
    },
    
    setStyle: function(style) {
        Ext.each(this.getEls(), function(el){
            el.setStyle(style);
        });
    }
    
};



Tine.Calendar.DaysViewEventUI = Ext.extend(Tine.Calendar.EventUI, {
    onSelectedChange: function(state){
        Tine.Calendar.DaysViewEventUI.superclass.onSelectedChange.call(this, state);
        if(state){
            this.addClass('cal-daysviewpanel-event-active');
            
        }else{
            this.removeClass('cal-daysviewpanel-event-active');
        }
    },
    
    render: function(view) {
        
        // @todo fetch color from calendar
        this.color = '#FD0000';
        
        var extraCls = '';
        
        // lighten up background
        var r = Math.min(view.hex2dec(this.color.substring(1,3)) + 150, 255);
        var g = Math.min(view.hex2dec(this.color.substring(3,5)) + 150, 255);
        var b = Math.min(view.hex2dec(this.color.substring(5,7)) + 150, 255);
        this.bgColor = 'rgb(' + r + ',' + g + ',' + b + ')';
        
        this.dtStart = this.event.get('dtstart');
        this.startColNum = view.getColumnNumber(this.dtStart);
        this.dtEnd = this.event.get('dtend');//.add(Date.SECOND, -1);
        this.endColNum = view.getColumnNumber(this.dtEnd);
        
        // skip dates not in our diplay range
        if (this.endColNum < 0 || this.startColNum > view.numOfDays-1) {
            return;
        }
        
        var registry = this.event.get('is_all_day_event') ? view.parallelWholeDayEventsRegistry : view.parallelScrollerEventsRegistry;
        this.parallelEvents = registry.getEvents(this.dtStart, this.dtEnd);
        
        // poperties might be missing on quickAdd
        var parallels = Math.max(1, this.parallelEvents.length);
        var pos = Math.max(0, this.parallelEvents.indexOf(this.event));
        
        if (this.event.get('is_all_day_event')) {
            this.renderAllDayEvent(view, parallels, pos);
        } else {
            this.renderScrollerEvent(view, parallels, pos);
        }
    },
    
    renderAllDayEvent: function(view, parallels, pos) {
        var offsetWidth = Ext.fly(view.wholeDayArea).getWidth();
        
        var width = Math.round(offsetWidth * (this.dtEnd.getTime() - this.dtStart.getTime()) / (view.numOfDays * Date.msDAY)) -5;
        var left = Math.round(offsetWidth * (this.dtStart.getTime() - view.startDate.getTime()) / (view.numOfDays * Date.msDAY));
        
        if (this.startColNum < 0) {
            width = width - Math.abs(this.startColNum) * (offsetWidth/view.numOfDays);
            left = 0;
            extraCls = extraCls + ' cal-daysviewpanel-this.event-cropleft';
        }
        
        if (this.endColNum > view.numOfDays) {
            width = width - Math.abs(this.endColNum - view.numOfDays) * (offsetWidth/view.numOfDays);
            extraCls = extraCls + ' cal-daysviewpanel-this.event-cropright';
        }
        
        var domId = Ext.id() + '-evnet:' + this.event.get('id');
        this.domIds.push(domId);
        
        var eventEl = view.templates.wholeDayEvent.insertFirst(view.wholeDayArea, {
            id: domId,
            summary: this.event.get('summary'),
            startTime: this.dtStart.format('H:i'),
            extraCls: extraCls,
            color: this.color,
            bgColor: this.bgColor,
            zIndex: 100,
            width: width  +'px',
            height: '15px',
            left: left + 'px',
            top: pos * 18 + 'px'//'1px'
        }, true);
        
        if (! (this.endColNum > view.numOfDays) && this.event.get('editGrant')) {
            this.resizeable = new Ext.Resizable(eventEl, {
                handles: 'e',
                disableTrackOver: true,
                //dynamic: !!this.event.isRangeAdd,
                widthIncrement: Math.round(offsetWidth / view.numOfDays),
                minWidth: Math.round(offsetWidth / view.numOfDays),
                listeners: {
                    scope: view,
                    resize: view.onEventResize,
                    beforeresize: view.onBeforeEventResize
                }
            });
        }
    },
    
    renderScrollerEvent: function(view, parallels, pos) {
        for (var currColNum=this.startColNum; currColNum<=this.endColNum; currColNum++) {
            
            extraCls = '';
            if (currColNum < 0 || currColNum > view.numOfDays) {
                continue;
            }
            
            var top = view.getTimeOffset(this.dtStart);
            var height = this.startColNum == this.endColNum ? view.getTimeHeight(this.dtStart, this.dtEnd) : view.getTimeOffset(this.dtEnd);
            
            if (currColNum != this.startColNum) {
                top = 0;
                extraCls = extraCls + ' cal-daysviewpanel-this.event-croptop';
            }
            
            if (this.endColNum != currColNum) {
                height = view.getTimeHeight(this.dtStart, this.dtStart.add(Date.DAY, 1));
                extraCls = extraCls + ' cal-daysviewpanel-this.event-cropbottom';
            }
            
            var domId = Ext.id() + '-evnet:' + this.event.get('id');
            this.domIds.push(domId);
            
            var eventEl = view.templates.event.append(view.getDateColumnEl(currColNum), {
                id: domId,
                summary: this.event.get('summary'),
                startTime: this.dtStart.format('H:i'),
                extraCls: extraCls,
                color: this.color,
                bgColor: this.bgColor,
                zIndex: 100,
                width: Math.round(90 * 1/parallels) + '%',
                height: height + 'px',
                left: Math.round(pos * 90 * 1/parallels) + '%',
                top: top + 'px'
            }, true);
            
            if (currColNum == this.endColNum && this.event.get('editGrant')) {
                this.resizeable = new Ext.Resizable(eventEl, {
                    handles: 's',
                    disableTrackOver: true,
                    dynamic: !!this.event.isRangeAdd,
                    heightIncrement: view.granularityUnitHeights/2,
                    listeners: {
                        scope: view,
                        resize: view.onEventResize,
                        beforeresize: view.onBeforeEventResize
                    }
                });
            }
        }
    }
});

Tine.Calendar.MonthViewEventUI = Ext.extend(Tine.Calendar.EventUI, {
    onSelectedChange: function(state){
        Tine.Calendar.MonthViewEventUI.superclass.onSelectedChange.call(this, state);
        if(state){
            this.addClass('cal-monthview-active');
            this.setStyle({
                'background-color': this.color,
                'color':            '#FFFFFF'
            });
            
        }else{
            this.removeClass('cal-monthview-active');
            this.setStyle({
                'background-color': this.is_all_day_event ? this.bgColor : '',
                'color':            this.is_all_day_event ? '#000000' : this.color
            });
        }
    }
});