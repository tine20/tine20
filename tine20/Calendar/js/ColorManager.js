/* 
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 */
 
Ext.ns('Tine.Calendar');

/**
 * @namespace Tine.Calendar
 * @class Tine.Calendar.ColorManager
 * Colormanager for Coloring Calendar Events <br>
 * 
 * @constructor
 * Creates a new color manager
 * @param {Object} config
 * 
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Tine.Calendar.ColorManager = function(config) {
    Ext.apply(this, config);
};

Ext.apply(Tine.Calendar.ColorManager.prototype, {

    /**
     * gray color set
     * 
     * @type Object 
     * @property gray
     */
    gray: {color: '#808080', light: '#EDEDED', text: '#FFFFFF', lightText: '#FFFFFF'},
    
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
    
    getStrategy: function() {
        var name = Tine.Calendar.ColorManager.colorStrategyBtn.colorStrategy;
        return Tine.Calendar.colorStrategies[name];
    },
    
    /**
     * hack for container only support
     * 
     * @param {Tine.Calendar.Model.Evnet} event
     * @return {Object} colorset
     */
    getColor: function(event, attendeeRecord) {
        var color = this.getStrategy().getColor(event, attendeeRecord);
        
        color = String(color).replace('#', '');
        if (! color.match(/[0-9a-fA-F]{6}/)) {
            return this.gray;
        }
        
        var schema = this.colorSchemata[color];
        return schema ? schema : this.getCustomSchema(color);
    },
    
    getCustomSchema: function(color) {
        var diffs = new Ext.util.MixedCollection();
        for (var key in this.colorSchemata) {
            if (this.colorSchemata.hasOwnProperty(key)) {
                diffs.add(Tine.Calendar.ColorManager.compare(color, key, true), this.colorSchemata[key]);
            }
        }
        
        //NOTE default is string sort
        diffs.keySort('ASC', function(v1,v2) {return v1 > v2 ? 1 : (v1 < v2 ? -1 : 0);});
        
        // cache result
        this.colorSchemata[color] = diffs.first();
        
        return this.colorSchemata[color];
    }
});

Tine.Calendar.ColorManager.str2dec = function(string) {
    var s = String(string).replace('#', ''),
        parts = s.match(/([0-9a-fA-F]{2})([0-9a-fA-F]{2})([0-9a-fA-F]{2})/);
    
    if (! parts || parts.length != 4) return;
    
    return [parseInt('0x' + parts[1]), parseInt('0x' + parts[2]), parseInt('0x' + parts[3])];
};

Tine.Calendar.ColorManager.compare = function(color1, color2, abs) {
    var c1 = Ext.isArray(color1) ? color1 : Tine.Calendar.ColorManager.str2dec(color1),
        c2 = Ext.isArray(color2) ? color2 : Tine.Calendar.ColorManager.str2dec(color2);
    
    if (!c1 || !c2) return;
    
    var diff = [c1[0] - c2[0], c1[1] - c2[1], c1[2] - c2[2]];
    
    return abs ? (Math.abs(diff[0]) + Math.abs(diff[1]) + Math.abs(diff[2])) : diff;
};

Tine.Calendar.ColorManager.colorStrategyBtn = Ext.extend(Ext.Button, {
    scale: 'medium',
    minWidth: 60,
    rowspan: 2,
    iconAlign: 'top',
    requiredGrant: 'readGrant',
    iconCls:'cal-grid-view-color',
    colorStrategy: 'container',
    stateful: true,
    stateId: 'cal-calpanel-color-strategy-btn',
    stateEvents: [],

    initComponent: function() {
        this.app = Tine.Tinebase.appMgr.get('Calendar');

        if (! this.app.featureEnabled('featureColorBy')) {
            // hide button and make sure it isn't pressed
            Tine.log.info('ColorStrategy feature is deactivated');
            this.hidden = true;
            this.stateful = false;
        }

        // init state now to render menu with state applied
        this.initState();

        this.text = this.app.i18n._('Colors');
        this.menu = {
            items: [
                {
                    text: this.app.i18n._('Color by Calendar'),
                    checked: this.colorStrategy == 'container',
                    group: 'colorStrategy',
                    checkHandler: this.changeColorStrategy.createDelegate(this, ['container'])
                }, {
                    text: this.app.i18n._('Color by Tags'),
                    checked: this.colorStrategy == 'tag',
                    group: 'colorStrategy',
                    checkHandler: this.changeColorStrategy.createDelegate(this, ['tag'])
                }, {
                    text: this.app.i18n._('Color by Attendee Role'),
                    checked: this.colorStrategy == 'requiredAttendee',
                    group: 'colorStrategy',
                    checkHandler: this.changeColorStrategy.createDelegate(this, ['requiredAttendee'])
                }
            ]
        };

        Tine.Calendar.ColorManager.colorStrategyBtn.superclass.initComponent.apply(this, arguments);
        Tine.Calendar.ColorManager.colorStrategyBtn = this;
    },

    changeColorStrategy: function(strategy) {
        this.colorStrategy = strategy;
        this.saveState();
        this.app.getMainScreen().getCenterPanel().refresh();
    },

    getState: function() {
        return {colorStrategy: this.colorStrategy}
    }
});

Ext.ux.ItemRegistry.registerItem('Calendar-MainScreenPanel-ViewBtnGrp', Tine.Calendar.ColorManager.colorStrategyBtn, 30);


/**
 * Color Strategies Registry
 * 
 * @type Object
 */
Tine.Calendar.colorStrategies = {};
Tine.Calendar.colorStrategies['container'] = {
    getColor: function(event, attendeeRecord) {
        var container = null,
            color = null,
            schema = null;
        
        if (! Ext.isFunction(event.get)) {
            // tree comes with containers only
            container = event;
        } else {
            
            container = event.get('container_id');
            
            // take displayContainer if user has no access to origin
            if (Ext.isPrimitive(container)) {
                container = event.getDisplayContainer();
            }
        }
        
        return String(container.color).replace('#', '');
    }
};

Tine.Calendar.colorStrategies['requiredAttendee'] = {
    getColor: function(event, attendeeRecord) {
        var color, role;

        if (attendeeRecord) {
            role = attendeeRecord.get('role');

            color = role == 'REQ' ? 'FF0000' : '00FF00'
        } else {
            color = 'C0C0C0' // light gray
        }

        return color;
    }
};

Tine.Calendar.colorStrategies['tag'] = {
    getColor: function(event, attendeeRecord) {
        var color = 'C0C0C0',
            tags = event.get('tags'),
            tag = Ext.isArray(tags) ? tags[0] : null;

        if (tag) {
            color = tag.color;
        }

        return color;
    }
};

