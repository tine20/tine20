/* 
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */

Ext.ns('Tine.Calendar');

/**
 * registry to cope with parallel events
 * 
 * @class Tine.Events.ParallelEventsRegistry
 * @constructor
 */
Tine.Calendar.ParallelEventsRegistry = function(config) {
    Ext.apply(this, config);
    
    this.dtStartTs = this.dtStart.getTime();
    this.dtEndTs = this.dtEnd.getTime();
    this.dt = this.granularity * Date.msMINUTE;
    
    // init map
    this.frameLength = Math.ceil((this.dtEndTs - this.dtStartTs) / this.dt);
    this.map = [];
}

Tine.Calendar.ParallelEventsRegistry.prototype = {
    /**
     * @cfg {Date} dtStart
     * start of range for this registry
     */
    dtStart: null,
    /**
     * @cfg {Date} dtEnd
     * end of range for this registry 
     */
    dtEnd: null,
    /**
     * @cfg {Number} granularity
     * granularity of this registry in minutes
     */
    granularity: 5,
    /**
     * @cfg {String} dtStartProperty
     */
    dtStartProperty: 'dtstart',
    /**
     * @cfg {String} dtEndProperty
     */
    dtEndProperty: 'dtend',
    
    /**
     * @private {Array} map
     * 
     * array of frames. a frames
     */
    map: null,
    /**
     * @private {Number} dtStartTs
     */
    dtStartTs: null,
    /**
     * @private {Number} dtEndTs
     */
    dtEndTs: null,
    /**
     * @private {Number} dt
     */
    dt: null,
    
    
    
    /**
     * register event
     * @param {Ext.data.Record} event
     * @param {bool} returnAffected
     * @return mixed
     */
    register: function(event, returnAffected) {
        var dtStart = event.get(this.dtStartProperty);
        var dtStartTs = dtStart.getTime();
        var dtEnd = event.get(this.dtEndProperty);
        var dtEndTs = dtEnd.getTime() - 1000;
        
        // layout helper
        event.duration = dtEndTs - dtStartTs;
        
        var startIdx = this.tsToIdx(dtStart);
        var endIdx = this.tsToIdx(dtEndTs);
        
        var position = 0;
        var frame = this.getFrame(position);
        while (! this.isEmptySlice(frame, startIdx, endIdx)) frame = this.getFrame(++position);
        
        this.registerSlice(frame, startIdx, endIdx, event);
        
        event.parallelEventRegistry = {
            registry: this,
            position: position,
            startIdx: startIdx,
            endIdx: endIdx
        };
        
        //console.info('pushed event in frame# ' + position + ' from startIdx"' + startIdx + '" to endIdx "' + endIdx + '".');
        
        if (returnAffected) {
            return this.getEvents(dtStart, dtEnd);
        }
    },
    
    /**
     * unregister event
     * 
     * @param {Ext.data.Record} event
     */
    unregister: function(event) {
        var ri =  event.parallelEventRegistry;
        
        if (! ri) {
            // cannot unregister unregistered events
        }
        
        var frame = this.getFrame(ri.position);
        
        if (! this.skipIntegrityChecks) {
            for (var idx=ri.startIdx; idx<=ri.endIdx; idx++) {
                if (frame[idx] !== event) {
                    throw new Ext.Error('event is not registered at expected position');
                }
            }
        }
        
        this.unregisterSlice(frame, ri.startIdx, ri.endIdx);
        event.parallelEventRegistry = null;
    },
    
    /**
     * returns events of current range sorted by duration
     * 
     * @param  {Date} dtStart
     * @param  {Date} dtEnd
     * @return {Array}
     */
    getEvents: function(dtStart, dtEnd, sortByDtStart) {
        var dtStartTs = dtStart.getTime();
        var dtEndTs = dtEnd.getTime() - 1000;
        
        var startIdx = this.tsToIdx(dtStart);
        var endIdx = this.tsToIdx(dtEndTs);
        //console.info('get events from startIdx"' + startIdx + '" to endIdx "' + endIdx + '".'   )
        
        return this.getEventsFromIdx(startIdx, endIdx, sortByDtStart);
    },
    
    /**
     * @private
     * @param  {Number} startIdx
     * @param  {Number} endIdx
     * @return {Array}
     */
    getEventsFromIdx: function(startIdx, endIdx, sortByDtStart) {
        var events = [];
        Ext.each(this.map, function(frame) {
            for (var idx=startIdx; idx<=endIdx; idx++) {
                if (frame[idx] && events.indexOf(frame[idx]) === -1) {
                    events.push(frame[idx]);
                }
            }
        }, this);
        
        var parallels = events.length;
        /*
        var parallels = 1;
        for (var i=startIdx; i<=endIdx; i++) {
            if (Ext.isArray(this.map[i])) {
                parallels = this.map[i].length > parallels ? this.map[i].length : parallels;
                for (var j=0; j<this.map[i].length; j++) {
                    if (events.indexOf(this.map[i][j]) === -1) {
                        events.push(this.map[i][j]);
                    }
                }
            }
        }
        */
        
        // sort by duration and dtstart
        var scope = this;
        events.sort(function(a, b) {
            var d = b.duration - a.duration;
            var s = a.get(scope.dtStartProperty).getTime() - b.get(scope.dtStartProperty).getTime();
            
            return sortByDtStart ? 
                s ? s : d:
                d ? d : s;
        });
        
        // layout helper
        for (var i=0; i<events.length; i++) {
            events[i].parallels = parallels;
        }
        
        return events;
    },
    
    /*************************************** frame functions **********************************/
    
    /**
     * returns frame of given position. 
     * If no frame is found on given position it will be created implicitlty
     * 
     * @private
     * @param {Number} position
     * @return {Array}
     */
    getFrame: function(position) {
        if (position > this.map.length +1) {
            throw new Ext.Error('skipping frames is not allowed');
        }
        
        if (! Ext.isArray(this.map[position])) {
            this.map[position] = new Array(this.frameLength);
        }
        
        return this.map[position];
    },
    
    /**
     * checks if a slice in a given frame is free
     * 
     * @private
     * @param {Array} frame
     * @param {Number} startIdx
     * @param {Number} endIdx
     */
    isEmptySlice: function(frame, startIdx, endIdx) {
        for (var idx=startIdx; idx<=endIdx; idx++) {
            if (frame[idx]) {
                return false;
            }
        }
        return true;
    },
    
    /**
     * registers evnet in given frame for given slice
     * 
     * @private
     * @param {Array} frame
     * @param {Number} startIdx
     * @param {Number} endIdx
     * @param {Ext.data.Record} event
     * @return this
     */
    registerSlice: function(frame, startIdx, endIdx, event) {
        for (var idx=startIdx; idx<=endIdx; idx++) {
            frame[idx] = event;
        }
    },
    
    /**
     * @private
     * @param  {Number} ts
     * @return {Number}
     */
    tsToIdx: function(ts) {
        return Math.floor((ts - this.dtStartTs) / this.dt);
    },
    
    /**
     * registers evnet in given frame for given slice 
     * 
     * @private
     * @param {Array} frame
     * @param {Number} startIdx
     * @param {Number} endIdx
     * @return this
     */
    unregisterSlice: function(frame, startIdx, endIdx) {
        for (var idx=startIdx; idx<=endIdx; idx++) {
            frame[idx] = null;
        }
        
        return this;
    }
};