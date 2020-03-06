/* 
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009-2015 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine.Calendar');

require('../css/daysviewpanel.css');
require('./Printer/DaysView');

/**
 * @namespace   Tine.Calendar
 * @class       Tine.Calendar.DaysView
 * @extends     Tine.Calendar.AbstractView
 * Calendar view representing each day in a column
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @constructor
 * @param {Object} config
 */
Tine.Calendar.DaysView = function(config){
    Ext.apply(this, config);
    Tine.Calendar.DaysView.superclass.constructor.call(this);
    
    this.printRenderer = Tine.Calendar.Printer.DaysViewRenderer;

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
        'updateEvent',
        /**
         * @event onBeforeAllDayScrollerResize
         * fired when an the allDayArea gets resized
         *
         * @param {Tine.Calendar.Model.DaysView} this
         * @param {number} heigt
         */
        'onBeforeAllDayScrollerResize'
    );
};

Ext.extend(Tine.Calendar.DaysView, Tine.Calendar.AbstractView, {
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
     * @cfg {String} (H:i) defaultStart
     * generic scroll start of the (work) day
     */
    defaultStart: '08:00',
    /**
     * @cfg {String} (H:i) dayStart
     * generic start of the (work) day
     */
    dayStart: '08:00',
    /**
     * @cfg {String} (H:i) dayEnd
     * generic end of the (work) day
     */
    dayEnd: '18:00',
    /**
     * @cfg {Bool} cropDayTime
     * crop times before and after dayStart and dayEnd
     */
    cropDayTime: false,
    /**
     *  @cfg {Integer} wheelIncrement
     *  the number of pixels to increment on mouse wheel scrolling (defaults to 50)
     */
    wheelIncrement: 50,
    /**
     * @cfg {String} newEventSummary
     * i18n._('New Event')
     */
    newEventSummary: 'New Event',
    /**
     * @cfg {String} dayFormatString
     * i18n._('{0}, the {1}. of {2}')
     */
    dayFormatString: '{0}, the {1}. of {2}',
    /**
     * @cfg {Number} timeGranularity
     * granularity of timegrid in minutes
     */
    timeGranularity: 30,
    /**
     * @cfg {Number} timeIncrement
     * time increment for range adds/edits (minutes)
     */
    timeIncrement: 15,
    /**
     * @cfg {Number} timeVisible
     * time visible in scrolling area (minutes)
     */
    timeVisible: '10:00',
    /**
     * @cfg {Boolean} denyDragOnMissingEditGrant
     * deny drag action if edit grant for event is missing
     */
    denyDragOnMissingEditGrant: true,
    /**
     * @cfg {Boolean} readOnly
     * no dd acionts if read only
     */
    readOnly: false,


    /**
     * store holding timescale
     * @property {Ext.data.Store}
     * @private
     */
    timeScale: null,
    /**
     * The amount of space to reserve for the scrollbar (defaults to 19 pixels)
     * @property {Number}
     * @private
     */
    scrollOffset: 19,
    /**
     * The time in milliseconds, a scroll should be delayed after using the mousewheel
     * @property Number
     * @private
     */
    scrollBuffer: 200,
    /**
     * The minmum all day height in px
     * @property Number
     * @private
     */
    minAllDayScrollerHight: 10,
    /**
     * record currently being edited or false
     * @property {Record} editing
     * @private
     */
    editing: false,
    /**
     * @property {Ext.data.Store}
     * @private
     */
    ds: null,

    eventCls: 'cal-daysviewpanel-event',

    /**
     * updates period to display
     * @param {Array} period
     */
    updatePeriod: function(period) {
        this.startDate = period.from;
        
        var tbar = this.findParentBy(function(c) {return c.getTopToolbar()}).getTopToolbar();
        if (tbar && tbar.periodPicker) {
            tbar.periodPicker.update(this.startDate);
            this.startDate = tbar.periodPicker.getPeriod().from;
        }
        
        this.endDate = this.startDate.add(Date.DAY, this.numOfDays+1);
        
        //this.parallelScrollerEventsRegistry = new Tine.Calendar.ParallelEventsRegistry({dtStart: this.startDate, dtEnd: this.endDate});
        //this.parallelWholeDayEventsRegistry = new Tine.Calendar.ParallelEventsRegistry({dtStart: this.startDate, dtEnd: this.endDate});
        //this.store.each(this.removeEvent, this);
        
        this.updateDayHeaders();
        this.onBeforeScroll();
        
        this.fireEvent('changePeriod', period);
    },
    
    /**
     * init this view
     */
    initComponent: function() {
        this.app = Tine.Tinebase.appMgr.get('Calendar');
        
        this.newEventSummary      =  this.app.i18n._hidden(this.newEventSummary);
        this.dayFormatString      =  this.app.i18n._hidden(this.dayFormatString);
        
        this.startDate.setHours(0);
        this.startDate.setMinutes(0);
        this.startDate.setSeconds(0);
        
        this.endDate = this.startDate.add(Date.DAY, this.numOfDays+1);
        
        this.boxMinWidth = 60*this.numOfDays;
        
        this.parallelScrollerEventsRegistry = new Tine.Calendar.ParallelEventsRegistry({dtStart: this.startDate, dtEnd: this.endDate});
        this.parallelWholeDayEventsRegistry = new Tine.Calendar.ParallelEventsRegistry({dtStart: this.startDate, dtEnd: this.endDate});

        this.timeIncrement = parseInt(this.app.getRegistry().get('preferences').get('timeIncrement')) || this.timeIncrement;

        this.initTimeScale();

        if (Tine.Tinebase.MainScreen) {
            this.mon(Tine.Tinebase.MainScreen, 'appactivate', this.onAppActivate, this);
        }

        // apply preferences
        var prefs = this.app.getRegistry().get('preferences'),
            defaultStartTime = Date.parseDate(prefs.get('daysviewdefaultstarttime'), 'H:i'),
            startTime = Date.parseDate(prefs.get('daysviewstarttime'), 'H:i'),
            endTime = Date.parseDate(prefs.get('daysviewendtime'), 'H:i'),
            timeVisible = Date.parseTimePart(prefs.get('daysviewtimevisible'), 'H:i');

        this.dayStart = Ext.isDate(startTime) ? startTime : Date.parseDate(this.dayStart, 'H:i');
        this.dayEnd = Ext.isDate(endTime) ? endTime : Date.parseDate(this.dayEnd, 'H:i');
        // 00:00 in users timezone is a spechial case where the user expects
        // something like 24:00 and not 00:00
        if (this.dayEnd.format('H:i') == '00:00') {
            this.dayEnd = this.dayEnd.add(Date.MINUTE, -1);
        }

        this.timeVisible = Ext.isDate(timeVisible) ? timeVisible : Date.parseTimePart(this.timeVisible, 'H:i');

        this.cropDayTime = !! Tine.Tinebase.configManager.get('daysviewcroptime', 'Calendar');

        if (this.cropDayTime) {
            this.defaultStart = Ext.isDate(defaultStartTime) ? defaultStartTime : Date.parseDate(this.defaultStart, 'H:i');
        } else {
            this.defaultStart = this.dayStart;
        }

        this.wheelIncrement = Tine.Tinebase.configManager.get('daysviewwheelincrement', 'Calendar') || this.wheelIncrement;

        Tine.Calendar.DaysView.superclass.initComponent.apply(this, arguments);
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
    
    initDropZone: function() {
        this.dd = new Ext.dd.DropZone(this.mainWrap.dom, {
            ddGroup: 'cal-event',

            view: this,

            notifyOver : function(dd, e, data) {
                var sourceEl = Ext.fly(data.sourceEl),
                    sourceView = data.scope,
                    colorIcon = _.get(data,'event.colorSet.text') === '#FFFFFF' ? '-WHITE' : '';

                sourceEl.setStyle({'border-left-style': 'dashed'});
                sourceEl.setOpacity(0.5);

                data.denyDrop = true;
                
                if (data.event) {
                    var event = data.event;

                    var targetDateTime = Tine.Calendar.DaysView.prototype.getTargetDateTime.call(data.scope, e);
                    if (targetDateTime) {
                        var dtString = targetDateTime.format(targetDateTime.is_all_day_event ? Ext.form.DateField.prototype.format : 'H:i');
                        if (! event.data.is_all_day_event) {
                            Ext.fly(dd.proxy.el.query('div[class=cal-daysviewpanel-event-header-inner]')[0]).update(dtString);
                        }

                        if (event.get('editGrant')) {
                            data.denyDrop = this.view == sourceView && Math.abs(targetDateTime.getTime() - event.get('dtstart').getTime()) < Date.msMINUTE;

                            if (data.dtstartLimit && targetDateTime.getTimePart() > data.dtstartLimit) {
                                data.denyDrop = true;
                            }

                            var eventDrop =  data.denyDrop ? 'cal-daysviewpanel-event-drop-nodrop' : 'cal-daysviewpanel-event-drop-ok';
                            return eventDrop + colorIcon;
                        }
                    }
                }
                
                return 'cal-daysviewpanel-event-drop-nodrop' + colorIcon;
            },
            
            notifyOut : function() {
                //delete this.grid;
            },
            
            notifyDrop : function(dd, e, data) {
                var v = data.scope,
                    targetDate = v.getTargetDateTime(e);

                if (targetDate) {
                    var event = data.event,
                        originalDuration = (event.get('dtend').getTime() - event.get('dtstart').getTime()) / Date.msMINUTE;

                    if (data.denyDrop) {
                        return false;
                    }

                    event.beginEdit();
                    event.set('dtstart', targetDate);

                    if (! event.get('is_all_day_event') && targetDate.is_all_day_event && event.duration < Date.msDAY) {
                        // draged from scroller -> dropped to allDay and duration less than a day
                        event.set('dtend', targetDate.add(Date.DAY, 1).add(Date.SECOND, -1));
                    } else if (event.get('is_all_day_event') && !targetDate.is_all_day_event) {
                        // draged from allDay -> droped to scroller will be resetted to hone hour
                        event.set('dtend', targetDate.add(Date.MINUTE, Tine.Calendar.Model.Event.getMeta('defaultEventDuration')));
                    } else {
                        event.set('dtend', targetDate.add(Date.MINUTE, originalDuration));
                    }

                    event.set('is_all_day_event', targetDate.is_all_day_event);


                    // change attendee in split view
                    if (this.view.ownerCt.attendee) {
                        var attendeeStore = Tine.Calendar.Model.Attender.getAttendeeStore(event.get('attendee')),
                            sourceAttendee = Tine.Calendar.Model.Attender.getAttendeeStore.getAttenderRecord(attendeeStore, event.view.ownerCt.attendee),
                            destinationAttendee = Tine.Calendar.Model.Attender.getAttendeeStore.getAttenderRecord(attendeeStore, this.view.ownerCt.attendee);

                        if (! destinationAttendee) {
                            destinationAttendee = new Tine.Calendar.Model.Attender(this.view.ownerCt.attendee.data);

                            attendeeStore.remove(sourceAttendee);
                            attendeeStore.add(destinationAttendee);

                            Tine.Calendar.Model.Attender.getAttendeeStore.getData(attendeeStore, event);
                        }
                    }

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
    initDragZone: function() {
        this.scroller.ddScrollConfig = {
            hthresh: -1,
            frequency: 500
        };
        Ext.dd.ScrollManager.register(this.scroller);
        
        // init dragables
        this.dragZone = new Ext.dd.DragZone(this.el, {
            ddGroup: 'cal-event',
            view: this,
            scroll: false,
            containerScroll: true,
            
            getDragData: function(e) {
                // adjust scrollConfig
                var scrollUnit = this.view.getTimeOffset(this.view.timeIncrement);
                this.view.scroller.ddScrollConfig.vthresh = scrollUnit *2;
                this.view.scroller.ddScrollConfig.increment =  scrollUnit * 4;

                var selected = this.view.getSelectionModel().getSelectedEvents();
                
                var eventEl = e.getTarget('div.cal-daysviewpanel-event', 10);
                if (eventEl) {
                    var parts = eventEl.id.split(':');
                    var event = this.view.store.getById(parts[1]);
                    
                    // don't allow dragging of dirty events
                    // don't allow dragging with missing edit grant
                    if (! event || event.dirty || this.readOnly || (this.view.denyDragOnMissingEditGrant && ! event.get('editGrant'))) {
                        return;
                    }
                    
                    // we need to clone an event with summary in
                    var eventEl = Ext.get(event.ui.domIds[0]),
                        eventBox = eventEl.getBox(),
                        d = eventEl.dom.cloneNode(true);

                    d.id = Ext.id();

                    if (event.get('is_all_day_event')) {
                        Ext.fly(d).setTop(-2);
                        Ext.fly(d).setLeft(0);
                        Ext.fly(d).setWidth(eventBox.width);
                    } else {
                        var width = (Ext.fly(this.view.dayCols[0]).getWidth() * 0.9);
                        Ext.fly(d).setTop(0);
                        Ext.fly(d).setWidth(width);
                        Ext.fly(d).setHeight(eventBox.height);
                    }

                    return {
                        scope: this.view,
                        sourceEl: eventEl,
                        event: event,
                        ddel: d,
                        selections: this.view.getSelectionModel().getSelectedEvents(),
                        dtstartLimit: this.view.cropDayTime ? this.view.dayEnd.getTimePart() - event.duration : false
                    }
                }
            },
            
            getRepairXY: function(e, dd) {
                Ext.fly(this.dragData.sourceEl).setStyle({'border-left-style': 'solid'});
                Ext.fly(this.dragData.sourceEl).setOpacity(1, 1);
                
                return Ext.fly(this.dragData.sourceEl).getXY();
            }
        });
    },
    
    /**
     * renders the view
     */
    onRender: function(container, position) {
        Tine.Calendar.DaysView.superclass.onRender.apply(this, arguments);

        this.templates.master.append(this.el.dom, {
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
        Tine.Calendar.DaysView.superclass.afterRender.apply(this, arguments);

        this.mon(this.el, 'mousedown', this.onMouseDown, this);
        this.mon(this.el, 'mouseup', this.onMouseUp, this);

        this.initDropZone();
        this.initDragZone();
        
        this.updatePeriod({from: this.startDate});

        // apply os specific scrolling space
        Ext.fly(this.innerHd.firstChild.firstChild).setStyle('margin-right', Ext.getScrollBarWidth() + 'px');
        
        // crop daytime
        if (this.cropDayTime) {
            this.cropper.setStyle('overflow', 'hidden');
            this.scroller.addClass('cal-daysviewpanel-body-cropDayTime');
        }

        if (this.store.getCount()) {
            this.onLoad.defer(100, this);
        }

        this.rendered = true;
    },
    
    scrollTo: function(time) {
        time = Ext.isDate(time) ? time : new Date();
        
        var scrollTop = this.getTimeOffset(time);
        if (this.cropDayTime) {
            scrollTop = scrollTop - this.getTimeOffset(this.dayStart);
        }

        this.scroller.dom.scrollTop = scrollTop;
    },

    onMouseWheel: function(e) {
        var d = e.getWheelDelta()*this.wheelIncrement*-1;
        e.stopEvent();

        var scrollTop = this.scroller.dom.scrollTop,
            newTop = scrollTop + d,
            sh = this.scroller.dom.scrollHeight-this.scroller.dom.clientHeight;

        var s = Math.max(0, Math.min(sh, newTop));
        if(s != scrollTop){
            this.scroller.scrollTo('top', s);
        }
    },

    onBeforeScroll: function() {
        if (! this.isScrolling) {
            this.isScrolling = true;

            // walk all cols an hide hints
            Ext.each(this.dayCols, function(dayCol, idx) {
                this.aboveHints.item(idx).setDisplayed(false);
                this.belowHints.item(idx).setDisplayed(false);
            }, this);
        }
    },
    
    /**
     * add hint if events are outside visible area
     * 
     * @param {} e
     * @param {} t
     * @param {} o
     */
    onScroll: function(e, t, o) {
        // no arguments means programatic scroll (show/hide/...)
        if (! arguments.length) {
            var topTime = this.lastScrollTime || this.defaultStart;

            if (topTime) {
                this.scrollTo(topTime);
            }
        }

        var visibleHeight = this.scroller.dom.clientHeight,
            visibleStart  = this.scroller.dom.scrollTop - this.mainBody.dom.offsetTop,
            visibleEnd    = visibleStart + visibleHeight,
            vStartMinutes = this.getHeightMinutes(visibleStart),
            vEndMinutes   = this.getHeightMinutes(visibleEnd);

        Ext.each(this.dayCols, function(dayCol, idx) {
            var dayColEl    = Ext.get(dayCol),
                dayStart    = this.startDate.add(Date.DAY, idx),
                aboveEvents = this.parallelScrollerEventsRegistry.getEvents(dayStart, dayStart.add(Date.MINUTE, vStartMinutes)),
                belowEvents = this.parallelScrollerEventsRegistry.getEvents(dayStart.add(Date.MINUTE, vEndMinutes), dayStart.add(Date.DAY, 1));

            if (aboveEvents.length) {
                var aboveHint = this.aboveHints.item(idx);
                aboveHint.setTop(visibleStart + 5);
                if (!aboveHint.isVisible()) {
                    aboveHint.fadeIn({duration: 1.6});
                }
            }

            if (belowEvents.length) {
                var belowHint = this.belowHints.item(idx);
                belowHint.setTop(visibleEnd - 14);
                if (!belowHint.isVisible()) {
                    belowHint.fadeIn({duration: 1.6});
                }
            }
        }, this);

        var topOffset = this.scroller ? this.getHeightMinutes(this.scroller.dom.scrollTop) : null;
        if (topOffset !== null) {
            if (this.cropDayTime) {
                topOffset = topOffset + this.dayStart.getHours() * 60 + this.dayStart.getMinutes();
            }
            this.lastScrollTime = this.dayStart.clearTime(true).add(Date.MINUTE, topOffset);
        }
        this.isScrolling = false;
    },

    /**
     * renders a single event into this daysview
     * @param {Tine.Calendar.Model.Event} event
     * 
     * @todo Add support vor Events spanning over a day boundary
     */
    insertEvent: function(event) {
        if (! this.mainBody) {
            // maybe another app is active and mainBody has not been rendered yet
            return;
        }

        event.ui = new Tine.Calendar.DaysViewEventUI(event);
        event.ui.render(this);
    },
    
    /**
     * creates a new event directly from this view
     * @param {} event
     */
    createEvent: function(e, event) {
        // only add range events if mouse is down long enough
        if (this.editing || (event.isRangeAdd && ! this.mouseDown) || !event.isValid()) {
            return;
        }
        
        // insert event silently into store
        this.editing = event;
        this.store.suspendEvents();
        this.store.add(event);
        this.store.resumeEvents();
        
        // draw event
        var registry = event.get('is_all_day_event') ? this.parallelWholeDayEventsRegistry : this.parallelScrollerEventsRegistry;
        registry.register(event);
        this.insertEvent(event);
        this.onLayout();
        
        //var eventEls = event.ui.getEls();
        //eventEls[0].setStyle({'border-left-style': 'dashed'});
        //eventEls[0].setOpacity(0.5);
        
        // start sizing for range adds
        if (event.isRangeAdd) {
            // don't create events with very small duration
            event.ui.resizeable.on('resize', function() {
                if (event.get('is_all_day_event')) {
                    var keep = true;
                } else {
                    var keep = (event.get('dtend').getTime() - event.get('dtstart').getTime()) / Date.msMINUTE >= this.timeIncrement;
                }
                
                if (keep) {
                    this.startEditSummary(event);
                } else {
                    this.abortCreateEvent(event);
                }
            }, this);

            // snap correction for rangeadds
            var box = event.ui.resizeable.el.getBox(),
                mouseXY = Ext.EventObject.getXY(),
                startX = mouseXY[0],
                startY = mouseXY[1],
                handleX = box.x + box.width,
                handleY = box.y + box.height;

            event.ui.resizeable.correctionX = handleX - startX;
            event.ui.resizeable.correctionY = handleY - startY;

            event.ui.resizeable.snap = function(value, inc, min) {
                var pos = this.activeHandle.position;

                if (pos === 'south' && inc === this.heightIncrement) {
                    value = value - this.correctionY;
                } else if (pos === 'east' && inc === this.widthIncrement) {
                    value = value - this.correctionX;
                }

                return Ext.Resizable.prototype.snap.call(this, value, inc, min);
            };

            var rzPos = event.get('is_all_day_event') ? 'east' : 'south';
            
            event.ui.resizeable[rzPos].onMouseDown.call(event.ui.resizeable[rzPos], e);

            // adjust initial size to avoid flickering when start resizing
            event.ui.resizeable.onMouseMove(Ext.EventObject);
        } else {
            this.startEditSummary(event);
        }
    },
    
    abortCreateEvent: function(event) {
        this.store.remove(event);
        this.editing = false;
    },
    
    startEditSummary: function(event) {
        if (event.summaryEditor) {
            return false;
        }
        
        var eventEls = event.ui.getEls();
        
        var bodyCls = event.get('is_all_day_event') ? 'cal-daysviewpanel-wholedayevent-body' : 'cal-daysviewpanel-event-body';
        event.summaryEditor = new Ext.form.TextArea({
            event: event,
            renderTo: eventEls[0].down('div[class=' + bodyCls + ']'),
            width: event.ui.getEls()[0].getWidth() -12,
            height: Math.max(12, event.ui.getEls()[0].getHeight() -18),
            style: 'background-color: transparent; background: 0: border: 0; position: absolute; top: 0px; font-weight: bold; color: ' + event.ui.colorSet.text + ';' ,
            value: this.newEventSummary,
            maxLength: 255,
            maxLengthText: this.app.i18n._('The summary must not be longer than 255 characters.'),
            minLength: 1,
            minLengthText: this.app.i18n._('The summary must have at least 1 character.'),
            enableKeyEvents: true,
            listeners: {
                scope: this,
                render: function(field) {
                    field.focus(true, 100);
                },
                blur: this.endEditSummary,
                specialkey: this.endEditSummary,
                keydown: this.endEditSummary
            }
            
        });
    },
    
    endEditSummary: function(f, e) {
        if (! this.editing || this.validateMsg) {
            return;
        }

        var field   = this.editing.summaryEditor;
        var event   = field.event;
        var summary = field.getValue();



        // abort edit on ESC key
        if (e && (e.getKey() == e.ESC)) {
            this.abortCreateEvent(event);
            return;
        }

        // only commit edit on Enter & blur
        if (e && e.getKey() != e.ENTER) {
            return;
        }
        
        // Validate Summary maxLength
        if (summary.length > field.maxLength) {
            field.markInvalid();
            this.validateMsg = Ext.Msg.alert(this.app.i18n._('Summary too Long'), field.maxLengthText, function(){
                field.focus();
                this.validateMsg = false;
                }, this);
            return;
        }

        // Validate Summary minLength
        if (!summary || summary.match(/^\s{1,}$/) || summary.length < field.minLength) {
            field.markInvalid();
            this.validateMsg = Ext.Msg.alert(this.app.i18n._('Summary too Short'), field.minLengthText, function(){
                field.focus();
                this.validateMsg = false;
                }, this);
            return;
        }

        this.editing = false;
        event.summaryEditor = false;

        event.set('summary', summary);
        
        this.store.suspendEvents();
        this.store.remove(event);
        this.store.resumeEvents();
        
        var registry = event.get('is_all_day_event') ? this.parallelWholeDayEventsRegistry : this.parallelScrollerEventsRegistry;
        registry.unregister(event);
        this.removeEvent(event);
        
        event.dirty = true;
        this.store.add(event);
        this.fireEvent('addEvent', event);
    },
    
    onAppActivate: function(app) {
        if (app === this.app) {
            this.redrawWholeDayEvents();
        }
    },
    
    onResize: function() {
        Tine.Calendar.DaysView.superclass.onResize.apply(this, arguments);
        
        this.updateDayHeaders();
        this.redrawWholeDayEvents.defer(50, this);

        this.unbufferedOnLayout();
    },
    
    redrawWholeDayEvents: function() {
        this.store.each(function(event) {
            // check if event is currently visible by looking into ui.domIds
            if (event.ui && event.ui.domIds.length > 0 && event.get('is_all_day_event')) {
                this.removeEvent(event);
                this.insertEvent(event);
            }
        }, this);
    },
    
    onClick: function(e) {
        // check for hint clicks first
        var hint = e.getTarget('img[class^=cal-daysviewpanel-body-daycolumn-hint-]', 10, true);
        if (hint) {
            this.scroller.scroll(hint.hasClass('cal-daysviewpanel-body-daycolumn-hint-above') ? 't' : 'b', 10000, true);
            return;
        }

        return Tine.Calendar.DaysView.superclass.onClick.call(this, e);
    },
    
    /**
     * @private
     */
    onDblClick: function(e, target) {
        e.stopEvent();
        var event = this.getTargetEvent(e);
        var dtStart = this.getTargetDateTime(e);

        if (event) {
            if (event.dirty && this.editing ) {
                event.set('summary', this.editing.summaryEditor.getValue());
                event.summaryEditor = false;
                this.editing = false;
                this.abortCreateEvent.defer(500, this, [event]);
            }

            this.fireEvent('dblclick', event, e);
        } else if (dtStart && !this.editing) {
            var newId = 'cal-daysviewpanel-new-' + Ext.id();
            var dtend = dtStart.add(Date.MINUTE, Tine.Calendar.Model.Event.getMeta('defaultEventDuration'));
            if (dtStart.is_all_day_event) {
                dtend = dtend.add(Date.HOUR, 23).add(Date.SECOND, -1);
            }
            
            // do not create an event exceeding the crop day time limit
            else if (this.cropDayTime) {
                var format = 'Hms';
                if (dtStart.format(format) >= this.dayEnd.format(format)) {
                    return false;
                }
                
                if (dtend.format(format) >= this.dayEnd.format(format)) {
                    dtend.setHours(this.dayEnd.getHours());
                    dtend.setMinutes(this.dayEnd.getMinutes());
                    dtend.setSeconds(this.dayEnd.getSeconds());
                }
            }
            
            var event = new Tine.Calendar.Model.Event(Ext.apply(Tine.Calendar.Model.Event.getDefaultData(), {
                id: newId,
                dtstart: dtStart, 
                dtend: dtend,
                is_all_day_event: dtStart.is_all_day_event
            }), newId);

            this.createEvent(e, event);
            event.dirty = true;
        } else if (target.className == 'cal-daysviewpanel-dayheader-day'){
            var dayHeaders = Ext.DomQuery.select('div[class=cal-daysviewpanel-dayheader-day]', this.innerHd);
            var date = this.startDate.add(Date.DAY, dayHeaders.indexOf(target));
            this.fireEvent('changeView', 'day', date);
        }
    },
    
    /**
     * @private
     */
    onMouseDown: function(e) {
        // don't care for right btn
        if (e.button > 0) {
            return;
        }
        
        if (! this.editing) {
            this.focusEl.focus();
        }
        this.mouseDown = true;
        
        var targetEvent = this.getTargetEvent(e);
        if (this.editing && this.editing.summaryEditor && (targetEvent != this.editing)) {
            this.editing.summaryEditor.fireEvent('blur', this.editing.summaryEditor, null);
            return;
        }

        var sm = this.getSelectionModel();
        sm.select(targetEvent);
        
        var dtStart = this.getTargetDateTime(e),
            dtEnd = this.getTargetDateTime(e, this.timeIncrement, 's');

        if (dtStart && !this.readOnly) {
            var newId = 'cal-daysviewpanel-new-' + Ext.id();
            var event = new Tine.Calendar.Model.Event(Ext.apply(Tine.Calendar.Model.Event.getDefaultData(), {
                id: newId,
                dtstart: dtStart,
                dtend: dtStart.is_all_day_event ? dtStart.add(Date.HOUR, 24).add(Date.SECOND, -1) : dtEnd,
                is_all_day_event: dtStart.is_all_day_event
            }), newId);
            event.isRangeAdd = true;
            event.dirty = true;
            
            e.stopEvent();
            e.preventDefault();

            this.createEvent.defer(100, this, [e, event]);
        }
    },
    
    /**
     * @private
     */
    onMouseUp: function() {
        this.mouseDown = false;
    },
    
    /**
     * @private
     */
    onBeforeEventResize: function(rz, e) {
        var me = this;
        var parts = rz.el.id.split(':');
        var event = this.store.getById(parts[1]);

        this.getSelectionModel().select(event);

        // @TODO compute max minutes also
        var maxHeight = 10000;

        if (this.cropDayTime) {
            var maxMinutes = (this.dayEnd.getTimePart() - event.get('dtstart').getTimePart()) / Date.msMINUTE;
            maxHeight = this.getTimeOffset(maxMinutes);
        }

        rz.heightIncrement = this.getTimeOffset(this.timeIncrement);
        rz.maxHeight = maxHeight;

        rz.event = event;
        rz.originalHeight = rz.el.getHeight();
        rz.originalWidth  = rz.el.getWidth();

        // NOTE: ext dosn't support move events via api
        rz.onMouseMove = rz.onMouseMove.createSequence(function(e) {
            var event = this.event;
            if (! event) {
                //event already gone -> late event / busy brower?
                return;
            }
            var ui = event.ui;
            var rzInfo = ui.getRzInfo(this);

            if (e.type === 'mousemove') {
                if (! event.get('is_all_day_event')) {
                    // getRzInfo calcs wrong values???
                    let shouldHeight = me.getTimeOffset(rzInfo.dtend) - me.getTimeOffset(event.get('dtstart'));
                    this.el.setHeight(shouldHeight);
                }

                if (this.durationEl) {
                    this.durationEl.update(rzInfo.dtend.format(event.get('is_all_day_event') ? Ext.form.DateField.prototype.format : 'H:i'));
                }
            }
        }, rz);

        // adjust initial size to avoid flickering when start resizing
        if (e.getTarget('.x-resizable-handle-south')) {
            event.ui.resizeable.onMouseMove(e);
        }

        event.ui.markDirty();
        
        // NOTE: Ext keeps proxy if element is not destroyed (diff !=0)
        if (! rz.durationEl) {
            rz.durationEl = rz.el.insertFirst({
                'class': 'cal-daysviewpanel-event-rzduration',
                'style': 'position: absolute; bottom: 3px; right: 2px; z-index: 1000;'
            });
        }
        rz.durationEl.update(event.get('dtend').format(event.get('is_all_day_event') ? Ext.form.DateField.prototype.format : 'H:i'));
    },
    
    /**
     * @private
     */
    onEventResize: function(rz, width, height) {
        var event = rz.event;
        
        if (! event) {
            //event already gone -> late event / busy brower?
            return;
        }
        
        var rzInfo = event.ui.getRzInfo(rz, width, height);
        
        if (rzInfo.diff != 0) {
            if (rzInfo.duration > 0) {
                event.set('dtend', rzInfo.dtend);
            } else {
                // force event length to at least 1 minute
                var date = new Date(event.get('dtstart').getTime());
                date.setMinutes(date.getMinutes() + 1);
                event.set('dtend', date);
            }
        }
        
        if (event.summaryEditor) {
            event.summaryEditor.setHeight(event.ui.getEls()[0].getHeight() -18);
        }
        
        // don't fire update events on rangeAdd
        if (rzInfo.diff != 0 && event != this.editing && ! event.isRangeAdd) {
            this.fireEvent('updateEvent', event);
        } else if (event.isRangeAdd) {
            event.ui.clearDirty();
        } else {
            // NOTE: we need to redraw event as resizer is broken after one attempt
            this.removeEvent(event);
            this.insertEvent(event);
            this.getSelectionModel().select(event);
        }
    },

    getParallelEventRegistry: function(event, original) {
        var isAllDayEvent = original && event.modified.hasOwnProperty('is_all_day_event') ?
            event.modified.is_all_day_event :
            event.get('is_all_day_event');

        return isAllDayEvent ?
            this.parallelWholeDayEventsRegistry :
            this.parallelScrollerEventsRegistry;
    },

    initParallelEventRegistry: function(event) {
        this.parallelScrollerEventsRegistry = new Tine.Calendar.ParallelEventsRegistry({dtStart: this.startDate, dtEnd: this.endDate});
        this.parallelWholeDayEventsRegistry = new Tine.Calendar.ParallelEventsRegistry({dtStart: this.startDate, dtEnd: this.endDate});
    },

    /**
     * print wrapper
     */
    print: function(printMode) {
        var renderer = new this.printRenderer({printMode: printMode});
        renderer.print(this);
    },

    getPeriod: function() {
        return {
            from: this.startDate,
            until: this.startDate.add(Date.DAY, this.numOfDays)
        };
    },
    
    /**
     * get date of a (event) target
     * 
     * @param {Ext.EventObject} e
     * @param {number} graticule
     * @param {char} graticuleEdge one of n (north) s, (south)
     * @return {Date}
     */
    getTargetDateTime: function(e, graticule, graticuleHandle) {
        var target = e.getTarget('div[class^=cal-daysviewpanel-datetime]');
        
        if (target && target.id.match(/^ext-gen\d+:\d+/)) {
            var parts = target.id.split(':');
            
            var date = this.startDate.add(Date.DAY, parseInt(parts[1], 10));
            date.is_all_day_event = true;
            
            if (parts[2] ) {
                var timePart = this.timeScale.getAt(parts[2]),
                    eventXY = e.getXY(),
                    mainBodyXY = this.mainBody.getXY(),
                    offsetPx = eventXY[1] - mainBodyXY[1],
                    offsetMinutes = this.getHeightMinutes(offsetPx),
                    graticule = graticule ? graticule : this.timeGranularity,
                    graticuleHandle = graticuleHandle ? graticuleHandle : 'n',
                    graticuleFn = graticuleHandle == 'n' ? Math.floor : Math.ceil;

                // constraint to graticule
                offsetMinutes = graticuleFn(offsetMinutes/graticule) * graticule;

                date = date.add(Date.MINUTE, offsetMinutes);
                date.is_all_day_event = false;
            }
            
            return date;
        }
    },

    /**
     * get offset in px for the time part of given date (with current scaling)
     *
     * @param {date} date
     * @returns {number}
     */
    getTimeOffset: function(date) {
        if (this.mainBody) {
            var minutes = Ext.isDate(date) ? date.getTimePart() / Date.msMINUTE : date,
                d = this.getMainBodyHeight() / (24 * 60);

            return Math.round(d * minutes);
        }
    },

    /**
     * get offset in % for the time part of given date
     *
     * @param {date|number} date
     * @returns {number}
     */
    getTimeOffsetPct: function(date) {
        var minutes = Ext.isDate(date) ? date.getTimePart() / Date.msMINUTE : date;

        return 100 * ((Date.msMINUTE * minutes) / Date.msDAY);
    },

    /**
     * get height in px of the diff for the given dates (with current scaling)
     *
     * @param {date} dtStart
     * @param {date} dtEnd
     * @returns {number}
     */
    getTimeHeight: function(dtStart, dtEnd) {
        if (this.mainBody) {
            var d = this.getMainBodyHeight() / (24 * 60);
            return Math.round(d * ((dtEnd.getTime() - dtStart.getTime()) / Date.msMINUTE));
        }
    },

    /**
     * get height in % of the diff for the given dates
     *
     * @param {date} dtStart
     * @param {date} dtEnd
     * @returns {number}
     */
    getTimeHeightPct: function(dtStart, dtEnd) {
        return 100 * ((dtEnd.getTime() - dtStart.getTime()) / Date.msDAY);
    },

    /**
     * get number of minutes represented by height in px (current scaleing)
     *
     * @param {number} height
     * @returns {number}
     */
    getHeightMinutes: function(height) {
        var d = (24 * 60) / this.getMainBodyHeight();
        return Math.round(d * height);
    },

    getMainBodyHeight: function() {
        if (! this.mainBody) {
            // maybe another app is active
            return 0;
        }

        var height = this.mainBody.getHeight();

        // hidden atm.
        if (! height) {
            height = parseInt(this.mainBody.dom.style.height, 10);
        }

        return height;
    },

    /**
     * fetches elements from our generated dom
     */
    initElements : function(){
        var E = Ext.Element;

        var cs = this.el.dom.firstChild.childNodes;

        this.mainWrap = new E(cs[0]);
        this.mainHd = new E(this.mainWrap.dom.firstChild);

        this.innerHd = this.mainHd.dom.firstChild;
        
        this.wholeDayScroller = new E(this.innerHd.firstChild.childNodes[1]);
        this.wholeDayArea = this.wholeDayScroller.dom.firstChild;
        
        this.scroller = new E(this.mainWrap.dom.childNodes[1]);
        this.scroller.setStyle('overflow-x', 'hidden');


        this.mon(this.scroller, 'mousewheel', this.onMouseWheel, this);
        this.mon(this.scroller, 'scroll', this.onBeforeScroll, this);
        this.mon(this.scroller, 'scroll', this.onScroll, this, {buffer: 200});


        this.cropper = new E(this.scroller.dom.firstChild);
        this.mainBody = new E(this.cropper.dom.firstChild);
        this.dayCols = this.mainBody.dom.lastChild.childNodes;

        this.focusEl = new E(this.el.dom.lastChild.lastChild);
        this.focusEl.swallowEvent("click", true);
        this.focusEl.swallowEvent("dblclick", true);
        this.focusEl.swallowEvent("contextmenu", true);
        
        this.aboveHints   = this.mainBody.select('img[class=cal-daysviewpanel-body-daycolumn-hint-above]');
        this.belowHints   = this.mainBody.select('img[class=cal-daysviewpanel-body-daycolumn-hint-below]');
    },
    
    /**
     * @TODO this returns wrong cols on DST boundaries:
     *  e.g. on DST switch form +2 to +1 an all day event is 25 hrs. long
     * 
     * @param {} date
     * @return {}
     */
    getColumnNumber: function(date) {
        return Math.floor((date.add(Date.SECOND, 1).getTime() - this.startDate.getTime()) / Date.msDAY);
    },
    
    getDateColumnEl: function(pos) {
        return this.dayCols[pos];
    },
    
    checkWholeDayEls: function() {
        var freeIdxs = [];
        for (var i=0; i<this.wholeDayArea.childNodes.length-1; i++) {
            if(this.wholeDayArea.childNodes[i].childNodes.length === 1) {
                freeIdxs.push(i);
            }
        }
        
        for (var i=1; i<freeIdxs.length; i++) {
            Ext.fly(this.wholeDayArea.childNodes[freeIdxs[i]]).remove();
        }
    },

    /**
     * buffered version of this.unbufferedOnLayout
     * @see this.initComponent
     */
    onLayout: Ext.emptyFn,

    /**
     * layouts the view
     */
    unbufferedOnLayout: function() {
        Tine.Calendar.DaysView.superclass.onLayout.apply(this, arguments);
        if(!this.mainBody){
            return; // not rendered
        }
        
        var csize = this.container.getSize(true),
            vw = csize.width;
        if (! vw) {
            return; // hidden
        }

        this.el.setSize(csize.width, csize.height);
        
        // layout whole day area -> take one third of the available height maximum
        var wholeDayAreaEl = Ext.get(this.wholeDayArea),
            wholeDayAreaHeight = this.computeAllDayAreaHeight(),
            wholeDayScrollerHeight = wholeDayAreaHeight,
            maxAllowedHeight = Math.round(csize.height/4),
            resizeEvent = {
                wholeDayAreaHeight: wholeDayAreaHeight,
                wholeDayScrollerHeight: Math.min(wholeDayAreaHeight, maxAllowedHeight),
                maxAllowedHeight: maxAllowedHeight
            };

        wholeDayAreaEl.setHeight(wholeDayAreaHeight);
        this.fireEvent('onBeforeAllDayScrollerResize', this, resizeEvent);

        this.wholeDayScroller.setHeight(resizeEvent.wholeDayScrollerHeight);
        
        var hdHeight = this.mainHd.getHeight();
        var vh = csize.height - (hdHeight);
        
        this.scroller.setSize(vw, vh);

        // resize mainBody for visibleMinutes to fit
        var timeToDisplay = this.timeVisible.getTime() / Date.msMINUTE,
            scrollerHeight = this.scroller.getHeight(),
            height = scrollerHeight * (24 * 60)/timeToDisplay;

        this.mainBody.setHeight(height);

        if (this.cropDayTime) {
            var cropHeightPx = this.getTimeHeight(this.dayStart, this.dayEnd),
                cropStartPx = this.getTimeOffset(this.dayStart);

            this.cropper.setStyle('height', cropHeightPx + 'px');
            this.cropper.dom.scrollTop = cropStartPx;
        }

        if (! this.initialScrolled) {
            // scrollTo initial position
            this.isScrolling = true;

            this.scrollTo(this.defaultStart);
            this.initialScrolled = true;
        }

        // force positioning on scroll hints
        this.onBeforeScroll.defer(50, this);
        this.onScroll.defer(100, this);
    },

    computeAllDayAreaHeight: function() {
        var wholeDayAreaEl = Ext.get(this.wholeDayArea);
        for (var i=0, bottom = wholeDayAreaEl.getTop(); i<this.wholeDayArea.childNodes.length -1; i++) {
            bottom = Math.max(parseInt(Ext.get(this.wholeDayArea.childNodes[i]).getBottom(), 10), bottom);
        }

        // take one third of the available height maximum
        return bottom - wholeDayAreaEl.getTop() + this.minAllDayScrollerHight;
    },
    
    /**
     * returns HTML frament of the day headers
     */
    getDayHeaders: function() {
        var html = '';
        var width = 100/this.numOfDays;
        
        for (var i=0, date; i<this.numOfDays; i++) {
            var day = this.startDate.add(Date.DAY, i);
            html += this.templates.dayHeader.applyTemplate({
                day: String.format(this.dayFormatString, day.format('l'), day.format('j'), day.format('F')),
                height: '20px',
                width: width + '%',
                left: i * width + '%'
            });
        }
        return html;
    },
    
    /**
     * updates HTML of day headers
     */
    updateDayHeaders: function() {
        if (! this.rendered) return;

        var dayHeaders = Ext.DomQuery.select('div[class=cal-daysviewpanel-dayheader-day]', this.innerHd),
            dayWidth = dayHeaders ? Ext.get(dayHeaders[0]).getWidth() : [],
            headerString;

        for (var i=0, date, isToDay, headerEl, dayColEl; i<dayHeaders.length; i++) {
            
            date = this.startDate.add(Date.DAY, i);
            isToDay = date.getTime() == new Date().clearTime().getTime();
            
            headerEl = Ext.get(dayHeaders[i]);
            
            if (dayWidth > 150) {
                headerString = String.format(this.dayFormatString, date.format('l'), date.format('j'), date.format('F'));
            } else if (dayWidth > 60){
                headerString = date.format('D') + ', ' + date.format('j') + '.' + date.format('n');
            } else {
                headerString = date.format('j') + '.' + date.format('n');
            }
            
            headerEl.update(headerString);
            headerEl.parent()[(isToDay ? 'add' : 'remove') + 'Class']('cal-daysviewpanel-dayheader-today');
            Ext.get(this.dayCols[i])[(isToDay ? 'add' : 'remove') + 'Class']('cal-daysviewpanel-body-daycolumn-today');
        }
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
                cls: time.get('minutes')%60 ? 'cal-daysviewpanel-timeRow-off' : 'cal-daysviewpanel-timeRow-on',
                height: 100/(24 * 60 / this.timeGranularity) + '%',
                time: time.get('minutes')%60 ? '' : time.get('time')
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
        var html = '',
            baseId = Ext.id();
        
        this.timeScale.each(function(time, index, totalCount) {
            var cls = 'cal-daysviewpanel-daycolumn-row-' + (time.get('minutes')%60 ? 'off' : 'on');
            if (index+1 == totalCount) {
                cls += ' cal-daysviewpanel-daycolumn-row-last';
            }

            html += this.templates.overRow.applyTemplate({
                id: baseId + ':' + dayIndex + ':' + index,
                cls: cls,
                height: 100/(24 * 60 / this.timeGranularity) + '%',
                time: time.get('time')
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
                    '<div class="cal-daysviewpanel-header">',
                        '<div class="cal-daysviewpanel-header-inner">',
                            '<div class="cal-daysviewpanel-header-offset">{header}</div>',
                        '</div>',
                    '<div class="x-clear"></div>',
                '</div>',
                '<div class="cal-daysviewpanel-scroller">',
                    '<div class="cal-daysviewpanel-cropper">{body}</div>',
                '</div>',
            '</div>',
            '<a href="#" class="cal-daysviewpanel-focus" tabIndex="-1"></a>'
        );
        
        ts.header = new Ext.XTemplate(
            '<div class="cal-daysviewpanel-daysheader">{daysHeader}</div>',
            '<div class="cal-daysviewpanel-wholedayheader-scroller">',
                '<div class="cal-daysviewpanel-wholedayheader">',
                    '<div class="cal-daysviewpanel-wholedayheader-daycols">{wholeDayCols}</div>',
                '</div>',
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
                '<div id="{id}" class="cal-daysviewpanel-datetime cal-daysviewpanel-body-wholedaycolumn-over">&#160;</div>' +
            '</div>'
        );
        
        ts.body = new Ext.XTemplate(
            '<div class="cal-daysviewpanel-body">',
                '<div class="cal-daysviewpanel-body-timecolumn">{timeRows}</div>',
                '<div class="cal-daysviewpanel-body-daycolumns">{dayColumns}</div>',
            '</div>'
        );
        
        ts.timeRow = new Ext.XTemplate(
            '<div class="{cls}" style="height: {height}; top: {top};">',
                '<div class="cal-daysviewpanel-timeRow-time">{time}</div>',
            '</div>'
        );
        
        ts.dayColumn = new Ext.XTemplate(
            '<div class="cal-daysviewpanel-body-daycolumn" style="left: {left}; width: {width}; height: 100%">',
                '<div class="cal-daysviewpanel-body-daycolumn-inner">&#160;</div>',
                '{overRows}',
                '<img src="', Ext.BLANK_IMAGE_URL, '" class="cal-daysviewpanel-body-daycolumn-hint-above" />',
                '<img src="', Ext.BLANK_IMAGE_URL, '" class="cal-daysviewpanel-body-daycolumn-hint-below" />',
            '</div>'
        );
        
        ts.overRow = new Ext.XTemplate(
            '<div id="{id}" class="cal-daysviewpanel-datetime cal-daysviewpanel-daycolumn-row" style="height: {height};">' +
                '<div class="{cls}" >{time}</div>'+
            '</div>'
        );
        
        ts.event = new Ext.XTemplate(
            '<div id="{id}" class="cal-daysviewpanel-event {extraCls}" style="width: {width}; height: {height}; left: {left}; top: {top}; z-index: {zIndex}; background-color: {bgColor}; border-color: {color};">',
                '<div class="cal-daysviewpanel-event-header" style="background-color: {bgColor};">',
                    '<div class="cal-daysviewpanel-event-header-inner" style="color: {textColor}; background-color: {bgColor}; z-index: {zIndex};">{startTime}</div>',
                    '<div class="cal-daysviewpanel-event-header-icons">',
                        '<tpl for="statusIcons">',
                            '<img src="', Ext.BLANK_IMAGE_URL, '" class="cal-status-icon {status}-{[parent.textColor == \'#FFFFFF\' ? \'white\' : \'black\']}" ext:qtip="{[this.encode(values.text)]}" />',
                        '</tpl>',
                    '</div>',
                '</div>',
                '<div class="cal-daysviewpanel-event-body">{[Ext.util.Format.nl2br(Ext.util.Format.htmlEncode(values.summary))]}</div>',
                '<div class="cal-daysviewpanel-event-tags">{tagsHtml}</div>',
            '</div>',
            {
                encode: function(v) { return Tine.Tinebase.common.doubleEncode(v); }
            }
        );
        
        ts.wholeDayEvent = new Ext.XTemplate(
            '<div id="{id}" class="cal-daysviewpanel-event {extraCls}" style="width: {width}; height: {height}; left: {left}; top: {top}; z-index: {zIndex}; background-color: {bgColor}; border-color: {color};">' +
            '<div class="cal-daysviewpanel-wholedayevent-tags">{tagsHtml}</div>' +
                '<div class="cal-daysviewpanel-wholedayevent-body">{[Ext.util.Format.nl2br(Ext.util.Format.htmlEncode(values.summary))]}</div>' +
                '<div class="cal-daysviewpanel-event-header-icons" style="background-color: {bgColor};" >' +
                    '<tpl for="statusIcons">' +
                        '<img src="', Ext.BLANK_IMAGE_URL, '" class="cal-status-icon {status}-black" ext:qtip="{[this.encode(values.text)]}" />',
                    '</tpl>' +
                '</div>' +
            '</div>',
            {
                encode: function(v) { return Tine.Tinebase.common.doubleEncode(v); }
            }
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

Ext.reg('Tine.Calendar.DaysView', Tine.Calendar.DaysView);
