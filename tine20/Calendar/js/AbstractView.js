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
 * @class       Tine.Calendar.AbstractView
 * @extends     Ext.Container
 *
 * Calendar abstract view
 *
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @constructor
 * @param {Object} config
 */
Tine.Calendar.AbstractView = Ext.extend(Ext.Container, {
    /**
     * @cfg {String} eventCls
     * css class of event ui
     */
    eventCls: '-evnet',

    initComponent: function() {
        this.initTemplates();
        this.initData(this.store);

        if (! this.selModel) {
            this.selModel = new Tine.Calendar.EventSelectionModel();
        }

        if (Ext.isFunction(this.unbufferedOnLayout)) {
            this.onLayout = Function.createBuffered(this.unbufferedOnLayout, 100, this);
        }

        Tine.Calendar.AbstractView.superclass.initComponent.apply(this, arguments);
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
     * fill the events into the view
     */
    afterRender: function() {
        Tine.Calendar.AbstractView.superclass.afterRender.apply(this, arguments);

        this.mon(this.el, 'click', this.onClick, this);
        this.mon(this.el, 'dblclick', this.onDblClick, this);
        this.mon(this.el, 'contextmenu', this.onContextMenu, this);
        this.mon(this.el, 'keydown', this.onKeyDown, this);

        this.getSelectionModel().init(this);
    },

    onBeforeLoad: function(store, options) {
        if (options.autoRefresh && this.editing) {
            return false;
        }
        if (! options.refresh) {
            this.removeAllEvents();
        }
    },

    /**
     * @private
     */
    onLoad : function() {
        if(! this.rendered){
            return;
        }

        // remove old events
        this.clearAll();

        this.initParallelEventRegistry();

        this.store.fields = Tine.Calendar.Model.Event.prototype.fields;
        this.store.sortInfo = {field: 'dtstart', direction: 'ASC'};
        this.store.applySort();

        this.store.each(function(event) {
            this.getParallelEventRegistry(event).register(event);
        }, this);

        // put the events in
        this.store.each(this.insertEvent, this);

        this.onLayout();
    },

    /**
     * @private
     */
    onAdd : function(ds, records, index) {
        for (var i=0; i<records.length; i++) {
            var event = records[i];

            this.getParallelEventRegistry(event).register(event);

            var parallelEvents = this.getParallelEventRegistry(event).getEvents(event.get('dtstart'), event.get('dtend'));

            for (var j=0; j<parallelEvents.length; j++) {
                this.removeEvent(parallelEvents[j]);
                this.insertEvent(parallelEvents[j]);
            }
        }

        this.onLayout();
    },

    /**
     * @private
     */
    onUpdate : function(ds, event) {
        // don't update events while being created
        if (event.get('id').match(/new/)) {
            return;
        }

        var originalRegistry = this.getParallelEventRegistry(event, true);
        var originalDtstart = event.modified.hasOwnProperty('dtstart') ? event.modified.dtstart : event.get('dtstart');
        var originalDtend = event.modified.hasOwnProperty('dtend') ? event.modified.dtend : event.get('dtend');

        var originalParallels = originalRegistry.getEvents(originalDtstart, originalDtend);
        for (var j=0; j<originalParallels.length; j++) {
            this.removeEvent(originalParallels[j]);
        }
        originalRegistry.unregister(event);

        var originalParallels = originalRegistry.getEvents(originalDtstart, originalDtend);
        for (var j=0; j<originalParallels.length; j++) {
            this.insertEvent(originalParallels[j]);
        }

        // relayout actual context
        var registry = this.getParallelEventRegistry(event);
        var parallelEvents = registry.getEvents(event.get('dtstart'), event.get('dtend'));
        for (var j=0; j<parallelEvents.length; j++) {
            this.removeEvent(parallelEvents[j]);
        }

        registry.register(event);
        var parallelEvents = registry.getEvents(event.get('dtstart'), event.get('dtend'));
        for (var j=0; j<parallelEvents.length; j++) {
            this.insertEvent(parallelEvents[j]);
        }

        this.onLayout();
    },

    /**
     * @private
     */
    onRemove : function(ds, event, index, isUpdate) {
        if (!event || index == -1) {
            return;
        }

        this.removeEvent(event);
        var registry = this.getParallelEventRegistry(event);
        var parallelEvents = registry.getEvents(event.get('dtstart'), event.get('dtend'));
        for (var j=0; j<parallelEvents.length; j++) {
            this.removeEvent(parallelEvents[j]);
        }

        this.getParallelEventRegistry(event).unregister(event);

        var parallelEvents = registry.getEvents(event.get('dtstart'), event.get('dtend'));
        for (var j=0; j<parallelEvents.length; j++) {
            this.insertEvent(parallelEvents[j]);
        }

        this.onLayout();
    },

    getParallelEventRegistry: function(event, original) {
        return this.parallelEventRegistry;
    },

    initParallelEventRegistry: function(event) {
        this.parallelEventRegistry = new Tine.Calendar.ParallelEventsRegistry({dtStart: this.period.from, dtEnd: this.period.until});
    },

    /**
     * removes all events from store
     */
    removeAllEvents: function() {
        this.store.each(function(event) {
            if (event.ui) {
                event.ui.remove();
            }
        });
    },

    /**
     * remove all events from dom
     */
    clearAll: function() {
        var els = this.el.query('.' + this.eventCls);
        Ext.each(els, function(el) {
            el.remove();
        });
    },

    /**
     * removes a event from the dom
     * @param {Tine.Calendar.Model.Event} event
     */
    removeEvent: function(event) {
        if(this.editing == event) {
            this.abortCreateEvent(event);
        }

        if (event.ui) {
            event.ui.remove();
        }

        this.onLayout();
    },

    replaceEvent: function(event) {
        this.removeEvent(event);
        this.insertEvent(event);
    },

    onClick: function(e) {
        var event = this.getTargetEvent(e);
        if (event) {
            this.fireEvent('click', event, e);
        }
    },

    onDblClick: function(e, target) {
        e.stopEvent();
        var event = this.getTargetEvent(e);

        if (event) {
            this.fireEvent('dblclick', event, e);
        }
    },

    onContextMenu: function(e) {
        this.fireEvent('contextmenu', e);
    },

    onKeyDown : function(e){
        this.fireEvent("keydown", e);
    },

    /**
     * gets event el of target
     *
     * @param {Ext.EventObject} e
     * @return {Tine.Calendar.Model.Event}
     */
    getTargetEvent: function(e) {
        var target = e.getTarget();
        var el = Ext.fly(target);

        if (el.hasClass(this.eventCls) || (el = el.up('[id*=event:]', 10))) {
            var parts = el.dom.id.split(':');
            parts.shift();

            return this.store.getById(parts.join(':'));
        }
    },

    /**
     * returns the selectionModel of the active panel
     * @return {}
     */
    getSelectionModel: function() {
        return this.selModel;
    },

    hex2dec: function(hex) {
        var dec = 0;
        hex = hex.toString();
        var length = hex.length, multiplier, digit;
        for (var i=0; i<length; i++) {

            multiplier = Math.pow(16, (Math.abs(i - hex.length)-1));
            digit = parseInt(hex.toString().charAt([i]), 10);
            if (isNaN(digit)) {
                switch (hex.toString().charAt([i]).toUpperCase()) {
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

    beforeDestroy: function() {
        this.removeAllEvents();
        this.initData(false);
        this.purgeListeners();

        Tine.Calendar.AbstractView.superclass.beforeDestroy.apply(this, arguments);
    }
});
