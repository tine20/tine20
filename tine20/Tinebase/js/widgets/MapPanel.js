/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009-2022 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import flag from 'images/icon-set/icon_flag_full.svg';
import {Map, View} from 'ol';
import TileLayer from 'ol/layer/Tile';
import VectorLayer from 'ol/layer/Vector';
import XYZ from 'ol/source/XYZ';
import VectorSource from "ol/source/Vector";
import {fromLonLat} from "ol/proj";
import {Style, Icon} from 'ol/style';
import Feature from 'ol/Feature';
import Point from 'ol/geom/Point';

export default Ext.extend(Ext.Container, {
    /**
     * @cfg {Number}
     */
    zoom: null,
    /**
     * @cfg {Number}
     */
    lon: null,
    /**
     * @cfg {Number}
     */
    lat: null,

    initComponent() {
        this.mapServiceUrl = this.mapServiceUrl || Tine.Tinebase.configManager.get('mapServiceUrl', 'Tinebase');

        this.on('afterrender', this.injectOL, this);
        this.supr().initComponent.call(this);
    },

    async injectOL() {
        if(!this.center && this.lon && this.lat) {
            this.center = fromLonLat(this.lon, this.lat);
            _.defer(() => {this.addFlagLayer(this.lon, this.lat)});
        }

        this.olMap = new Map({
            target: this.el.id,
            layers: [
                new TileLayer({
                    source: new XYZ({
                        url: this.mapServiceUrl.replace(/\/{0,1}$/, '/{z}/{x}/{y}.png')
                    })
                })
            ],
            view: new View({
                center: this.center || fromLonLat([0, 0]),
                zoom: this.zoom || 4
            })
        });

        this.el.select('.ol-rotate').hide()
        this.el.select('.ol-zoom').setStyle({margin: '10px 0px 10px 10px'})
        this.fireEvent('mapAdded', this);
    },

    setCenter(lon, lat) {
        this.center = fromLonLat([lon, lat]);
        this.olMap.getView().setCenter(this.center);
        this.addFlagLayer(lon, lat)
    },

    addFlagLayer(lon, lat) {
        const iconFeature = new Feature({
            geometry: new Point(fromLonLat([lon, lat])),
        })

        const iconStyle = new Style({
            image: new Icon({
                src: decodeURI(flag.replaceAll('"', '')),
            })
        });
        iconFeature.setStyle(iconStyle);

        const vectorSource = new VectorSource({
            features: [iconFeature],
        });

        const vectorLayer = new VectorLayer({
            source: vectorSource,
        });
        this.olMap.addLayer(vectorLayer);
    },

    setZoom(zoom) {
        this.zoom = zoom;
        this.olMap.getView().setZoom(this.zoom);
    },

    doLayout() {
        this.supr().doLayout.apply(this, arguments);
        this.olMap?.updateSize();
    }
});
