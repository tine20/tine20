/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine.Calendar');

require('../css/timelinepanel.css');

/**
 * @namespace   Tine.Calendar
 * @class       Tine.Calendar.TimelineView
 * @extends     Tine.Calendar.AbstractView
 *
 * Calendar timeline view
 *
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @constructor
 * @param {Object} config
 */
Tine.Calendar.TimelineView = Ext.extend(Tine.Calendar.AbstractView, {
    /**
     * @cfg {Ext.data.Store} store
     */
    store: null,

    /**
     * @cfg {Object} period
     * period to display
     */
    period: null,

    /**
     * @cfg {Tine.Calendar.TimelineLabel} label
     */
    label: null,

    initialHeight: 25,
    collapsed: true,
    cls: 'cal-timeline-view',
    collapsedCls: 'cal-timeline-view-collapsed',
    eventCls: 'cal-daysviewpanel-event',


    initComponent: function() {
        if (this.collapsed) {
            this.cls = this.cls + ' ' + this.collapsedCls;
        }
        this.style = 'height: ' + this.initialHeight + 'px;';

        if (this.label) {
            this.label.on('beforeexpand', function(label, o) {
                this.expand();
                o.expandedHeight = this.getExpandedHeight();
            } , this);
            this.label.on('collapse', function(o) {
                this.collapse();
            } , this);
        }

        this.initParallelEventRegistry();
        this.updatePeriod(this.period);

        Tine.Calendar.TimelineView.superclass.initComponent.call(this);
    },

    unbufferedOnLayout: function(shallow, forceLayout) {
        var currHeight = this.el.getHeight(),
            height = this.collapsed ? this.initialHeight : this.getExpandedHeight();

        if (currHeight != height) {
            this.getEl().dom.style.height = height + 'px';
            if (this.label) {
                this.label.getEl().dom.style.height = height + 'px';
            }
        }
    },

    updatePeriod: function(period) {
        this.period = period;

        var msTotal = this.period.until.getTime() - this.period.from.getTime();
        this.scalingFactor = 100 / msTotal;
    },

    insertEvent: function(event) {
        event.ui = new Tine.Calendar.TimelineViewEventUI(event);
        event.ui.render(this);

        this.onLayout();
    },

    getExpandedHeight: function() {
        return Math.max(this.initialHeight, this.getParallelEventRegistry().map.length * this.initialHeight);
    },

    toggleCollapsed: function() {
        return this.collapsed ? this.expand() : this.collapse();
    },

    collapse: function() {
        if (! this.collapsed) {
            this.getEl().addClass(this.collapsedCls);
            this.collapsed = true;
            this.getEl().dom.style.height = this.initialHeight + 'px';
            this.store.each(this.replaceEvent, this);

            this.fireEvent('collapse', this, this.initialHeight);
        }
    },

    expand: function() {
        var expandedHeight = this.getExpandedHeight();
        this.collapsed = false;
        this.getEl().removeClass(this.collapsedCls);
        this.getEl().dom.style.height = expandedHeight + 'px';
        this.store.each(this.replaceEvent, this);
        this.fireEvent('expand', this, expandedHeight);
    },

    /**
     * inits all tempaltes of this view
     */
    initTemplates: function() {
        var ts = this.templates || {};

        ts.event = new Ext.XTemplate(
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