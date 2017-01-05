/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.ns('Tine.widgets');

/**
 * Widget to display maps
 *
 * @namespace   Tine.widgets
 * @class       Tine.widgets.MapPanel
 * @extends     GeoExt.MapPanel
 */
Tine.widgets.MapPanel = Ext.extend(Ext.Panel, {
    initComponent: function() {

        Tine.widgets.MapPanel.superclass.initComponent.call(this);
    },

    afterRender: function() {
        var me = this;
        require.ensure(["../../../library/OpenLayers/OpenLayers", "../../../library/GeoExt/script/GeoExt"], function() {
            require("../../../library/OpenLayers/OpenLayers");
            require("../../../library/GeoExt/script/GeoExt");

            // fix OpenLayers script location to find images/themes/...
            OpenLayers._getScriptLocation = function () {
                return 'library/OpenLayers/';
            };

            me.geoExtPanel = new GeoExt.MapPanel({
                zoom: me.zoom || 4,
                map:  new OpenLayers.Map(),
                layers: [
                    new OpenLayers.Layer.OSM()
                ]
            });

            me.add(me.geoExtPanel);
            me.fireEvent('mapAdded', me);

        }, 'Tinebase/js/OpenLayers');

        Tine.widgets.MapPanel.superclass.afterRender.call(this);
    },

    setCenter: function(lon, lat) {
        this.geoExtPanel.center = new OpenLayers.LonLat(lon, lat).transform(new OpenLayers.Projection("EPSG:4326"), this.geoExtPanel.map.getProjectionObject());
        this.geoExtPanel.map.setCenter(this.center);

        // add a marker
        var size = new OpenLayers.Size(32,32);
        var offset = new OpenLayers.Pixel(0, -size.h);
        var icon = new OpenLayers.Icon('images/oxygen/32x32/actions/flag-red.png', size, offset);

        var markers = new OpenLayers.Layer.Markers( "Markers" );
        markers.addMarker(new OpenLayers.Marker(this.geoExtPanel.center, icon));
        this.geoExtPanel.map.addLayer(markers);
    },

    beforeDestroy: function() {
        delete this.geoExtPanel.map;
        this.supr().beforeDestroy.apply(this, arguments);
    }
});

Ext.reg('widget-mappanel', Tine.widgets.MapPanel);
