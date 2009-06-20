/* 
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */
 
Ext.ns('Tine.Calendar');
 
Tine.Calendar.ColorManager = function(config) {
   Ext.apply(this, config);
   
   this.colorMap = {};
   
   
};

Tine.Calendar.ColorManager.prototype = {
    /**
     * @property {Object} colorMap
     */
    colorMap: null,
    
    colorMapPointer: 0,
    
    colorSchema: [
        {color: '#00A700', light: '#7FD27F', text: '#FFFFFF', lightText: '#FFFFFF'}, // green
        {color: '#0050D9', light: '#7FA7EC', text: '#FFFFFF', lightText: '#FFFFFF'}, // blue
        {color: '#FD0000', light: '#FE7F7F', text: '#FFFFFF', lightText: '#FFFFFF'}, // red
        {color: '#FF7200', light: '#FFB87F', text: '#FFFFFF', lightText: '#FFFFFF'}, // orange
        {color: '#C302B1', light: '#E080D7', text: '#FFFFFF', lightText: '#FFFFFF'}, // purple
        {color: '#5123A5', light: '#A790D1', text: '#FFFFFF', lightText: '#FFFFFF'}, // violet
        {color: '#808080', light: '#EDEDED', text: '#FFFFFF', lightText: '#FFFFFF'}  // gray
    ],
    
    getColor: function(event) {
        var container = event.get('container_id');
        var container_id = container.id ? container.id : container;
        
        if (! this.colorMap.hasOwnProperty(container_id)) {
            this.colorMap[container_id] = this.colorMapPointer;
            this.colorMapPointer = (this.colorMapPointer+1) % this.colorSchema.length;
        }
        
        
        return this.colorSchema[this.colorMap[container_id]];
    }
};
 