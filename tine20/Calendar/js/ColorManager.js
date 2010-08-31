/* 
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */
 
Ext.ns('Tine.Calendar');

/**
 * @namespace Tine.Calendar
 * @class Tine.Calendar.ColorManager
 * @extends Ext.util.Observable
 * Colormanager for Coloring Calendar Events <br>
 * 
 * @constructor
 * Creates a new color manager
 * @param {Object} config
 * 
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */
Tine.Calendar.ColorManager = function(config) {
    Ext.apply(this, config);
    
    this.colorMap = {};
    
    // allthough we don't extend component as we have nothing to render, we borrow quite some stuff from it
    this.id = this.stateId;
    Ext.ComponentMgr.register(this);
    
    this.addEvents(
        /**
         * @event beforestaterestore
         * Fires before the state of this colormanager is restored. Return false to stop the restore.
         * @param {Tine.Calendar.ColorManager} this
         * @param {Object} state The hash of state values
         */
        'beforestaterestore',
        /**
         * @event staterestore
         * Fires after the state of tthis colormanager is restored.
         * @param {Tine.Calendar.ColorManager} this
         * @param {Object} state The hash of state values
         */
        'staterestore',
        /**
         * @event beforestatesave
         * Fires before the state of this colormanager is saved to the configured state provider. Return false to stop the save.
         * @param {Tine.Calendar.ColorManager} this
         * @param {Object} state The hash of state values
         */
        'beforestatesave',
        /**
         * @event statesave
         * Fires after the state of this colormanager is saved to the configured state provider.
         * @param {Tine.Calendar.ColorManager} this
         * @param {Object} state The hash of state values
         */
        'statesave'
    );
    
    if (this.stateful) {
        this.initState();
    }
   
};

