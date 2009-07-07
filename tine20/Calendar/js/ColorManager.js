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
 * Manages coloring for calendar
 * 
 * @class Tine.Calendar.ColorManager
 * @constructor
 * @param {Object} config
 */
Tine.Calendar.ColorManager = function(config) {
   Ext.apply(this, config);
   
   this.colorMap = {};
   
   
};

Tine.Calendar.ColorManager.prototype = {
    /**
     * @cfg {String} schemaName
     */
    schemaName: 'standard',
    
    /**
     * @property {Object} colorMap
     */
    colorMap: null,
    
    colorSchemataPointer: 0,
    
    /**
     * @property {Object} gray
     */
    gray: {color: '#808080', light: '#EDEDED', text: '#FFFFFF', lightText: '#FFFFFF'},
    
    /**
     * @property {Array} colorPalette
     */
    colorPalette: Ext.ColorPalette.prototype.colors,
    
    /**
     * $property {Array} colorSchemata
     * color palette from Ext.ColorPalette
     */
    colorSchemata : [
        /*"000000" :*/ {},
        /*"993300" :*/ {},
        /*"333300" :*/ {}, 
        /*"003300" :*/ {},
        /*"003366" :*/ {},
        /*"000080" :*/ {},
        /*"333399" :*/ {},
        /*"333333" :*/ {},
        /*"800000" :*/ {},
        /*"FF6600" :*/ {color: '#FF7200', light: '#FFB87F', text: '#FFFFFF', lightText: '#FFFFFF'}, // orange
        /*"808000" :*/ {},
        /*"008000" :*/ {},
        /*"008080" :*/ {},
        /*"0000FF" :*/ {},
        /*"666699" :*/ {},
        /*"808080" :*/ {},
        /*"FF0000" :*/ {color: '#FD0000', light: '#FE7F7F', text: '#FFFFFF', lightText: '#FFFFFF'}, // red
        /*"FF9900" :*/ {},
        /*"99CC00" :*/ {},
        /*"339966" :*/ {},
        /*"33CCCC" :*/ {},
        /*"3366FF" :*/ {color: '#0050D9', light: '#7FA7EC', text: '#FFFFFF', lightText: '#FFFFFF'}, // blue
        /*"800080" :*/ {},
        /*"969696" :*/ {},
        /*"FF00FF" :*/ {color: '#C302B1', light: '#E080D7', text: '#FFFFFF', lightText: '#FFFFFF'}, // purple
        /*"FFCC00" :*/ {},
        /*"FFFF00" :*/ {},
        /*"00FF00" :*/ {color: '#00A700', light: '#7FD27F', text: '#FFFFFF', lightText: '#FFFFFF'}, // green
        /*"00FFFF" :*/ {},
        /*"00CCFF" :*/ {},
        /*"993366" :*/ {color: '#5123A5', light: '#A790D1', text: '#FFFFFF', lightText: '#FFFFFF'}, // violet
        /*"C0C0C0" :*/ {},
        /*"FF99CC" :*/ {},
        /*"FFCC99" :*/ {},
        /*"FFFF99" :*/ {},
        /*"CCFFCC" :*/ {},
        /*"CCFFFF" :*/ {},
        /*"99CCFF" :*/ {},
        /*"CC99FF" :*/ {},
        /*"FFFFFF" :*/ {}
    ],
    
    getColor: function(event) {
        // hack for container only support
        var container = typeof event.get == 'function' ? event.get('container_id') : event;
        var container_id = container.id ? container.id : container;
        
        return this.getColorSchema(container_id);
    },
    
    getColorSchema: function(item) {
        if (this.colorMap[item]) {
            return this.colorSchemata[this.colorMap[item]];
        }
        
        // find a 'free' schema
        for (var i=1,cpi; i<=this.colorPalette.length; i++) {
            // color palette index
            cpi = (i+this.colorSchemataPointer) % this.colorPalette.length;
            if (this.colorSchemata[cpi].color && !this.inUse(this.colorPalette[cpi])) {
                this.colorSchemataPointer = cpi;
                this.colorMap[item] = this.colorSchemataPointer;
                //console.log('assigned color ' + this.colorMap[item] + ' to item ' + item);
                
                return this.colorSchemata[this.colorSchemataPointer];
                
            }
        }

        // no more free colors ;-(
        this.colorSchemataPointer++;
        this.colorMap[item] = this.colorSchemataPointer;
        return this.colorSchemata[this.colorSchemataPointer];
    },
    
    inUse: function(color) {
        for (var item in this.colorMap) {
            if (this.colorMap.hasOwnProperty(item) && this.colorMap[item] == color) {
                //console.log(color + ' is already used');
                return true;
            }
        }
        //console.log(color + 'is not in use yet');
        return false;
    }
    
};
 