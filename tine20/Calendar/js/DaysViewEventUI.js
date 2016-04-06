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
            el.setStyle({'border-style': 'solid'});
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
            rzInfo.diff = Math.round(rzInfo.diff/view.timeIncrement) * view.timeIncrement;
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
            el.setStyle({'border-style': 'dashed'});
        });
    },

    onSelectedChange: function(state){
        Tine.Calendar.DaysViewEventUI.superclass.onSelectedChange.call(this, state);
        if(state){
            this.addClass('cal-daysviewpanel-event-active');

        }else{
            this.removeClass('cal-daysviewpanel-event-active');
        }
    },

    render: function(view) {
        this.event.view = view;

        this.attendeeRecord = view.ownerCt && view.ownerCt.attendee ?
            Tine.Calendar.Model.Attender.getAttendeeStore.getAttenderRecord(this.event.getAttendeeStore(), view.ownerCt.attendee) :
            this.event.getMyAttenderRecord();

        this.colorSet = Tine.Calendar.colorMgr.getColor(this.event, this.attendeeRecord);
        this.event.colorSet = this.colorSet;

        this.dtStart = this.event.get('dtstart');
        this.startColNum = view.getColumnNumber(this.dtStart);

        this.dtEnd = this.event.get('dtend');

        if (this.event.get('editGrant')) {
            this.extraCls = 'cal-daysviewpanel-event-editgrant';
        }

        this.extraCls += ' cal-status-' + this.event.get('status');

        // 00:00 in users timezone is a spechial case where the user expects
        // something like 24:00 and not 00:00
        if (this.dtEnd.format('H:i') == '00:00') {
            this.dtEnd = this.dtEnd.add(Date.MINUTE, -1);
        }
        this.endColNum = view.getColumnNumber(this.dtEnd);

        // skip dates not in our diplay range
        if (this.endColNum < 0 || this.startColNum > view.numOfDays-1) {
            return;
        }

        // compute status icons
        this.statusIcons = [];
        if (this.event.get('class') === 'PRIVATE') {
            this.statusIcons.push({
                status: 'private',
                text: this.app.i18n._('private classification')
            });
        }

        if (this.event.get('rrule')) {
            this.statusIcons.push({
                status: 'recur',
                text: this.app.i18n._('recurring event')
            });
        } else if (this.event.isRecurException()) {
            this.statusIcons.push({
                status: 'recurex',
                text: this.app.i18n._('recurring event exception')
            });
        }



        if (! Ext.isEmpty(this.event.get('alarms'))) {
            this.statusIcons.push({
                status: 'alarm',
                text: this.app.i18n._('has alarm')
            });
        }

        if (! Ext.isEmpty(this.event.get('attachments'))) {
            this.statusIcons.push({
                status: 'attachment',
                text: this.app.i18n._('has attachments')
            });
        }

        var attenderStatusRecord = this.attendeeRecord ? Tine.Tinebase.widgets.keyfield.StoreMgr.get('Calendar', 'attendeeStatus').getById(this.attendeeRecord.get('status')) : null;

        if (attenderStatusRecord && attenderStatusRecord.get('system')) {
            this.statusIcons.push({
                status: this.attendeeRecord.get('status'),
                text: attenderStatusRecord.get('i18nValue')
            });
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

        if (this.event.outOfFilter) {
            this.markOutOfFilter();
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
            summary: this.event.get('summary'),
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
            eventEl.setStyle({'border-style': 'dashed'});
            eventEl.setOpacity(0.5);
        }

        if (! (this.endColNum > view.numOfDays) && this.event.get('editGrant')) {
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
                summary: isShortEvent ? '' : this.event.get('summary'),
                tagsHtml: isShortEvent ? '' : Tine.Tinebase.common.tagsRenderer(this.event.get('tags')),
                startTime: isShortEvent ? this.dtStart.format('H:i') + ' ' +  this.event.get('summary') : this.dtStart.format('H:i'),
                extraCls: extraCls,
                color: this.colorSet.color,
                bgColor: this.colorSet.light,
                textColor: this.colorSet.text,
                zIndex: 100,
                height: height + '%',
                left: Math.round(pos * 90 * 1/parallels) + '%',
                width: Math.round(90 * 1/parallels) + '%',
                top: top + '%',
                statusIcons: this.statusIcons
            }, true);

            if (this.event.dirty) {
                eventEl.setStyle({'border-style': 'dashed'});
                eventEl.setOpacity(0.5);
            }

            if (currColNum == this.endColNum && this.event.get('editGrant')) {
                this.resizeable = new Ext.Resizable(eventEl, {
                    handles: 's',
                    disableTrackOver: true,
                    dynamic: true,
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