Ext.extend(Tine.Calendar.ColorManager, Ext.util.Observable, {
    /**
     * @cfg {String} schemaName
     * Name of color schema to use
     */
    schemaName: 'standard',
    
    /**
     * @cfg {String} stateId
     * State id to use
     */
    stateId: 'cal-color-mgr-containers',
    
    /**
     * @cfg {Boolean} stateful
     * Is this component statefull?
     */
    stateful: false,
    
    /**
     * current color map 
     * 
     * @type Object 
     * @propertycolorMap
     */
    colorMap: null,
    
    /**
     * pointer to current color set in color schema 
     * 
     * @type Number 
     * @property colorSchemataPointer
     */
    colorSchemataPointer: 0,
    
    /**
     * gray color set
     * 
     * @type Object 
     * @property gray
     */
    gray: {color: '#808080', light: '#EDEDED', text: '#FFFFFF', lightText: '#FFFFFF'},
    
    /**
     * color palette from Ext.ColorPalette
     * 
     * @type Array
     * @property colorPalette
     */
    colorPalette: Ext.ColorPalette.prototype.colors,
    
    /**
     * color sets for colors from colorPalette
     * 
     * @type Array 
     * @property colorSchemata
     */
    colorSchemata : {
        "000000" : {color: '#000000', light: '#8F8F8F', text: '#FFFFFF', lightText: '#FFFFFF'},
        "993300" : {color: '#993300', light: '#CEA590', text: '#FFFFFF', lightText: '#FFFFFF'},
        "333300" : {color: '#333300', light: '#A6A691', text: '#FFFFFF', lightText: '#FFFFFF'}, 
        "003300" : {color: '#003300', light: '#8FA48F', text: '#FFFFFF', lightText: '#FFFFFF'},
        "003366" : {color: '#003366', light: '#90A5B9', text: '#FFFFFF', lightText: '#FFFFFF'},
        "000080" : {color: '#000080', light: '#9090C4', text: '#FFFFFF', lightText: '#FFFFFF'},
        "333399" : {color: '#333399', light: '#A5A5CE', text: '#FFFFFF', lightText: '#FFFFFF'},
        "333333" : {color: '#333333', light: '#A6A6A6', text: '#FFFFFF', lightText: '#FFFFFF'},
        
        "800000" : {color: '#800000', light: '#C79393', text: '#FFFFFF', lightText: '#FFFFFF'},
        "FF6600" : {color: '#FF6600', light: '#F8BB92', text: '#FFFFFF', lightText: '#FFFFFF'}, // orange
        "808000" : {color: '#808000', light: '#C6C692', text: '#FFFFFF', lightText: '#FFFFFF'},
        "008000" : {color: '#008000', light: '#92C692', text: '#FFFFFF', lightText: '#FFFFFF'},
        "008080" : {color: '#008080', light: '#91C5C5', text: '#FFFFFF', lightText: '#FFFFFF'},
        "0000FF" : {color: '#0000FF', light: '#9292F8', text: '#FFFFFF', lightText: '#FFFFFF'},
        "666699" : {color: '#666699', light: '#BBBBD0', text: '#FFFFFF', lightText: '#FFFFFF'},
        "808080" : {color: '#808080', light: '#C6C6C6', text: '#FFFFFF', lightText: '#FFFFFF'},
        "FF0000" : {color: '#FF0000', light: '#F89292', text: '#FFFFFF', lightText: '#FFFFFF'}, // red
        "FF9900" : {color: '#FF9900', light: '#F8D092', text: '#FFFFFF', lightText: '#FFFFFF'},
        "99CC00" : {color: '#99CC00', light: '#D0E492', text: '#FFFFFF', lightText: '#FFFFFF'},
        "339966" : {color: '#339966', light: '#A7D0BB', text: '#FFFFFF', lightText: '#FFFFFF'},
        "33CCCC" : {color: '#33CCCC', light: '#A8E5E5', text: '#FFFFFF', lightText: '#FFFFFF'},
        "3366FF" : {color: '#3366FF', light: '#A7BBF8', text: '#FFFFFF', lightText: '#FFFFFF'}, // blue
        "800080" : {color: '#800080', light: '#C692C6', text: '#FFFFFF', lightText: '#FFFFFF'},
        "969696" : {color: '#969696', light: '#CECECE', text: '#FFFFFF', lightText: '#FFFFFF'},
        "FF00FF" : {color: '#FF00FF', light: '#F690F6', text: '#FFFFFF', lightText: '#FFFFFF'}, // purple
        "FFCC00" : {color: '#FFCC00', light: '#F7E391', text: '#FFFFFF', lightText: '#FFFFFF'},
        "FFFF00" : {color: '#FFFF00', light: '#F7F791', text: '#000000', lightText: '#000000'},
        "00FF00" : {color: '#00FF00', light: '#93F993', text: '#000000', lightText: '#000000'}, // green
        "00FFFF" : {color: '#00FFFF', light: '#93F9F9', text: '#000000', lightText: '#000000'},
        "00CCFF" : {color: '#00CCFF', light: '#93E5F9', text: '#FFFFFF', lightText: '#FFFFFF'},
        "993366" : {color: '#993366', light: '#D1A8BC', text: '#FFFFFF', lightText: '#FFFFFF'}, // violet
        "C0C0C0" : {color: '#C0C0C0', light: '#DFDFDF', text: '#FFFFFF', lightText: '#FFFFFF'},
        "FF99CC" : {color: '#FF99CC', light: '#F8F0E4', text: '#FFFFFF', lightText: '#FFFFFF'},
        "FFCC99" : {color: '#FFCC99', light: '#F8E4D0', text: '#000000', lightText: '#000000'},
        "FFFF99" : {color: '#FFFF99', light: '#F9F9D1', text: '#000000', lightText: '#000000'},
        "CCFFCC" : {color: '#CCFFCC', light: '#E5F9E5', text: '#000000', lightText: '#000000'},
        "CCFFFF" : {color: '#CCFFFF', light: '#E5F9F9', text: '#000000', lightText: '#000000'},
        "99CCFF" : {color: '#99CCFF', light: '#D0E4F8', text: '#000000', lightText: '#000000'},
        "CC99FF" : {color: '#CC99FF', light: '#E5D1F9', text: '#000000', lightText: '#000000'},
        "FFFFFF" : {color: '#DFDFDF', light: '#F8F8F8', text: '#000000', lightText: '#000000'}
    },
    
    /**
     * hack for container only support
     * 
     * @param {Tine.Calendar.Model.Evnet} event
     * @return {Object} colorset
     */
    getColor: function(event) {
        var container = null;
        
        if (typeof event.get != 'function') {
            // tree comes with containers only
            container = event;
        } else {
            container = event.get('container_id');
            if (! container || !container.type || container.type != 'shared') {
                container = event.getDisplayContainer();
            }
        }
        
        if (! container.color) {
            return this.gray;
        }
        
        return this.colorSchemata[container.color.replace('#', '')];
        //var container_id = container.id ? container.id : container;
        //return container ? this.getColorSchema(container_id) : this.gray;
    }
    
//    /**
//     * gets the next free color set
//     * 
//     * @param {String} item e.g. a calendar id
//     * @return {Object} colorset
//     */
//    getColorSchema: function(item) {
//        if (this.colorMap[item]) {
//            return this.colorSchemata[this.colorMap[item]];
//        }
//        
//        // find a 'free' schema
//        for (var i=1,cpi; i<=this.colorPalette.length; i++) {
//            // color palette index
//            cpi = (i+this.colorSchemataPointer) % this.colorPalette.length;
//            if (this.colorSchemata[cpi].color && !this.inUse(this.colorPalette[cpi])) {
//                this.colorSchemataPointer = cpi;
//                this.colorMap[item] = this.colorSchemataPointer;
//                this.saveState();
//                //console.log('assigned color ' + this.colorMap[item] + ' to item ' + item);
//                
//                return this.colorSchemata[this.colorSchemataPointer];
//            }
//        }
//
//        // no more free colors ;-(
//        this.colorSchemataPointer++;
//        this.colorMap[item] = this.colorSchemataPointer;
//        return this.colorSchemata[this.colorSchemataPointer];
//    },
//    
//    /**
//     * checkes if given color is already in use
//     * 
//     * @param {String} color
//     * @return {Boolean}
//     */
//    inUse: function(color) {
//        for (var item in this.colorMap) {
//            if (this.colorMap.hasOwnProperty(item) && this.colorMap[item] == color) {
//                //console.log(color + ' is already used');
//                return true;
//            }
//        }
//        //console.log(color + 'is not in use yet');
//        return false;
//    },
//    
//    /* state handling */
//    initState:       Ext.Component.prototype.initState,
//    getStateId:      Ext.Component.prototype.getStateId,
//    //initStateEvents: Ext.Component.prototype.initState,
//    applyState:      Ext.Component.prototype.applyState,
//    saveState:       Ext.Component.prototype.saveState,
//    getState:        function() {
//        return {
//            colorMap            : this.colorMap,
//            colorSchemataPointer: this.colorSchemataPointer
//        };
//    }
    
    
    
});
