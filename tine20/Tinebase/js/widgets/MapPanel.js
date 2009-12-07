/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
 
Ext.namespace('Tine.widgets');

/**
 * Widget to display maps
 */
Tine.widgets.MapPanel = Ext.extend(GeoExt.MapPanel, {
	zoom: 4,
	map: null,
	layers: null,
	
    /**
     * @private
     */
    initComponent: function() {
        this.map =  new OpenLayers.Map();
        this.layers = [new OpenLayers.Layer.OSM()];
        
		if(this.center instanceof Array) {
	        this.center = new OpenLayers.LonLat(this.center[0], this.center[1]).transform(new OpenLayers.Projection("EPSG:4326"), new OpenLayers.Projection("EPSG:900913"));
	    }

        Tine.widgets.MapPanel.superclass.initComponent.call(this);   
    },
    
    setCenter: function(lon, lat) {
    	this.center = new OpenLayers.LonLat(lon, lat).transform(new OpenLayers.Projection("EPSG:4326"), this.map.getProjectionObject());
    	this.map.setCenter(this.center);
    }
});

Ext.reg('widget-mappanel', Tine.widgets.MapPanel);