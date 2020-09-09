/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2016 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */
 
Ext.ns('Tine.Calendar');

Tine.Calendar.EventUI = function(event) {
    this.event = event;
    this.domIds = [];
    this.app = Tine.Tinebase.appMgr.get('Calendar');
    this.init();
};

Tine.Calendar.EventUI.prototype = {
    zIndex: 100,
    
    addClass: function(cls) {
        Ext.each(this.getEls(), function(el){
            el.addClass(cls);
        });
    },

    removeClass: function(cls) {
        Ext.each(this.getEls(), function(el){
            el.removeClass(cls);
        });
    },

    focus: function() {
        Ext.each(this.getEls(), function(el){
            el.focus();
        });
    },

    blur: function() {
        Ext.each(this.getEls(), function(el){
            el.blur();
        });
    },
    
    clearDirty: function() {
        this.setOpacity(1, 1);
    },

    setStyle: function(style) {
        Ext.each(this.getEls(), function(el){
            el.setStyle(style);
        });
    },

    getStyle: function(property) {
        var value;
        Ext.each(this.getEls(), function(el){
            value = el.getStyle(property);
            return false;
        });

        return value;
    },

    setOpacity: function(v, a) {
        Ext.each(this.getEls(), function(el){
            el.setOpacity(v, a);
        });
    },

    /**
     * returns events dom
     * @return {Array} of Ext.Element
     */
    getEls: function() {
        var domEls = [];
        for (var i=0; i < this.domIds.length; i++) {
            var el = Ext.get(this.domIds[i]);
            if (el) {
                domEls.push(el);
            }
        }
        return domEls;
    },
    
    init: function() {
        // shortcut
        //this.colMgr = Tine.Calendar.colorMgr;
    },
    
    markDirty: function() {
        this.setOpacity(0.5, 1);
    },

    markOutOfFilter: function() {
        Ext.each(this.getEls(), function(el) {
            el.setOpacity(0.5, 0);
            el.setStyle({'background-color': '#aaa', 'border-color': '#888'});
            Ext.DomHelper.applyStyles(el.dom.firstChild, {'background-color': '#888'});
            if (el.dom.firstChild.firstChild) {
                Ext.DomHelper.applyStyles(el.dom.firstChild.firstChild, {'background-color': '#888'});
            }
            if (_.get(el, 'dom.firstChild.children[1]')) {
                Ext.DomHelper.applyStyles(_.get(el, 'dom.firstChild.children[1]'), {'background-color': '#888'});
            }
        });
    },
    
    onSelectedChange: function(state){
        if(state){
            //this.focus();
            this.addClass('cal-event-active');
            this.setStyle({'z-index': 1000});
            
        }else{
            //this.blur();
            this.removeClass('cal-event-active');
            this.setStyle({'z-index': this.zIndex});
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
        if (this.resizeable) {
            this.resizeable.destroy();
            this.resizeable = null;
        }
        this.domIds = [];
    },

    render: function(view) {
        this.event.view = view;

        this.attendeeRecord = view.ownerCt && view.ownerCt.attendee ?
            Tine.Calendar.Model.Attender.getAttendeeStore.getAttenderRecord(this.event.getAttendeeStore(), view.ownerCt.attendee) :
            this.event.getMyAttenderRecord();

        this.colorSet = Tine.Calendar.colorMgr.getColor(this.event, this.attendeeRecord);
        this.event.colorSet = this.colorSet;

        this.dtStart = this.event.get('dtstart');
        this.dtEnd = this.event.get('dtend');
        // 00:00 in users timezone is a spechial case where the user expects
        // something like 24:00 and not 00:00
        if (this.dtEnd.format('H:i') == '00:00') {
            this.dtEnd = this.dtEnd.add(Date.MINUTE, -1);
        }
        
        if (this.event.get('editGrant')) {
            this.extraCls = 'cal-daysviewpanel-event-editgrant';
        }

        this.extraCls += ' cal-status-' + this.event.get('status');

        if (this.event.hasPoll()) {
            this.extraCls += ' cal-poll-event';
        }

        // compute status icons
        this.statusIcons = Tine.Calendar.EventUI.getStatusInfo(this.event, this.attendeeRecord);
    }
};

Tine.Calendar.EventUI.getStatusInfo = function(event, attendeeRecord) {
    var _ = window.lodash,
        app = Tine.Tinebase.appMgr.get('Calendar'),
        statusInfo = [];
    
    if (event.get('class') === 'PRIVATE') {
        statusInfo.push({
            status: 'private',
            text: app.i18n._('private classification')
        });
    }

    if (event.get('rrule')) {
        statusInfo.push({
            status: 'recur',
            text: app.i18n._('recurring event')
        });
    } else if (event.isRecurException()) {
        statusInfo.push({
            status: 'recurex',
            text: app.i18n._('recurring event exception')
        });
    }

    if (! Ext.isEmpty(event.get('alarms'))) {
        statusInfo.push({
            status: 'alarm',
            text: app.i18n._('has alarm')
        });
    }

    if (! Ext.isEmpty(event.get('attachments'))) {
        statusInfo.push({
            status: 'attachment',
            text: app.i18n._('has attachments')
        });
    }

    if (event.hasPoll()) {
        statusInfo.push({
            status: 'poll',
            text: app.i18n._('is part of an open poll')
        });
    }

    var attenderStatusRecord = attendeeRecord ? Tine.Tinebase.widgets.keyfield.StoreMgr.get('Calendar', 'attendeeStatus').getById(attendeeRecord.get('status')) : null;

    if (attenderStatusRecord && attenderStatusRecord.get('system')) {
        statusInfo.push({
            status: attendeeRecord.get('status'),
            text: attenderStatusRecord.get('i18nValue')
        });
    }
    
    return statusInfo;
}
