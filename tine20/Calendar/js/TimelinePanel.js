/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine.Calendar');

/**
 * @namespace   Tine.Calendar
 * @class       Tine.Calendar.TimelinePanel
 * @extends     Ext.Panel
 *
 * Calendar timeline view
 *
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @constructor
 * @param {Object} config
 */
Tine.Calendar.TimelinePanel = Ext.extend(Ext.Panel, {
    /**
     * @cfg {Object} period
     * period to display
     */
    period: null,

    /**
     * @cfg {Ext.data.Store} store
     */
    store: null,

    /**
     * @cfg {String} viewType
     */
    viewType: 'month',

    /**
     * @cfg {Number} labelWidth
     * width of left label column
     */
    labelWidth: 150,

    /**
     * @cfg {String} dayFormatString
     * i18n._('{0}, the {1}. of {2}')
     */
    dayFormatString: '{0}, the {1}. of {2}',

    /**
     * @property {Number} scalingFactor
     * percentage per millisecond
     */
    scalingFactor: null,

    groupingMetadataCache: null,

    layout: 'vbox',
    border: false,
    layoutConfig: {
        align : 'stretch',
        pack  : 'start'
    },

    initComponent: function() {
        this.app = Tine.Tinebase.appMgr.get('Calendar');
        this.dayFormatString = this.app.i18n._hidden(this.dayFormatString);
        this.groupingMetadataCache = {};
        this.labels = [];

        this.items = [{
            ref: 'headerRow',
            height: 24,
            layout: 'hbox',
            border: false,
            xtype: 'container',
            layoutConfig: {
                align : 'stretch',
                pack  : 'start'
            },
            items: [{
                ref: '../periodLabel',
                cls: 'cal-timelinepanel-header-label',
                width: this.labelWidth,
                border: false,
                xtype: 'container'
            }, {
                ref: '../timelineHeading',
                flex: 1,
                layout: 'fit',
                border: false,
                xtype: 'container'
            }]
        }, {
            ref: 'vScroller',
            cls: 'cal-timelinepanel-vscroller',
            flex: 1,
            layout: 'hbox',
            border: false,
            xtype: 'container',
            layoutConfig: {
                align : 'stretch',
                pack  : 'start'
            },
            items: [{
                ref: '../timelineLabels',
                cls: 'cal-timelinepanel-yscroller-labels',
                width: this.labelWidth,
                border: false,
                xtype: 'container',
                autoHeight: true
            }, {
                ref: '../timelines',
                cls: 'cal-timelinepanel-yscroller-timelines',
                flex: 1,
                border: false,
                xtype: 'container',
                autoHeight: true
            }]
        }];

        this.groupCollection = new Tine.Tinebase.data.GroupedStoreCollection({
            store: this.store,
            group: this.groupingFn.createDelegate(this),
            groupOnLoad: false,
            listeners: {
                scope: this,
                add: this.onGroupAdd,
                remove: this.onGroupRemove
            }
        });
        this.store.on('load', this.onStoreLoad, this);

        this.initTemplates();

        if (! this.selModel) {
            this.selModel = this.selModel || new Tine.Calendar.EventSelectionModel();
        }
        Tine.Calendar.TimelinePanel.superclass.initComponent.apply(this, arguments);
    },

    onStoreLoad: function(store, records, options) {
        this.groupingMetadataCache = {};

        var fixedGroups = [],
            attendeeFilterValue = Tine.Calendar.AttendeeFilterGrid.prototype.extractFilterValue(options.params.filter),
            attendeeStore = attendeeFilterValue ? Tine.Calendar.Model.Attender.getAttendeeStore(attendeeFilterValue) : null;

        if (attendeeStore) {
            attendeeStore.each(function(attendee) {
                // NOTE: we can't cope yet with memberOf entries as we would nee to know
                //       the listmembers of the list to add them to the group
                if (attendee.get('user_type') == 'memberOf') return;

                var groupName = attendee.getCompoundId();
                fixedGroups.push(groupName);
                this.groupingMetadataCache[groupName] = attendee;
            }, this);
        }
        this.groupCollection.setFixedGroups(fixedGroups);
    },

    groupingFn: function(event) {
        var groups = [],
            attendeeStore = Tine.Calendar.Model.Attender.getAttendeeStore(event.get('attendee'));

        attendeeStore.each(function(attendee) {
            var groupName = this.getAttendeeType(attendee) + '-' + attendee.getUserId();
            if (! this.groupingMetadataCache.hasOwnProperty(groupName)) {
                this.groupingMetadataCache[groupName] = attendee;
            }
            groups.push(groupName);
        }, this);

        return groups;
    },

    getAttendeeType: function(attendee) {
        var attendeeType = attendee.get('user_type');
        return  attendeeType === 'groupmember' ? 'user' : attendeeType;
    },

    onGroupAdd: function(idx, groupStore, groupName) {
        var _ = window.lodash,
            attendee = this.groupingMetadataCache[groupName],
            name = Tine.Calendar.AttendeeGridPanel.prototype.renderAttenderName.call(Tine.Calendar.AttendeeGridPanel.prototype, attendee.get('user_id'), false, attendee),
            type = this.getAttendeeType(attendee);

        var label = new Tine.Calendar.TimelineLabel({
            groupName: groupName,
            label: name,
            iconCls: type === 'user' ? 'tine-grid-row-action-icon renderer_typeAccountIcon' : attendee.getIconCls(),
            groupSortOrder: Tine.Calendar.Model.Attender.getSortOrder(type)
        });

        this.labels.push(label);
        this.labels = _.sortBy(this.labels, ['groupSortOrder', 'label']);

        var idx = this.labels.indexOf(label);

        this.timelineLabels.insert(idx, label);
        this.timelineLabels.doLayout();

        var view = new Tine.Calendar.TimelineView({
            groupName: groupName,
            store: groupStore,
            period: this.period,
            label: label
        });
        this.timelines.insert(idx, view);
        this.timelines.doLayout();
        this.relayEvents(view, ['click', 'dblclick', 'contextmenu']);
        view.getSelectionModel().on('selectionchange', this.onSelectionChange.createDelegate(this, [view]));
    },

    onGroupRemove: function(groupStore, groupName) {
        this.timelineLabels.remove(this.timelineLabels.find('groupName', groupName)[0]);
        this.timelines.remove(this.timelines.find('groupName', groupName)[0]);
    },

    getSelectionModel: function() {
        return this.selModel;
    },

    onSelectionChange: function(view) {
        if (this.selectionChangeLocked) return;
        this.selectionChangeLocked = true;

        view = Ext.isString(view) ? this.timelines.items.get(view) : view;
        this.activeView = view;

        // clear selections from other views
        this.timelines.items.each(function(v) {
            if (v !== view) {
                v.getSelectionModel().clearSelections(true);
            }
        }, this);

        var selections = view.getSelectionModel().getSelectedEvents();
        this.getSelectionModel().clearSelections();
        Ext.each(selections, function(viewEvent) {
            var event = this.store.getById(viewEvent.id);
            // @TODO: have own (grouping) ui
            if (event) {
                event.ui = viewEvent.ui;
            }
            this.getSelectionModel().select(event, true);
        }, this);

        this.selectionChangeLocked = false;
    },

    getPeriod: function() {
        return this.period;
    },

    updatePeriod: function(period) {
        this.period = period;

        var tbar = this.getTopToolbar();
        if (tbar) {
            tbar.periodPicker.update(period.from);
            this.period = tbar.periodPicker.getPeriod();
            this.viewType = tbar.view;
        }

        // @TODO do this on period change only
        this.timelines.items.each(function(view) {
            view.updatePeriod(this.period);
        }, this);
        this.onLayout();
    },

    onLayout: function() {
        Tine.Calendar.TimelinePanel.superclass.onLayout.apply(this, arguments);

        // update header & scaling
        var msTotal = this.period.until.getTime() - this.period.from.getTime();
        this.scalingFactor = 100 / msTotal;

        // this.periodLabel.update(this.period.from.format())
        this.setHeaders();
    },

    setHeaders: function() {
        var items = [],
            item = null,
            itemStart = this.period.from,
            periodEndTs = this.period.until.getTime(),
            unit = this.viewType == 'day' ? Date.HOUR : Date.DAY,
            formatString = this.viewType == 'day' ? 'G' : 'd',
            itemEnd = itemStart.add(unit, 1);

        if (this.viewType != 'day') {
            itemEnd.setHours(0);
        }
        itemEnd.setMinutes(0);
        itemEnd.setSeconds(0);
        itemEnd.setMilliseconds(0);

        while (itemEnd <= this.period.until) {
            item = {
                header: itemStart.format(formatString),
                width: this.scalingFactor * (Math.min(periodEndTs, itemEnd.getTime()) - itemStart.getTime())
            };

            item.headerLong =  this.viewType == 'day' ?
                item.header :
                String.format(this.dayFormatString, itemStart.format('l'), itemStart.format('j'), itemStart.format('F'));

            items.push(item);
            itemStart = itemEnd;
            itemEnd = itemStart.add(unit, 1);
        }

        this.timelineHeading.update(this.templates.header.apply(items));
    },

    onResize: function(w, h) {
        Tine.Calendar.TimelinePanel.superclass.onResize.apply(this, arguments);


    },

    getTargetEvent: function(e) {
        var viewEl = e.getTarget('.cal-timeline-view'),
            id = viewEl ? viewEl.id : undefined,
            view = this.timelines.items.get(id);

        return view.getTargetEvent(e);
    },

    getTargetDateTime: Ext.emptyFn,


    print: function() {
        Ext.ux.Printer.print(this.grid);
    },

    getView: function() {
        return this;
    },

    getStore: function() {
        return this.store;
    },

    initTemplates: function() {
        var ts = this.templates || {};

        ts.header = new Ext.XTemplate(
            '<div class="cal-timelinepanel-header">',
            '<tpl for=".">',
                '<div class="cal-timelinepanel-headeritem<tpl if="width &gt; 10"> cal-timelinepanel-header-long</tpl>" style="width: {width}%;">',
                    '<span class="cal-timelinepanel-headeritem-short">{header}</span>',
                    '<span class="cal-timelinepanel-headeritem-long">{headerLong}</span>',
                '</div>',
            '</tpl>',
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
