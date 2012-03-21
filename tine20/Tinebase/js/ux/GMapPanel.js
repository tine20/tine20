/**
 * Tine 2.0
 * 
 * @package     Ext
 * @subpackage  ux
 * @license     BSD
 * @author      Shea Frederick
 * @copyright   Copyright (c) 2008 Shea Frederick
 *
 * based on public domain code from Shea Frederick
 * "Free as a bird. Use it anywhere or any way you like."
 * 
 * http://extjs.com/blog/2008/07/01/integrating-google-maps-api-with-extjs/
 */
Ext.ns('Ext.ux');
 
/**
 * @namespace   Ext.ux
 * @class       Ext.ux.GMapPanel
 * @extends     Ext.Panel
 */
Ext.ux.GMapPanel = Ext.extend(Ext.Panel, {
    initComponent : function(){
        
        var defConfig = {
            plain: true,
            zoomLevel: 3,
            yaw: 180,
            pitch: 0,
            zoom: 0,
            gmapType: 'map',
            border: false
        };
        
        Ext.applyIf(this,defConfig);
        
        Ext.ux.GMapPanel.superclass.initComponent.call(this);

    },
    afterRender : function(){
        
        var wh = this.ownerCt.getSize();
        Ext.applyIf(this, wh);
        
        Ext.ux.GMapPanel.superclass.afterRender.call(this);
        
        if (this.gmapType === 'map'){
            this.gmap = new GMap2(this.body.dom);
        }
        
        if (this.gmapType === 'panorama'){
            this.gmap = new GStreetviewPanorama(this.body.dom);
        }
        
        if (typeof this.addControl === 'object' && this.gmapType === 'map') {
            this.gmap.addControl(this.addControl);
        }
        
        if (typeof this.setCenter === 'object') {
            if (typeof this.setCenter.geoCodeAddr === 'string'){
                this.geoCodeLookup(this.setCenter.geoCodeAddr);
            }else{
                if (this.gmapType === 'map'){
                    var point = new GLatLng(this.setCenter.lat,this.setCenter['long']);
                    this.gmap.setCenter(point, this.zoomLevel);
                }
                if (typeof this.setCenter.marker === 'object' && typeof point === 'object'){
                    this.addMarker(point,this.setCenter.marker,this.setCenter.marker.clear);
                }
            }
            if (this.gmapType === 'panorama'){
                this.gmap.setLocationAndPOV(new GLatLng(this.setCenter.lat,this.setCenter['long']), {yaw: this.yaw, pitch: this.pitch, zoom: this.zoom});
            }
        }
        
        var dt = new Ext.util.DelayedTask();
        dt.delay(300, function(){
            this.addMarkers(this.markers);
        }, this);

    },
    onResize : function(w, h){

        if (typeof this.gmap == 'object') {
            this.gmap.checkResize();
        }
        
        Ext.ux.GMapPanel.superclass.onResize.call(this, w, h);

    },
    setSize : function(width, height, animate){
        
        if (typeof this.gmap == 'object') {
            this.gmap.checkResize();
        }
        
        Ext.ux.GMapPanel.superclass.setSize.call(this, width, height, animate);
        
    },
    getMap: function(){
        
        return this.gmap;
        
    },
    addMarkers: function(markers) {
        
        if (Ext.isArray(markers)){
            for (var i = 0; i < markers.length; i++) {
                var mkr_point = new GLatLng(markers[i].lat,markers[i]['long']);
                this.addMarker(mkr_point,markers[i].marker,false,markers[i].setCenter);
            }
        }
        
    },
    addMarker: function(point, marker, clear, center){
        
        Ext.applyIf(marker,G_DEFAULT_ICON);

        if (clear === true){
            this.gmap.clearOverlays();
        }
        if (center === true) {
            this.gmap.setCenter(point, this.zoomLevel);
        }
        
        var mark = new GMarker(point,marker);
        this.gmap.addOverlay(mark);

    },
    geoCodeLookup : function(addr) {
        
        this.geocoder = new GClientGeocoder();
        this.geocoder.getLocations(addr, this.addAddressToMap.createDelegate(this));
        
    },
    addAddressToMap : function(response) {
        
        if (!response || response.Status.code != 200) {
            Ext.MessageBox.alert('Error', 'Code '+response.Status.code+' Error Returned');
        } else {
            place = response.Placemark[0];
            addressinfo = place.AddressDetails;
            accuracy = addressinfo.Accuracy;
            if (accuracy === 0) {
                Ext.MessageBox.alert('Unable to Locate Address', 'Unable to Locate the Address you provided');
            }else{
                if (accuracy < 7) {
                    Ext.MessageBox.alert('Address Accuracy', 'The address provided has a low accuracy.<br><br>Level '+accuracy+' Accuracy (8 = Exact Match, 1 = Vague Match)');
                }else{
                    point = new GLatLng(place.Point.coordinates[1], place.Point.coordinates[0]);
                    if (typeof this.setCenter.marker === 'object' && typeof point === 'object'){
                        this.addMarker(point,this.setCenter.marker,this.setCenter.marker.clear,true);
                    }
                }
            }
        }
        
    }
 
});

Ext.reg('gmappanel',Ext.ux.GMapPanel);
