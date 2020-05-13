/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2016 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine.Calendar');

Tine.Calendar.DaysViewEventUI = Ext.extend(Tine.Calendar.EventUI, {

    clearDirty: function() {
        Tine.Calendar.DaysViewEventUI.superclass.clearDirty.call(this);

        Ext.each(this.getEls(), function(el) {
            el.setStyle({'border-left-style': 'solid'});
        });
    },
    /**
     * get diff of resizeable
     *
     * @param {Ext.Resizeable} rz
     */
    getRzInfo: function(rz, width, height) {
        var rzInfo = {};

        var event = rz.event;
        var view = event.view;

        // NOTE proxy might be gone after resize
        var box = rz.proxy.getBox();
        var width = width ? width: box.width;
        var height =  height? height : box.height;

        var originalDuration = (event.get('dtend').getTime() - event.get('dtstart').getTime()) / Date.msMINUTE;

        if(event.get('is_all_day_event')) {
            var dayWidth = Ext.fly(view.wholeDayArea).getWidth() / view.numOfDays;
            rzInfo.diff = Math.round((width - rz.originalWidth) / dayWidth);

        } else {
            rzInfo.diff = view.getHeightMinutes(height - rz.originalHeight);
            // neglegt diffs due to borders etc.
            rzInfo.diff = Math.ceil(rzInfo.diff/view.timeIncrement) * view.timeIncrement;
        }
        rzInfo.duration = originalDuration + rzInfo.diff;

        if(event.get('is_all_day_event')) {
            rzInfo.dtend = event.get('dtend').add(Date.DAY, rzInfo.diff);
        } else {
            rzInfo.dtend = event.get('dtstart').add(Date.MINUTE, rzInfo.duration);
        }

        return rzInfo;
    },

    markDirty: function() {
        Tine.Calendar.DaysViewEventUI.superclass.markDirty.call(this);

        Ext.each(this.getEls(), function(el) {
            el.setStyle({'border-left-style': 'dashed'});
        });
    },

    render: function(view) {
        Tine.Calendar.DaysViewEventUI.superclass.render.call(this, view);

        this.startColNum = view.getColumnNumber(this.dtStart);
        this.endColNum = view.getColumnNumber(this.dtEnd);

        // skip dates not in our diplay range
        if (this.endColNum < 0 || this.startColNum > view.numOfDays-1) {
            return;
        }

        var registry = this.event.get('is_all_day_event') ? view.parallelWholeDayEventsRegistry : view.parallelScrollerEventsRegistry;

        var position = registry.getPosition(this.event);
        var maxParallels = registry.getMaxParalles(this.dtStart, this.dtEnd);

        if (this.event.get('is_all_day_event')) {
            this.renderAllDayEvent(view, maxParallels, position);
        } else {
            this.renderScrollerEvent(view, maxParallels, position);
        }

        var ids = Tine.Tinebase.data.Clipboard.getIds('Calendar', 'Event');

        if (ids.indexOf(this.event.get('id')) > -1) {
            this.markDirty(true);
        } else if (this.event.dirty) {
            // the event was selected before
            this.onSelectedChange(true);
        }

        this.rendered = true;
    },

    renderAllDayEvent: function(view, parallels, pos) {
        // lcocal COPY!
        var extraCls = this.extraCls;

        var offsetWidth = Ext.fly(view.wholeDayArea).getWidth();

        //var width = Math.round(offsetWidth * (this.dtEnd.getTime() - this.dtStart.getTime()) / (view.numOfDays * Date.msDAY)) -5;
        //var left = Math.round(offsetWidth * (this.dtStart.getTime() - view.startDate.getTime()) / (view.numOfDays * Date.msDAY));

        var width = Math.floor(1000 * (this.dtEnd.getTime() - this.dtStart.getTime()) / (view.numOfDays * Date.msDAY) -5) /10;
        var left = 100 * (this.dtStart.getTime() - view.startDate.getTime()) / (view.numOfDays * Date.msDAY);


        if (left < 0) {
            width = width + left;
            left = 0;
            extraCls = extraCls + ' cal-daysviewpanel-event-cropleft';
        }

        if (left + width > offsetWidth) {
            width = offsetWidth - left;
            extraCls = extraCls + ' cal-daysviewpanel-event-cropright';
        }

        var domId = Ext.id() + '-event:' + this.event.get('id');
        this.domIds.push(domId);

        var eventEl = view.templates.wholeDayEvent.insertFirst(view.wholeDayArea, {
            id: domId,
            tagsHtml: Tine.Tinebase.common.tagsRenderer(this.event.get('tags')),
            summary: this.event.getTitle(),
            startTime: this.dtStart.format('H:i'),
            extraCls: extraCls,
            color: this.colorSet.color,
            bgColor: this.colorSet.light,
            textColor: this.colorSet.text,
            zIndex: 100,
            width: width  +'%',
            height: '15px',
            left: left + '%',
            top: pos * 18 + 'px',//'1px'
            statusIcons: this.statusIcons
        }, true);

        if (this.event.dirty) {
            eventEl.setStyle({'border-left-style': 'dashed'});
            eventEl.setOpacity(0.5);
        }

        if (! (this.endColNum > view.numOfDays) && this.event.get('editGrant') && !view.readOnly) {
            this.resizeable = new Ext.Resizable(eventEl, {
                handles: 'e',
                disableTrackOver: true,
                dynamic: true,
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
        //console.log([eventEl.dom, parallels, pos])
    },

    renderScrollerEvent: function(view, parallels, pos) {
        var mainBodyHeight = view.getMainBodyHeight();

        for (var currColNum=this.startColNum; currColNum<=this.endColNum; currColNum++) {

            if (currColNum < 0 || currColNum >= view.numOfDays) {
                continue;
            }

            var domId = Ext.id() + '-event:' + this.event.get('id'),
                extraCls = this.extraCls,
                top = view.getTimeOffsetPct(this.dtStart),
                height = this.startColNum == this.endColNum ?
                    view.getTimeHeightPct(this.dtStart, this.dtEnd) :
                    view.getTimeOffsetPct(this.dtEnd),
                isShortEvent = (height * mainBodyHeight/100) < 24;

            this.domIds.push(domId);

            if (currColNum != this.startColNum) {
                top = 0;
                extraCls = extraCls + ' cal-daysviewpanel-event-croptop';
            }

            if (this.endColNum != currColNum) {
                height = view.getTimeHeightPct(this.dtStart, this.dtStart.add(Date.DAY, 1));
                extraCls = extraCls + ' cal-daysviewpanel-event-cropbottom';
            }

            var eventEl = view.templates.event.append(view.getDateColumnEl(currColNum), {
                id: domId,
                summary: isShortEvent ? '' : this.event.getTitle(),
                tagsHtml: isShortEvent ? '' : Tine.Tinebase.common.tagsRenderer(this.event.get('tags')),
                startTime: isShortEvent ? this.dtStart.format('H:i') + ' ' +  this.event.getTitle() : this.dtStart.format('H:i'),
                extraCls: extraCls,
                color: this.colorSet.color,
                bgColor: this.colorSet.light,
                textColor: '#000000',//this.colorSet.text,
                zIndex: 100,
                height: height + '%',
                left: Math.round(pos * 90 * 1/parallels) + '%',
                width: Math.round(90 * 1/parallels) + '%',
                top: top + '%',
                statusIcons: this.statusIcons
            }, true);

            if (this.event.dirty) {
                eventEl.setStyle({'border-left-style': 'dashed'});
                eventEl.setOpacity(0.5);
            }

            if (currColNum == this.endColNum && this.event.get('editGrant') && !view.readOnly) {
                this.resizeable = new Ext.Resizable(eventEl, {
                    handles: 's',
                    disableTrackOver: true,
                    dynamic: true,
                    minHeight: view.getTimeOffset(view.timeGranularity),
                    heightIncrement: view.getTimeOffset(view.timeGranularity),
                    resizeElement: function() {
                        return this.proxy.getBox();
                    },
                    listeners: {
                        scope: view,
                        resize: view.onEventResize,
                        beforeresize: view.onBeforeEventResize
                    }
                });
            }
        }
    },

    onSelectedChange: function(state){
        Tine.Calendar.MonthViewEventUI.superclass.onSelectedChange.call(this, state);
        var style = {
            'background-color': state ? this.colorSet.color : this.colorSet.light,
            'color':            state ? this.colorSet.text: '#000000'
        };

        Ext.each(this.getEls(), function(el) {
            el.setStyle(style);
            el.select('div[class^=cal-daysviewpanel-event-header]').setStyle(style);
            el.select('.cal-status-icon').each((img) => {
                let status = img.dom.className.match(/([-a-zA-Z]+)-(?:black|white)/)[1];
                img.removeClass([status + '-black', status + '-white']);
                img.addClass(status + (style.color === '#FFFFFF' ? '-white' : '-black'));
            });
        }, this);
    }
});
