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
 * @class Tine.Calendar.EventSelectionModel
 * @extends Ext.util.Observable
 * Selection model for a calendar views.
 */
Tine.Calendar.EventSelectionModel = function(config){
    this.selEvents = [];
    this.selMap = {};
    
    this.addEvents(
       /**
        * @event selectionchange
        * Fires when the selected event change
        * @param {EventSelectionModel} this
        * @param {Array} events Array of the selected events
        */
       "selectionchange"
    );

    Ext.apply(this, config);
    Tine.Calendar.EventSelectionModel.superclass.constructor.call(this);
};

Ext.extend(Tine.Calendar.EventSelectionModel, Ext.util.Observable, {
    init : function(view){
        this.view = view;
        view.el.on("keydown", this.onKeyDown, this);
        view.on("click", this.onEventClick, this);
    },

    onEventClick : function(event, e){
        this.select(event, e, e.ctrlKey);
    },

    /**
     * Select a event.
     * @param {Tine.Calendar.Event} event The event to select
     * @param {EventObject} e (optional) An event associated with the selection
     * @param {Boolean} keepExisting True to retain existing selections
     * @return {Tine.Calendar.Event} The selected event
     */
    select : function(event, e, keepExisting){
        if(keepExisting !== true){
            this.clearSelections(true);
        }
        if(this.isSelected(event)){
            this.lastSelEvent = event;
            return event;
        }
        this.selEvents.push(event);
        this.selMap[event.id] = event;
        this.lastSelEvent = event;
        event.ui.onSelectedChange(true);
        this.fireEvent("selectionchange", this, this.selEvents);
        return event;
    },

    /**
     * Deselect a event.
     * @param {Tine.Calendar.Event} event The event to unselect
     */
    unselect : function(event){
        if(this.selMap[event.id]){
            event.ui.onSelectedChange(false);
            var sn = this.selEvents;
            var index = sn.indexOf(event);
            if(index != -1){
                this.selEvents.splice(index, 1);
            }
            delete this.selMap[event.id];
            this.fireEvent("selectionchange", this, this.selEvents);
        }
    },

    /**
     * Clear all selections
     */
    clearSelections : function(suppressEvent){
        var sn = this.selEvents;
        if(sn.length > 0){
            for(var i = 0, len = sn.length; i < len; i++){
                sn[i].ui.onSelectedChange(false);
            }
            this.selEvents = [];
            this.selMap = {};
            if(suppressEvent !== true){
                this.fireEvent("selectionchange", this, this.selEvents);
            }
        }
    },

    /**
     * Returns true if the event is selected
     * @param {Tine.Calendar.Event} event The event to check
     * @return {Boolean}
     */
    isSelected : function(event){
        return this.selMap[event.id] ? true : false;  
    },

    /**
     * Returns an array of the selected events
     * @return {Array}
     */
    getSelectedEvents : function(){
        return this.selEvents;    
    },

    onKeyDown : Ext.emptyFn,//Ext.view.DefaultSelectionModel.prototype.onKeyDown,
    selectNext : Ext.emptyFn,//Ext.view.DefaultSelectionModel.prototype.selectNext,
    selectPrevious : Ext.emptyFn//Ext.view.DefaultSelectionModel.prototype.selectPrevious
});
